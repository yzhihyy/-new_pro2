<?php

namespace app\common\utils\payment\kernel\gateways\wechat;

use app\common\utils\payment\kernel\gateways\Wechat;
use app\common\utils\payment\kernel\supports\Collection;

class GroupRedpackGateway extends Gateway
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
        $payload['wxappid'] = $payload['appid'];
        $payload['amt_type'] = 'ALL_RAND';
        $this->mode !== Wechat::MODE_SERVICE ?: $payload['msgappid'] = $payload['appid'];
        unset($payload['appid'], $payload['trade_type'], $payload['notify_url'], $payload['spbill_create_ip']);
        $payload['sign'] = Support::generateSign($payload, $this->config->get('key'));

        return Support::requestApi(
            'mmpaymkttransfers/sendgroupredpack',
            $payload,
            $this->config->get('key'),
            ['cert' => $this->config->get('cert_client'), 'sslkey' => $this->config->get('cert_key')]
        );
    }

    /**
     * Get trade type config.
     *
     * @return string
     */
    protected function getTradeType()
    {
        return '';
    }
}
