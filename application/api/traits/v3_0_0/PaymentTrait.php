<?php

namespace app\api\traits\v3_0_0;

use app\common\utils\payment\Payment;

trait PaymentTrait
{
    /**
     * TODO 目前仅支持APP支付,其它类型支付未测试
     * 支付宝预支付
     *
     * @param array $order 订单信息
     * @param int $payType 支付方式 1:APP支付, 2:手机网站支付, 3:电脑支付, 4:扫码支付
     * @param array $extraConfig
     *
     * @return mixed|string
     */
    public function alipayUnified($order, $payType = 1, $extraConfig = [])
    {
        $paymentConfig = $this->getAlipayConfig($payType);
        $config = array_merge($paymentConfig['config'], $extraConfig);
        $params = [
            'out_trade_no' => $order['num'],
            'total_amount' => bcsub($order['money'], 0, 2),
            'subject' => $config['subject'],
            'timeout_express' => $config['timeout_express']
        ];

        generateCustomLog("Paying An {$paymentConfig['method']} Order:" . serialize($params), $config['log_path'], 'debug');
        $payInfo = Payment::alipay($config)->{$paymentConfig['method']}($params);

        return $payInfo;
    }

    /**
     * TODO 目前仅支持APP和小程序支付,其它类型支付未测试
     * 微信预支付
     *
     * @param array $order 订单信息
     * @param int $payType 支付方式 1:APP支付, 2:小程序支付, 3:H5支付, 4:公众号支付, 5:扫码支付
     * @param string $openid
     * @param array $extraConfig
     *
     * @return mixed|string
     */
    public function wxpayUnified($order, $payType = 1, $openid = null, $extraConfig = [])
    {
        $paymentConfig = $this->getWxpayConfig($payType);
        $config = array_merge($paymentConfig['config'], $extraConfig);
        $params = [
            'out_trade_no' => $order['num'],
            'total_fee' => bcmul($order['money'], 100, 0),
            'body' => $config['body']
        ];
        !is_null($openid) && $params['openid'] = $openid;
        generateCustomLog("Paying An {$paymentConfig['method']} Order:" . serialize($params), $config['log_path'], 'debug');
        $payInfo = Payment::wechat($config)->{$paymentConfig['method']}($params);

        return $payInfo;
    }

    /**
     * 获取支付宝支付配置
     *
     * @param int $payType 支付方式 1:APP支付, 2:手机网站支付, 3:电脑支付, 4:扫码支付
     *
     * @return array
     */
    public function getAlipayConfig($payType = 1)
    {
        $paymentConfig = [];
        switch ($payType) {
            case 2:
                $config = config('alipay.alipay_config');
                $paymentConfig['config'] = [
                    'app_id' => $config['app_id'],
                    'subject' => $config['title'],
                    'timeout_express' => '30m',
                    'rsa_private_key' => $config['rsa_private_key'],
                    'alipay_public_key' => $config['rsa_public_key'],
                    'notify_url' => isset($config['notify_url_v3_0_0']) ? $config['notify_url_v3_0_0'] : '',
                    'log_path' => $config['log_path'],
                    // 'jump_method' => 'get' // 默认返回form表单，传 get 时返回链接
                ];
                $paymentConfig['method'] = 'wap';
                break;
            default:
                $config = config('alipay.alipay_config');
                $paymentConfig['config'] = [
                    'app_id' => $config['app_id'],
                    'subject' => $config['title'],
                    'timeout_express' => '30m',
                    'rsa_private_key' => $config['rsa_private_key'],
                    'alipay_public_key' => $config['rsa_public_key'],
                    'notify_url' => isset($config['notify_url_v3_0_0']) ? $config['notify_url_v3_0_0'] : '',
                    'log_path' => $config['log_path']
                ];
                $paymentConfig['method'] = 'app';
                break;
        }

        return $paymentConfig;
    }

    /**
     * 获取微信支付配置
     *
     * @param int $payType 支付方式 1:APP支付, 2:小程序支付, 3:H5支付, 4:公众号支付, 5:扫码支付
     *
     * @return array
     */
    public function getWxpayConfig($payType = 1)
    {
        $paymentConfig = [];
        switch ($payType) {
            case 2:
                $config = config('wechat.wxpay_mini_program_config');
                $paymentConfig['config'] = [
                    'miniapp_id' => $config['appid'],
                    'mch_id' => $config['mch_id'],
                    'key' => $config['key'],
                    'body' => $config['body'],
                    'notify_url' => $config['notify_url_v3_0_0'],
                    'log_path' => $config['log_path'],
                ];
                $paymentConfig['method'] = 'miniapp';
                break;
            case 4:
                $config = config('wechat.wxpay_mp_config');
                $paymentConfig['config'] = [
                    'app_id' => $config['app_id'],
                    'mch_id' => $config['mch_id'],
                    'body' => $config['body'],
                    'notify_url' => $config['notify_url_v3_0_0'],
                    'log_path' => $config['log_path'],
                    'key' => $config['key'],
                ];
                $paymentConfig['method'] = 'mp';
                break;
            default:
                $config = config('wechat.wxpay_app_config');
                $paymentConfig['config'] = [
                    'app_id' => $config['app_id'],
                    'mch_id' => $config['mch_id'],
                    'key' => $config['key'],
                    'body' => $config['body'],
                    'notify_url' => $config['notify_url_v3_0_0'],
                    'cert_client' => $config['sslcert'],
                    'cert_key' => $config['sslkey'],
                    'log_path' => $config['log_path'],
                ];
                $paymentConfig['method'] = 'app';
                break;
        }

        return $paymentConfig;
    }
}
