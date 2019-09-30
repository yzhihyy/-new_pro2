<?php

namespace app\api\controller\v3_7_0\user;

use app\api\Presenter;
use app\api\traits\v3_7_0\PaymentTrait;
use app\api\logic\v3_7_0\user\PaymentLogic;
use app\common\utils\payment\Payment as ThirdPartyPayment;

class Payment extends Presenter
{
    use PaymentTrait;

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
     * 苹果支付app通知
     *
     * @return Response
     */
    public function applePayNotify()
    {
        $config = config('applePay.apple_pay_config');
        try {
            // 获取请求参数
            $paramsArray = input();
            if (empty($paramsArray['apple_receipt']) || empty($paramsArray['order_number'])) {
                return apiError(config('response.msg53'));
            }
            //苹果内购的验证收据
            $receiptData = $paramsArray['apple_receipt'];
            // 验证支付状态
            $result = validateApplePay($receiptData);
            // 记录通知参数
            generateCustomLog('Receive Apple Pay Request:' . serialize($_POST) . '苹果支付验证返回数据:' . serialize($result), $config['log_path'], 'info');
            if($result['status']){
                $notify = $result['data']['receipt']['in_app'][0];
                // 支付订单号
                $transactionId = $notify['transaction_id'];
                // 商户订单号
                $outTradeNo = $paramsArray['order_number'];
                // 总金额
                $receiptAmount = '';
                $paymentLogic = new PaymentLogic();
                // 支付成功后的处理
                $paymentLogic->handleAfterPaymentNotify($transactionId, $outTradeNo, $receiptAmount, $notify['product_id']);

                return apiSuccess(['order_number' => $outTradeNo]);
            } else {
                // 验证不通过
                return apiError($result['message']);
            }
        } catch (\Exception $e) {
            generateCustomLog("苹果支付结果通知异常：{$e->getMessage()}", $config['log_path']);
        }

        return apiError();
    }
}