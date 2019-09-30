<?php

namespace app\common\utils\payment\kernel\gateways\wechat;

use app\common\utils\payment\kernel\supports\Collection;

class ScanGateway extends Gateway
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
        $payload['spbill_create_ip'] = Support::getClientIp();
        $payload['trade_type'] = $this->getTradeType();

        return $this->preOrder('pay/unifiedorder', $payload);
    }

    /**
     * Get trade type config.
     *
     * @return string
     */
    protected function getTradeType()
    {
        return 'NATIVE';
    }
}
