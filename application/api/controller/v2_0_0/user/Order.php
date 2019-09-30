<?php

namespace app\api\controller\v2_0_0\user;

use app\api\Presenter;
use think\response\Json;
use app\common\utils\mcrypt\AesEncrypt;
use app\common\utils\string\StringHelper;
use app\api\logic\v2_0_0\user\OrderLogic;
use app\api\model\v2_0_0\{
    FreeRuleModel, OrderModel, ShopModel, UserModel
};
use app\api\traits\v2_0_0\PaymentTrait;
use app\api\validate\v2_0_0\PaymentValidate;
use app\common\utils\payment\Payment;
use think\Response;

class Order extends Presenter
{
    use PaymentTrait;

    /**
     * 准备支付接口
     *
     * @return Json
     */
    public function preparePayment()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(PaymentValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('preparePayment')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            $shopId = $paramsArray['shop_id'];
            // 实例化店铺模型
            /** @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            // 查询店铺信息
            $shopInfo = $shopModel->getShopInfo([
                'shopId' => $shopId
            ]);
            if (empty($shopInfo)) {
                // 商家不存在或被禁用！
                return apiError(config('response.msg10'));
            }
            // 用户id
            $userId = $this->getUserId();
            if (!$userId) {
                return apiError(config('response.msg9'));
            }
            // 实例化用户在店铺的免单规则模型
            /** @var FreeRuleModel $freeRuleModel */
            $freeRuleModel = model(FreeRuleModel::class);
            // 查询用户在店铺的免单规则
            $freeRule = $freeRuleModel->getFreeRule([
                'user_id' => $userId,
                'shop_id' => $shopId,
                'status' => 1
            ]);
            // 免单需消费的次数
            $freeOrderFrequency = $shopInfo['freeOrderFrequency'];
            // 该轮免单已消费的次数
            $orderCount = 0;
            // 免单的金额
            $freeMoney = '0';
            if (!empty($freeRule)) {
                $freeOrderFrequency = $freeRule['shop_free_order_frequency'];
                $orderCount = $freeRule['order_count'];
                if ($freeRule['shop_free_order_frequency'] <= $freeRule['order_count']) {
                    $freeMoney = decimalDiv($freeRule['consume_amount'], $freeRule['order_count']);
                }
            }
            $data = [
                'shop_name' => $shopInfo['shopName'],
                'shop_image' => getImgWithDomain($shopInfo['shopImage']),
                'shop_thumb_image' => getImgWithDomain($shopInfo['shopThumbImage']),
                'free_order_frequency' => $freeOrderFrequency,
                'order_count' => $orderCount,
                'free_money' => $freeMoney,
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '准备支付接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 支付接口
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function payment()
    {
        //return apiError(config('response.msg78'));
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        try {
            // 订单日志路径
            $logPath = config('parameters.order_log_path');
            // 获取请求参数
            $paramsArray = input();
            // 用户id
            $userId = $this->getUserId();
            // 日志信息
            $logContent = '用户id='.$userId.', 下单请求参数信息'.serialize($paramsArray);
            // 日志级别
            $logLevel = config('parameters.log_level');
            // 记录请求日志
            generateCustomLog($logContent, $logPath, $logLevel['info']);

            if (!$userId) {
                return apiError(config('response.msg9'));
            }
            // 实例化验证器
            $validate = validate(PaymentValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('payment')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 店铺id
            $shopId = $paramsArray['shop_id'];
            // 实例化店铺模型
            /** @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            // 查询店铺信息
            $shopInfo = $shopModel->getShopInfo([
                'shopId' => $shopId
            ]);
            if (empty($shopInfo)) {
                // 商家不存在或被禁用！
                return apiError(config('response.msg10'));
            }

            // 实例化用户在店铺的免单规则模型
            /** @var FreeRuleModel $freeRuleModel */
            $freeRuleModel = model(FreeRuleModel::class);
            $freeRuleCondition = [
                'user_id' => $userId,
                'shop_id' => $shopId,
                'status' => 1
            ];
            // 查询用户在店铺的免单规则
            $freeRule = $freeRuleModel->getFreeRule($freeRuleCondition);
            // 本次是否可免单
            $freeFlag = 0;
            // 免单金额
            $freeMoney = 0;
            if (!empty($freeRule) && $freeRule['shop_free_order_frequency'] <= $freeRule['order_count']) {
                $freeFlag = 1;
                // 免单金额
                $freeMoney = decimalDiv($freeRule['consume_amount'], $freeRule['order_count']);
            }

            // 支付类型
            $paymentType = $paramsArray['payment_type'] ?? 0;
            // 支付金额
            $paymentMoney = $paramsArray['payment_money'] ?? 0;
            // 订单金额
            $orderMoney = $paramsArray['order_money'];
            // 如果 免单金额 加 支付金额 小于 订单金额
            if (decimalAdd($freeMoney, $paymentMoney) < $orderMoney) {
                // 支付金额错误
                return apiError(config('response.msg23'));
            }
            // 日期
            $date = date('Y-m-d H:i:s');
            // 实例化订单模型
            /** @var OrderModel $model */
            $model = $orderModel = model(OrderModel::class);

            // 启动事务
            $model->startTrans();
            $orderData = [
                'order_num' => StringHelper::generateNum('FO'),
                'user_id' => $userId,
                'shop_id' => $shopId,
                'shop_user_id' => $shopInfo['userId'],
                'order_amount' => $orderMoney,
                'order_status' => 0,
                'free_flag' => $freeFlag,
                'generate_time' => $date
            ];
            // 订单备注
            if (!empty($paramsArray['remark'])) {
                $orderData['remark'] = $paramsArray['remark'];
            }
            if ($freeMoney > 0) {
                $orderData['free_money'] = $freeMoney;
                if ($freeMoney >= $orderMoney) {
                    $orderData['order_status'] = 1; // 全免则订单状态直接改为1
                    $orderData['payment_time'] = $date;
                    $paymentType = $paymentMoney = 0; // 全免不需要付钱
                    $freeRuleData = [
                        'order_count' => $freeRule['order_count'] + 1,
                        'update_time' => $date,
                        'status' => 0,
                        'free_money' => $freeMoney
                    ];
                    // 删除当前的免单规则
                    $freeRuleResult = $freeRuleModel->where($freeRuleCondition)->update($freeRuleData);
                    if (empty($freeRuleResult)) {
                        $logContent = "删除免单规则失败，用户id={$userId}, 订单编号={$orderData['order_num']}, 店铺id={$shopId}";
                        generateCustomLog($logContent, $logPath);
                    }
                }
            }
            // 非全免时支付信息为必传
            if ( $orderData['order_status'] != 1 && (empty($paymentType) || empty($paymentMoney)) ) {
                return apiError(config('response.msg20'));
            }
            if ($paymentType && $paymentMoney) {
                // 最低支付金额
                $minPaymentMoney = config('parameters.min_payment_money');
                // 支付金额不能低于%s元
                if ($paymentMoney < $minPaymentMoney) {
                    $msg = config('response.msg37');
                    return apiError(sprintf($msg, $minPaymentMoney));
                }
                $orderData['payment_type'] = $paymentType;
                $orderData['payment_amount'] = $paymentMoney;
            }

            if (empty($freeRule)) {
                $shopFreeOrderFrequency = $shopInfo['freeOrderFrequency'];
                $currentNumber = 1;
                $consumeAmount = empty($orderData['payment_amount']) ? 0 : $orderData['payment_amount'];
            } else {
                $shopFreeOrderFrequency = $freeRule['shop_free_order_frequency'];
                $currentNumber = $freeRule['order_count'] + 1;
                $consumeAmount = !empty($orderData['payment_amount']) ? decimalAdd($freeRule['consume_amount'], $orderData['payment_amount']) : $freeRule['consume_amount'];
                // 免单规则id
                $orderData['free_rule_id'] = $freeRule['id'];
            }
            // 店铺该轮设置的免单次数
            $orderData['shop_free_order_frequency'] = $shopFreeOrderFrequency;
            // 本单当前次数
            $orderData['current_number'] = $currentNumber;
            // 该轮消费总额
            if (!empty($consumeAmount)) {
                $orderData['consume_amount'] = $consumeAmount;
            }
            // 平台
            $platform = request()->header('platform');
            if ($platform) {
                $orderData['order_platform'] = $platform;
            }
            // 微信openid
            if (!empty($paramsArray['wechat_mp_openid'])) {
                $orderData['wechat_mp_openid'] = $paramsArray['wechat_mp_openid'];
            }
            // 写入数据
            $orderResult = $orderModel::create($orderData);
            if (empty($orderResult['id'])) {
                return apiError(config('response.msg11'));
            }
            // 提交事务
            $model->commit();

            // 订单状态为已付款，则当前订单为免单订单，进行推送消息
            if ($orderResult['order_status'] == 1) {
                /** @var UserModel $userModel */
                $userModel = model(UserModel::class);
                // 下单用户信息
                $user = $userModel->where(['id' => $orderResult['user_id']])->find();
                $orderLogic = new OrderLogic();
                $orderLogic->pushMessageAfterPayment($orderResult, $user, $shopInfo, $date);
            }

            $payInfo = '';
            if ($paymentType && $paymentMoney) {
                // 第三方支付需要的交易信息
                $tradeInfo = [
                    'num' => $orderResult['order_num'], // 编号
                    'money' => $paymentMoney // 金额
                ];
                // 支付宝
                if ($paymentType == 1) {
                    // h5支付
                    if ($platform == 3) {
                        // 支付完跳转的 url
                        $returnUrl = config('app_host').'/h5/paySuccess.html?serial_number='.$orderResult['order_num'];
                        $extraConfig = ['return_url' => $returnUrl];
                        $payInfo = $this->alipayUnified($tradeInfo, 2, $extraConfig);
                    } else {
                        $payInfo = $this->alipayUnified($tradeInfo, 1);
                    }
                } elseif ($paymentType == 2) { // 微信
                    // h5支付
                    if ($platform == 3 || $platform == 4) {
                        // 微信openid
                        if (empty($paramsArray['wechat_mp_openid'])) {
                            return apiError(config('response.msg36'));
                        }
                        $payType = $platform == 3 ? 4 : 2;
                        $payInfo = $this->wxpayUnified($tradeInfo, $payType, $paramsArray['wechat_mp_openid']);
                    } else {
                        $payInfo = $this->wxpayUnified($tradeInfo, 1);
                    }
                }
                if (empty($payInfo)) {
                    return apiError(config('response.msg13'));
                }
                // h5 不加密
                if ($platform != 3) {
                    $aes = new AesEncrypt();
                    // 加密支付信息
                    $payInfo = $aes->aes128cbcEncrypt($payInfo);
                }
            }
            $data = [
                'order_id' => $orderResult['id'],
                'serial_number' => $orderResult['order_num'],
                'pay_info' => $payInfo
            ];
            return apiSuccess($data);
        } catch (\Exception $e) {
            // 回滚事务
            isset($model) && $model->rollback();
            $logContent = '支付接口异常：' . $e->getMessage(). ', 出错文件：' . $e->getFile() . ', 出错行号：' . $e->getLine();
            generateCustomLog($logContent, $logPath);
        }
        return apiError();
    }

    /**
     * 获取订单状态
     * @return json
     */
    public function getOrderStatus()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(PaymentValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('getOrderStatus')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 用户id
            $userId = $this->getUserId();
            if (!$userId) {
                return apiError(config('response.msg9'));
            }
            // 订单编号
            $orderNumber = $paramsArray['order_number'];
            // 实例化订单模型
            /** @var OrderModel $orderModel */
            $orderModel = model(OrderModel::class);
            // 查询订单信息
            $orderInfo = $orderModel->where([
                'order_num' => $orderNumber,
                'user_id' => $userId
            ])->find();
            if (empty($orderInfo)) {
                // 订单不存在
                return apiError(config('response.msg34'));
            }
            $data = [
                'status' => $orderInfo['order_status'],
                'serial_number' => $orderInfo['order_num']
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '获取订单状态接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 支付完成接口
     * @return json
     */
    public function paymentCompleted()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(PaymentValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('paymentCompleted')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 用户id
            $userId = $this->getUserId();
            if (!$userId) {
                return apiError(config('response.msg9'));
            }
            // 订单编号
            $orderNumber = $paramsArray['order_number'];
            // 实例化订单模型
            /** @var OrderModel $orderModel */
            $orderModel = model(OrderModel::class);
            // 查询订单信息
            $orderInfo = $orderModel->getOrderInfo([
                'orderNum' => $orderNumber,
                'userId' => $userId
            ]);
            if (empty($orderInfo)) {
                // 订单不存在
                return apiError(config('response.msg34'));
            }
            $alsoNeedOrderCount = $orderInfo['shop_free_order_frequency'] - $orderInfo['current_number'];
            $data = [
                'shop_id' => $orderInfo['shop_id'],
                'shop_name' => $orderInfo['shop_name'],
                'shop_thumb_image' => getImgWithDomain($orderInfo['shop_thumb_image']),
                'order_status' => $orderInfo['order_status'],
                'free_flag' => $orderInfo['free_flag'],
                'free_money' => $orderInfo['free_money'],
                'pay_money' => $orderInfo['payment_amount'],
                'also_need_order_count' => $alsoNeedOrderCount < 0 ? 0 : $alsoNeedOrderCount,
                'predict_free_money' => $orderInfo['current_number'] == 0 ? '0' : decimalDiv($orderInfo['consume_amount'], $orderInfo['current_number']),
                'share_url' => config('app_host') . '/h5/storeDetail.html?shop_id=' . $orderInfo['shop_id'],
                'free_rule_id' => $orderInfo['free_rule_id'],
                'free_order_frequency' => $orderInfo['shop_free_order_frequency']
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '支付完成接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 支付宝支付异步通知
     *
     * @return Response
     */
    public function alipayNotify()
    {
        $config = $this->getAlipayConfig();
        $payment = Payment::alipay($config['config']);
        // 记录通知参数
        generateCustomLog('Receive Alipay Request:' . serialize($_POST), $config['config']['log_path'], 'info');

        try {
            $notify = $payment->verify();
            if (!empty($notify)) {
                // 交易状态
                $tradeStatus = $notify['trade_status'];
                // 判断是否支付完成或交易完成
                if ($tradeStatus === 'TRADE_SUCCESS' || $tradeStatus === 'TRADE_FINISHED') {
                    // 支付宝交易号
                    $tradeNo = $notify['trade_no'];
                    // 商户订单号
                    $outTradeNo = $notify['out_trade_no'];
                    // 实收金额
                    $receiptAmount = $notify['receipt_amount'] ?? 0;
                    $orderLogic = new OrderLogic();
                    // 支付成功后的处理
                    $orderLogic->handleAfterPaymentNotify($tradeNo, $outTradeNo, $receiptAmount);

                    return $payment->success();
                }
            }
        } catch (\Exception $e) {
            generateCustomLog("alipay支付异步通知接口异常：{$e->getMessage()}", $config['config']['log_path']);
        }

        return $payment->fail();
    }

    /**
     * 微信支付异步通知
     *
     * @return mixed|Response
     *
     * @throws \Exception
     */
    public function wxpayNotify()
    {
        $config = $this->getWxpayConfig();
        $payment = Payment::wechat($config['config']);
        // 记录通知参数
        generateCustomLog('Receive Wechat Request:' . file_get_contents('php://input'), $config['config']['log_path'], 'info');

        try {
            $notify = $payment->verify();
            if (!empty($notify)) {
                if ($notify['return_code'] === 'SUCCESS' && $notify['result_code'] === 'SUCCESS') {
                    // 微信支付订单号
                    $transactionId = $notify['transaction_id'];
                    // 商户订单号
                    $outTradeNo = $notify['out_trade_no'];
                    // 总金额,微信金额最小单位是分
                    $receiptAmount = $notify['total_fee'];
                    $orderLogic = new OrderLogic();
                    // 支付成功后的处理
                    $orderLogic->handleAfterPaymentNotify($transactionId, $outTradeNo, $receiptAmount);

                    return $payment->success();
                }
            }
        } catch (\Exception $e) {
            generateCustomLog("wxpay支付结果通知异常：{$e->getMessage()}", $config['config']['log_path']);
        }

        return $payment->fail();
    }
}
