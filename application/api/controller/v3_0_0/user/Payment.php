<?php

namespace app\api\controller\v3_0_0\user;

use app\api\Presenter;
use app\common\utils\mcrypt\AesEncrypt;
use app\common\utils\string\StringHelper;
use app\api\logic\v3_0_0\user\PaymentLogic;
use app\api\model\v3_0_0\{
    OrderModel, ShopModel, VideoModel
};
use app\api\model\v3_4_0\{
    ThemeActivityModel
};
use app\api\traits\v3_0_0\PaymentTrait;
use app\api\validate\v3_0_0\PaymentValidate;
use app\common\utils\payment\Payment as ThirdPartyPayment;

class Payment extends Presenter
{
    use PaymentTrait;

    /**
     * 准备支付接口
     *
     * @return Json
     */
    public function prePayment()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(PaymentValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('prePayment')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            $shopId = $paramsArray['shop_id'];
            // 实例化店铺模型
            /** @var ShopModel $shopModel */
            $shopModel = model(ShopModel::class);
            $where = [
                'id' => $shopId
            ];
            // 查询店铺信息
            $shopInfo = $shopModel->getShopInfo($where);
            if (empty($shopInfo)) {
                // 商家不存在或被禁用！
                return apiError(config('response.msg10'));
            }
            $data = [
                'shop_name' => $shopInfo['shop_name'],
                'shop_image' => getImgWithDomain($shopInfo['shop_image']),
                'shop_thumb_image' => getImgWithDomain($shopInfo['shop_thumb_image']),
                'pay_setting_type' => $shopInfo['pay_setting_type'],
                'prestore_money' => $shopInfo['prestore_money'],
                'present_money' => $shopInfo['present_money'],
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
     */
    public function paying()
    {
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
            $checkResult = $validate->scene('paying')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 店铺id
            $shopId = $paramsArray['shop_id'];
            // 实例化店铺模型
            $shopModel = model(ShopModel::class);
            // 查询店铺信息
            $shopInfo = $shopModel->getShopInfo([
                'id' => $shopId
            ]);
            if (empty($shopInfo)) {
                // 商家不存在或被禁用！
                return apiError(config('response.msg10'));
            }
            // 支付类型
            $paymentType = empty($paramsArray['payment_type']) ? null : $paramsArray['payment_type'];
            // 支付金额
            $paymentMoney = empty($paramsArray['payment_money']) ? 0 : $paramsArray['payment_money'];
            // 订单类型
            $orderType = $shopInfo['pay_setting_type'];
            // 日期
            $date = date('Y-m-d H:i:s');
            // 实例化订单模型
            $orderModel = model(OrderModel::class);
            $orderData = [
                'order_num' => StringHelper::generateNum('FO'),
                'user_id' => $userId,
                'shop_id' => $shopId,
                'shop_user_id' => $shopInfo['user_id'],
                'order_amount' => $paymentMoney,
                'payment_type' => $paymentType,
                'payment_amount' => $paymentMoney,
                'generate_time' => $date
            ];
            // 订单类型
            if (!empty($paramsArray['pay_setting_type'])) {
                if ($paramsArray['pay_setting_type'] != $orderType) {
                    // 买单类型与商家设置的类型不一致
                    return apiError(config('response.msg98'));
                }
                $orderData['order_type'] = $orderType;
                if (in_array($orderType, [2, 3])) {
                    $orderData['verification_status'] = 0;
                }
                if ($orderType == 2) {
                    // 商家设置的预存金额
                    $prestoreMoney = $shopInfo['prestore_money'];
                    if ($paymentMoney != $prestoreMoney) {
                        // 支付金额与商家设置的预存金额不一致
                        return apiError(config('response.msg93'));
                    }
                    $orderData['prestore_amount'] = decimalAdd($shopInfo['prestore_money'], $shopInfo['present_money']);
                } elseif ($orderType == 3) {
                    $orderData['order_status'] = 1;
                    $orderData['payment_time'] = $date;
                }
            }
            // 主题活动id
            if (!empty($paramsArray['theme_activity_id'])) {
                // 实例化主题活动模型
                $themeActivityModel = model(ThemeActivityModel::class);
                $themeActivityWhere = [
                    'id' => $paramsArray['theme_activity_id'],
                    'delete_status' => 0,
                    'booking_status' => 1
                ];
                // 查询主题活动信息
                $themeActivityInfo = $themeActivityModel->where($themeActivityWhere)->find();
                if (empty($themeActivityInfo)) {
                    // 主题活动不存在
                    return apiError(config('response.msg83'));
                }
                if ($themeActivityInfo['theme_status'] == 3) {
                    // 活动已结束
                    return apiError(config('response.msg98'));
                }
                if ($themeActivityInfo['booking_people_limit'] > 0) {
                    $orderWhere = [
                        'theme_activity_id' => $paramsArray['theme_activity_id'],
                        'order_status' => 1
                    ];
                    $orderCount = $orderModel->where($orderWhere)->whereTime('generate_time', [$themeActivityInfo['generate_time'], $themeActivityInfo['end_time']])->count();
                    if ($orderCount >= $themeActivityInfo['booking_people_limit']) {
                        // 预约名额已满
                        return apiError(config('response.msg94'));
                    }
                }
                if ($themeActivityInfo['booking_amount'] > 0) {
                    if ($paymentMoney != $themeActivityInfo['booking_amount']) {
                        // 支付金额与商家设置的预存金额不一致
                        return apiError(config('response.msg93'));
                    }
                } else {
                    $orderData['order_status'] = 1;
                    $orderData['payment_time'] = $date;
                }
                $orderData['order_type'] = 4;
                $orderData['theme_activity_id'] = $paramsArray['theme_activity_id'];
                $orderData['verification_status'] = 0;
            }
            // 订单备注
            if (!empty($paramsArray['remark'])) {
                $orderData['remark'] = $paramsArray['remark'];
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
            $payInfo = '';

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
            if (empty($payInfo) && $orderResult['order_status'] != 1) {
                return apiError(config('response.msg13'));
            }
            // h5 不加密
            if ($platform != 3) {
                $aes = new AesEncrypt();
                // 加密支付信息
                $payInfo = $aes->aes128cbcEncrypt($payInfo);
            }
            $data = [
                'order_id' => $orderResult['id'],
                'serial_number' => $orderResult['order_num'],
                'pay_info' => $payInfo
            ];
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '支付接口异常：' . $e->getMessage(). ', 出错文件：' . $e->getFile() . ', 出错行号：' . $e->getLine();
            generateCustomLog($logContent, $logPath);
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
        $payment = ThirdPartyPayment::alipay($config['config']);
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
                    $paymentLogic = new PaymentLogic();
                    // 支付成功后的处理
                    $paymentLogic->handleAfterPaymentNotify($tradeNo, $outTradeNo, $receiptAmount);

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
     * @return Response
     */
    public function wxpayNotify()
    {
        $config = $this->getWxpayConfig();
        $payment = ThirdPartyPayment::wechat($config['config']);
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
                    $paymentLogic = new PaymentLogic();
                    // 支付成功后的处理
                    $paymentLogic->handleAfterPaymentNotify($transactionId, $outTradeNo, $receiptAmount);

                    return $payment->success();
                }
            }
        } catch (\Exception $e) {
            generateCustomLog("wxpay支付结果通知异常：{$e->getMessage()}", $config['config']['log_path']);
        }

        return $payment->fail();
    }

    /**
     * 支付完成接口
     * @return json
     */
    public function finishPayment()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(PaymentValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('finishPayment')->check($paramsArray);
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
            $orderWhere = [
                'order_num' => $orderNumber,
                'user_id' => $userId
            ];
            // 查询订单信息
            $orderInfo = $orderModel->where($orderWhere)->find();
            if (empty($orderInfo)) {
                // 订单不存在
                return apiError(config('response.msg34'));
            }
            // 视频表模型
            $videoModel = model(VideoModel::class);
            // 获取推荐视频
            $videoArray = $videoModel->getRecommendVideo(['limit' => config('parameters.page_size_level_3')]);
            $videoList = [];
            if (!empty($videoArray)) {
                shuffle($videoArray);
                $videoArray = array_slice($videoArray, 0, 4);
                foreach ($videoArray as $value) {
                    $info = [
                        'video_id' => $value['id'],
                        'video_title' => $value['title'],
                        'cover_url' => $value['cover_url'],
                        'video_url' => $value['video_url'],
                        'video_width' => $value['video_width'],
                        'video_height' => $value['video_height'],
                    ];
                    $videoList[] = $info;
                }
            }
            $data = [
                'pay_money' => $orderInfo['payment_amount'],
                'video_list' => $videoList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '支付完成接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}
