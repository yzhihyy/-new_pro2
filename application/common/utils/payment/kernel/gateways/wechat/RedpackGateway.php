<?php

namespace app\common\utils\payment\kernel\gateways\wechat;

use app\common\utils\payment\kernel\gateways\Wechat;
use app\common\utils\payment\kernel\supports\Collection;

class RedpackGateway extends Gateway
{
    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array  $payload
     *
     * @return Collection
     *
     * @throws \Exception
     */
    public function pay($endpoint, array $payload)
    {
        $payload['wxappid'] = $payload['appid'];
        php_sapi_name() === 'cli' ?: $payload['client_ip'] = Support::getClientIp();
        $this->mode !== Wechat::MODE_SERVICE ?: $payload['msgappid'] = $payload['appid'];
        unset($payload['appid'], $payload['trade_type'], $payload['notify_url'], $payload['spbill_create_ip']);
        $payload['sign'] = Support::generateSign($payload, $this->config->get('key'));

        return Support::requestApi(
            'mmpaymkttransfers/sendredpack',
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
