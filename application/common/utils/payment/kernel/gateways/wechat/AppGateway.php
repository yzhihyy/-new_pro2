<?php

namespace app\common\utils\payment\kernel\gateways\wechat;

use app\common\utils\payment\kernel\gateways\Wechat;
use app\common\utils\payment\kernel\supports\Str;

class AppGateway extends Gateway
{
    /**
     * Pay an order
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return string
     *
     * @throws \Exception
     */
    public function pay($endpoint, array $payload)
    {
        $payload['trade_type'] = $this->getTradeType();
        $this->mode !== Wechat::MODE_SERVICE ?: $payload['sub_appid'] = $this->config->get('sub_appid');
        $payRequest = [
            'appid' => $this->mode === Wechat::MODE_SERVICE ? $payload['sub_appid'] : $payload['appid'],
            'partnerid' => $this->mode === Wechat::MODE_SERVICE ? $payload['sub_mch_id'] : $payload['mch_id'],
            'prepayid' => $this->preOrder('pay/unifiedorder', $payload)->prepay_id,
            'timestamp' => strval(time()),
            'noncestr' => Str::random(),
            'package' => 'Sign=WXPay',
        ];
        $payRequest['sign'] = Support::generateSign($payRequest, $this->config->get('key'));

        return json_encode($payRequest);
    }

    /**
     * Get trade type config
     *
     * @return string
     */
    protected function getTradeType()
    {
        return 'APP';
    }
}
