<?php

namespace app\api\controller\order;

use Exception;
use app\api\traits\PaymentTrait;
use app\api\Presenter;
use app\api\logic\order\PaymentNotifyLogic;
use app\common\utils\payment\Payment;

class PaymentNotify extends Presenter
{
    use PaymentTrait;

    /**
     * 支付宝支付异步通知
     *
     * @return Response
     */
    public function paymentAlipayNotify()
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
                    $receiptAmount = isset($notify['receipt_amount']) ? $notify['receipt_amount'] : 0;
                    $paymentNotifyLogic = new PaymentNotifyLogic();
                    // 支付完成后的相关操作
                    $paymentNotifyLogic->handleAfterPaymentNotify($tradeNo, $outTradeNo, $receiptAmount);

                    return $payment->success();
                }
            }
        } catch (Exception $e) {
            $logContent = 'alipay支付异步通知接口异常：' . $e->getMessage();
            generateCustomLog($logContent, $config['config']['log_path']);
        }

        return $payment->fail();
    }

    /**
     * 微信支付异步通知
     *
     * @return mixed|Response
     * @throws Exception
     */
    public function paymentWxpayNotify()
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
                    // 总金额，微信金额最小单位是分
                    $receiptAmount = $notify['total_fee'];
                    $paymentNotifyLogic = new PaymentNotifyLogic();
                    // 支付完成后的相关操作
                    $paymentNotifyLogic->handleAfterPaymentNotify($transactionId, $outTradeNo, $receiptAmount);

                    return $payment->success();
                }
            }
        } catch (Exception $e) {
            $logContent = 'wxpay支付结果通知异常：' . $e->getMessage();
            generateCustomLog($logContent, $config['config']['log_path']);
        }

        return $payment->fail();
    }

}
