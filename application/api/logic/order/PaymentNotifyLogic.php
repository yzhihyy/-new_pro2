<?php

namespace app\api\logic\order;

use app\common\utils\curl\CurlHelper;
use Exception;
use app\api\logic\BaseLogic;
use app\common\utils\jPush\JpushHelper;

class PaymentNotifyLogic extends BaseLogic
{
    /**
     * 订单支付完成后的相关操作
     *
     * @param string $transactionId 第三方支付订单号
     * @param string $outTradeNo    商户订单号
     * @param string $receiptAmount 实付金额
     *
     * @return mixed
     * @throws Exception
     */
    public function handleAfterPaymentNotify($transactionId, $outTradeNo, $receiptAmount)
    {
        try {
            // 实例化订单模型
            $model = $orderModel = model('api/Order');
            // 启动事务
            $model->startTrans();
            $where = ['order_num' => $outTradeNo];
            // 根据编号取得订单信息
            $orderInfo = $orderModel->where($where)->find();
            if (empty($orderInfo)) {
                $logContent = '订单不存在，订单编号=' . $outTradeNo;
                throw new Exception($logContent);
            }
            // 判断状态, 状态不对则不继续处理, 可能是第三方支付的重复通知
            if ($orderInfo['order_status'] == 1) {
                throw new Exception('订单状态错误，订单编号=' . $outTradeNo);
            }
            // 支付金额
            $paymentAmount = $orderInfo['payment_amount'];
            // 支付方式
            $paymentType = $orderInfo['payment_type'];
            // 用户id
            $userId = $orderInfo['user_id'];
            // 微信支付单位是分
            if ($paymentType == 2) {
                // 支付金额
                $paymentAmount = bcmul($paymentAmount, 100);
            }
            // 判断支付的金额是否等于实收的金额
            if ($paymentAmount != $receiptAmount) {
                $logContent = "订单支付金额{$paymentAmount}不等于实收金额{$receiptAmount}，订单编号=" . $outTradeNo;
                throw new Exception($logContent);
            }

            // 店铺id
            $shopId = $orderInfo['shop_id'];
            // 免单标识
            $freeFlag = $orderInfo['free_flag'];
            // 实例化用户在店铺的免单规则模型
            $freeRuleModel = model('api/FreeRule');
            $freeRuleCondition = [
                'user_id' => $userId,
                'shop_id' => $shopId,
                'status' => 1
            ];
            // 日期
            $nowTime = date('Y-m-d H:i:s');
            // 实例化店铺模型
            $shopModel = model('api/Shop');
            // 查询店铺信息
            $shopInfo = $shopModel->getShopInfo(['id' => $shopId]);
            // 查询用户在店铺的免单规则
            $freeRule = $freeRuleModel->getFreeRule($freeRuleCondition);
            if (empty($freeRule)) {
                if (!empty($shopInfo['free_order_frequency'])) {
                    $freeRuleData = [
                        'user_id' => $userId,
                        'shop_id' => $shopId,
                        'shop_free_order_frequency' => $shopInfo['free_order_frequency'],
                        'order_count' => 1,
                        'consume_amount' => $orderInfo['payment_amount'],
                        'generate_time' => $nowTime,
                        'update_time' => $nowTime
                    ];
                    // 新增免单规则
                    $freeRuleResult = $freeRuleModel::create($freeRuleData);
                    if (empty($freeRuleResult['id'])) {
                        $logContent = "新增免单规则失败，用户id={$userId}, 订单编号={$outTradeNo}, 免单次数={$shopInfo['free_order_frequency']}";
                        generateApiLog($logContent);
                    } else {
                        // 免单规则id
                        $freeRuleId = $freeRuleResult['id'];
                    }
                }
            } else {
                $freeRuleData = [
                    'order_count' => $freeRule['order_count'] + 1,
                    'consume_amount' => decimalAdd($freeRule['consume_amount'], $orderInfo['payment_amount']),
                    'update_time' => $nowTime
                ];
                if ($freeFlag == 1) {
                    $freeRuleData['status'] = 0;
                    if (!empty($orderInfo['free_money']) && $orderInfo['free_money'] > 0) {
                        $freeRuleData['free_money'] = $orderInfo['free_money'];
                    }
                    // 删除当前的免单规则
                    $freeRuleResult = $freeRuleModel->where($freeRuleCondition)->update($freeRuleData);
                    if (empty($freeRuleResult)) {
                        $logContent = "删除免单规则失败，用户id={$userId}, 订单编号={$outTradeNo}, 店铺id={$shopId}";
                        generateApiLog($logContent);
                    }
                } else {
                    // 更新免单规则中的订单次数
                    $freeRuleResult = $freeRuleModel->where($freeRuleCondition)->update($freeRuleData);
                    if (empty($freeRuleResult)) {
                        $logContent = '更新免单规则数据失败，订单编号=' . $outTradeNo;
                        generateApiLog($logContent);
                    }
                }
            }

            // 支付完成更新订单数据
            $orderData = [
                'transaction_id' => $transactionId,
                'order_status' => 1,
                'payment_time' => $nowTime
            ];
            // 免单规则id
            if (!empty($freeRuleId)) {
                $orderData['free_rule_id'] = $freeRuleId;
            }
            $orderResult = $orderModel->where($where)->update($orderData);
            if (empty($orderResult)) {
                $logContent = '订单更新失败，订单编号=' . $outTradeNo;
                throw new Exception($logContent);
            }

            // 保存用户交易明细数据
            $userExtraData = [
                'order_id' => $orderInfo['id'],
                'type' => 1,
                'status' => 3,
                'payment_type' => $paymentType
            ];
            // 保存用户交易明细
            $recordResult = $this->saveUserTransactionsRecord($userId, $orderInfo['payment_amount'], $userExtraData);
            if (empty($recordResult)) {
                $logContent = '保存用户交易明细失败，用户id=' . $userId . ', 订单编号=' . $outTradeNo;
                generateApiLog($logContent);
            }

            // 实例化用户模型
            $userModel = model('api/User');
            $userCondition = ['id' => $orderInfo['shop_user_id']];
            // 店铺用户信息
            $shopUserInfo = $userModel->where($userCondition)->find();
            if (empty($shopUserInfo)) {
                $logContent = '商家用户不存在，商家id='.$shopId.', 订单编号=' . $outTradeNo;
                throw new Exception($logContent);
            }
            // 更新前商家余额
            $beforeAmount = $shopUserInfo['money'];
            // 更新后商家余额
            $afterAmount = decimalAdd($beforeAmount, $orderInfo['payment_amount']);
            // 更新商家的余额
            $userResult = $userModel->where($userCondition)->update(['money' => $afterAmount]);
            if (empty($userResult)) {
                $logContent = '更新商家余额失败，订单编号=' . $outTradeNo;
                throw new Exception($logContent);
            }
            // 保存商家余额明细
            $userExtraData = [
                'order_id' => $orderInfo['id'],
                'type' => 2,
                'status' => 3,
                'before_amount' => $beforeAmount,
                'after_amount' => $afterAmount
            ];
            // 保存商家用户余额明细
            $recordResult = $this->saveUserTransactionsRecord($orderInfo['shop_user_id'], $orderInfo['payment_amount'], $userExtraData);
            if (empty($recordResult)) {
                $logContent = '保存商家用户交易明细失败，商家用户id=' . $orderInfo['shop_user_id'] . ', 订单编号=' . $outTradeNo;
                generateApiLog($logContent);
            }

            // 提交事务
            $model->commit();

            // 下单用户信息
            $user = $userModel->where(['id' => $userId])->find();
            // 支付完推送消息
            $this->pushMessageAfterPayment($orderInfo, $user, $shopUserInfo, $shopInfo, $nowTime);

        } catch (Exception $e) {
            // 回滚事务
            isset($model) && $model->rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 支付完推送消息
     *
     * @param Order $order 订单信息
     * @param User $user 下单的用户信息
     * @param User $shopUser 商家对应的用户信息
     * @param Shop $shop 店铺信息
     * @param string $nowTime 日期字符串
     */
    public function pushMessageAfterPayment($order, $user, $shopUser, $shop, $nowTime)
    {
        $field = [
            'order_platform',
            'payment_type',
            'payment_amount',
            'wechat_mp_openid'
        ];
        foreach ($field as $value) {
            $order[$value] = isset($order[$value]) ? $order[$value] : '';
        }
        // 是否免单标志
        $freeOrderFlag = $order['free_flag'];
        // 预计免单金额
        $expectedFreeAmount = decimalDiv($order['consume_amount'], $order['current_number']);
        // 通过APP下单，用极光推送
        if (in_array($order['order_platform'], [1, 2])) {
            // 给用户发送极光推送
            if (!empty($user['registration_id'])) {
                // 本单免单，使用免单的提示语
                if ($freeOrderFlag == 1) {
                    $pushMsg = sprintf(config('response.msg47'), $order['free_money']);
                } else {
                    $pushMsg = sprintf(config('response.msg48'), $expectedFreeAmount);
                }
                JpushHelper::push([$user['registration_id']], [
                    'title' => $pushMsg,
                    'message' => $pushMsg
                ]);
            }
        }
        // 通过H5下单
        elseif ($order['order_platform'] == 3) {
            // 使用支付宝支付(发送短信)
            if ($order['payment_type'] == 1) {
                // 发送短信验证码
                $this->sendCaptcha([
                    'phone' => $user['phone'],
                    'captcha' => generateTelCode(),
                    'smsParam' => json_encode(['amount' => $expectedFreeAmount]),
                    'smsTemplateCode' => config('aliyunCaptcha.paymentTemplateId')
                ]);
            }
            // 使用微信支付(服务号推送)
            elseif ($order['payment_type'] == 2) {
                // 有 openid 才推送(如果未关注公众号则会推送失败)
                if ($order['wechat_mp_openid']) {
                    $this->wxTemplateMessage([
                        'touser' => $order['wechat_mp_openid'],
                        'template_id' => config('wechat.payment_template_message_id'),
                        'url' => '',
                        'data' => [
                            'first' => ['恭喜您支付成功！', '#1616ff'],
                            'keyword1' => $order['order_num'],
                            'keyword2' => $order['payment_amount'] . '元',
                            'keyword3' => $shop['shop_name'],
                            'keyword4' => date('Y年m月d日 H:i:s', strtotime($nowTime)),
                            'remark' => ['欢迎再次购买，祝生活愉快！', '#008000'],
                        ]
                    ]);
                }
            }
        }

        // 极光推送给商家
        $shopExtraPush = ['free_order_flag' => $freeOrderFlag, 'shop_income' => '0'];
        // 本单免单，使用免单的提示语
        if ($freeOrderFlag == 1) {
            $pushMsg = config('response.msg50');
            $sound = '免单成功.caf';
        } else {
            $pushMsg = sprintf(config('response.msg35'), $order['payment_amount']);
            $shopExtraPush['shop_income'] = $order['payment_amount'];
            $sound = '收到一笔付款.caf';
        }
        JpushHelper::push([$shopUser['registration_id']], [
            'title' => $pushMsg,
            'message' => $pushMsg,
            'sound' => $sound,
            'extras' => ['voice_broadcast' => $shopExtraPush]
        ]);
    }

    /**
     * 微信模板消息
     *
     * @param array $data
     *
     * @return array
     */
    public function wxTemplateMessage(array $data = [])
    {
        $message = [
            'touser' => '',
            'template_id' => '',
            'url' => '',
            'miniprogram' => '',
            'appid' => '',
            'pagepath' => '',
            'data' => []
        ];
        $required = ['touser', 'template_id'];

        $accessToken = getCustomCache('wechat_mp_access_token');
        $curl = new CurlHelper();
        // access_token does not exist or expires
        if (empty($accessToken)) {
            $url = config('wechat.mp_access_token_url');
            $url = str_replace(
                ['mp_appid_str', 'mp_app_secret_str'],
                [config('wechat.mp_appid'), config('wechat.mp_app_secret')],
                $url
            );
            $result = $curl->curlRequest($url);
            $resultArray = json_decode($result, true);
            if (empty($resultArray)) {
                return;
//                throw new \RuntimeException('WeChat not responding!');
            }
            if (isset($resultArray['errcode'])) {
                return;
//                throw new \LogicException('Request access_token fail: ' . $result);
            }

            $accessToken = $resultArray['access_token'];
            setCustomCache('wechat_mp_access_token', $accessToken, $resultArray['expires_in'] - 1200);
        }

        $templateMessageUrl = str_replace('access_token_str', $accessToken, config('wechat.mp_send_template_message_url'));
        $params = array_merge($message, $data);
        foreach ($params as $key => $value) {
            if (in_array($key, $required, true) && empty($value) && empty($message[$key])) {
                return;
//                throw new \InvalidArgumentException(sprintf('Attribute "%s" can not be empty!', $key));
            }

            $params[$key] = empty($value) ? $message[$key] : $value;
        }

        $params['data'] = $params['data'] ?? [];
        // building data array
        foreach ($params['data'] as $key => &$value) {
            if (is_array($value)) {
                if (isset($value['value'])) {
                    continue;
                }

                if (count($value) >= 2) {
                    $value = [
                        'value' => $value[0],
                        'color' => $value[1],
                    ];
                }
            } else {
                $value = [
                    'value' => strval($value),
                ];
            }
        }

        return $curl->curlRequest($templateMessageUrl, json_encode($params));
    }
}
