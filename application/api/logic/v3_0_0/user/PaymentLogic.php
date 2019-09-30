<?php

namespace app\api\logic\v3_0_0\user;

use app\api\model\v3_0_0\{
    OrderModel, ShopModel, UserModel, UserMessageModel
};
use app\api\model\v2_0_0\UserHasShopModel;
use app\api\Presenter;
use app\api\service\WxAccessTokenService;
use app\common\utils\curl\CurlHelper;
use app\common\utils\jPush\JpushHelper;
use Exception;

class PaymentLogic extends Presenter
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
            /** @var OrderModel $model */
            $model = $orderModel = model(OrderModel::class);
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
            // 日期
            $nowTime = date('Y-m-d H:i:s');
            // 实例化店铺模型
            /** @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            // 查询店铺信息
            $shopInfo = $shopModel->where(['id' => $shopId])->find();
            // 支付完成更新订单数据
            $orderData = [
                'transaction_id' => $transactionId,
                'order_status' => 1,
                'payment_time' => $nowTime
            ];
            $orderResult = $orderModel->where($where)->update($orderData);
            if (empty($orderResult)) {
                $logContent = '订单更新失败，订单编号=' . $outTradeNo;
                throw new Exception($logContent);
            }

            // v3.5.0明细新增2种类型 start (6=预存，7=主题活动预约)
            $userDealType = 1;
            $merchantDealType = 2;
            switch ($orderInfo['order_type']) {
                case 2;
                    $userDealType = $merchantDealType = 6;
                    break;
                case 4;
                    $userDealType = $merchantDealType = 7;
                    break;
            }
            // v3.5.0明细新增2种类型 end

            // 保存用户交易明细数据
            $userExtraData = [
                'order_id' => $orderInfo['id'],
                'type' => $userDealType,
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
            /** @var UserModel $userModel */
            $userModel = model(UserModel::class);
            // 更新前商家余额
            $beforeAmount = $shopInfo['balance'];
            // 更新后商家余额
            $afterAmount = decimalAdd($beforeAmount, $orderInfo['payment_amount']);
            // 更新商家的余额
            $shopResult = $shopModel->where(['id' => $shopId])->update(['balance' => $afterAmount]);
            if (empty($shopResult)) {
                $logContent = '更新商家余额失败，订单编号=' . $outTradeNo;
                throw new Exception($logContent);
            }
            // 保存商家余额明细
            $userExtraData = [
                'shop_id' => $orderInfo['shop_id'],
                'order_id' => $orderInfo['id'],
                'type' => $merchantDealType,
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
            $this->pushMessageAfterPayment($orderInfo, $user, $shopInfo, $nowTime);

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
     * @param Shop $shop 店铺信息
     * @param string $nowTime 日期字符串
     */
    public function pushMessageAfterPayment($order, $user, $shop, $nowTime)
    {
        $field = [
            'order_platform',
            'payment_type',
            'payment_amount',
            'wechat_mp_openid'
        ];
        foreach ($field as $value) {
            $order[$value] = $order[$value] ?? '';
        }
        // 通过H5下单且使用微信支付(服务号推送)
        if ($order['order_platform'] == 3 && $order['payment_type'] == 2) {
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
                        'keyword3' => $shop['shopName'],
                        'keyword4' => date('Y年m月d日 H:i:s', strtotime($nowTime)),
                        'remark' => ['欢迎再次购买，祝生活愉快！', '#008000'],
                    ]
                ]);
            }
        }

        // 极光推送给商家
        $shopExtraPush = ['free_order_flag' => 0, 'shop_income' => $order['payment_amount']];
        $pushMsg = sprintf(config('response.msg35'), $order['payment_amount']);
        $sound = '收到一笔付款.caf';
        $shopPivotModel = model(UserHasShopModel::class);
        // 获取需要推送的店铺用户极光ID
        $shopPivot = $shopPivotModel->getSelectedShopUser(['shopId' => $shop['id'], 'collectPushFlag' => 1]);
        // 需要推送的极光ID
        $pushIds = array_filter(array_column($shopPivot, 'registrationId'));
        if ($pushIds) {
            // 推送和语音播报
            JpushHelper::push($pushIds, [
                'title' => $pushMsg,
                'message' => $pushMsg,
                'sound' => $sound,
                'extras' => ['voice_broadcast' => $shopExtraPush]
            ]);
        }

        // 给店铺发送站内收款消息
        UserMessageModel::create([
            'msg_type' => 1,
            'order_id' => $order['id'],
            'from_user_id' => $user->id,
            'to_shop_id' => $order['shop_id'],
            'title' => $order['payment_amount'],
            'content' => $order['payment_amount'],
            'read_status' => 0,
            'delete_status' => 0,
            'generate_time' => $nowTime
        ]);

        if ($pushIds) {
            $msgCount = UserMessageModel::where(['to_shop_id' => $order['shop_id'], 'read_status' => 0, 'delete_status' => 0])->count(1);
            JpushHelper::message($pushIds, [
                'message' => $pushMsg,
                'contentType' => 'merchantMsgType',
                'extras' => ['msg_count' => $msgCount]
            ]);
        }
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

        $accessTokenService = new WxAccessTokenService();
        try {
            $accessToken = $accessTokenService->getToken();
        } catch (\Exception $e) {
            generateApiLog($e->getMessage());
            return;
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

        $curl = new CurlHelper();

        return $curl->curlRequest($templateMessageUrl, json_encode($params));
    }
}
