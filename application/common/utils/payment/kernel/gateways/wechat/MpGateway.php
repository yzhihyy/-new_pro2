<?php

namespace app\common\utils\payment\kernel\gateways\wechat;

use app\common\utils\payment\kernel\supports\Collection;
use app\common\utils\payment\kernel\supports\Str;

class MpGateway extends Gateway
{
    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return Collection
     *
     * @throws \Exception
     */
    public function pay($endpoint, array $payload)
    {
        $payload['trade_type'] = $this->getTradeType();
        $payRequest = [
            'appId' => $payload['appid'],
            'timeStamp' => strval(time()),
            'nonceStr' => Str::random(),
            'package' => 'prepay_id=' . $this->preOrder('pay/unifiedorder', $payload)->prepay_id,
            'signType' => 'MD5',
        ];
        $payRequest['paySign'] = Support::generateSign($payRequest, $this->config->get('key'));

        return new Collection($payRequest);
    }

    /**
     * Get trade type config.
     *
     * @return string
     */
    protected function getTradeType()
    {
        return 'JSAPI';
    }
}
