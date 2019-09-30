<?php

namespace app\common\utils\payment\kernel\gateways\wechat;

use app\common\utils\payment\kernel\gateways\Wechat;
use app\common\utils\payment\kernel\supports\Collection;

class TransferGateway extends Gateway
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
        if ($this->mode === Wechat::MODE_SERVICE) {
            unset($payload['sub_mch_id'], $payload['sub_appid']);
        }

        $type = isset($payload['type']) ? ($payload['type'] . ($payload['type'] == 'app' ?: '_') . 'id') : 'app_id';
        $payload['mch_appid'] = $this->config->get($type, '');
        $payload['mchid'] = $payload['mch_id'];
        php_sapi_name() === 'cli' ?: $payload['spbill_create_ip'] = Support::getClientIp();
        unset($payload['appid'], $payload['mch_id'], $payload['trade_type'], $payload['notify_url'], $payload['type']);
        $payload['sign'] = Support::generateSign($payload, $this->config->get('key'));

        return Support::requestApi(
            'mmpaymkttransfers/promotion/transfers',
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
