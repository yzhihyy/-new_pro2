<?php

namespace app\common\utils\payment\kernel\gateways\wechat;

use app\common\utils\payment\kernel\supports\Collection;

class PosGateway extends Gateway
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
        unset($payload['trade_type'], $payload['notify_url']);
        return $this->preOrder('pay/micropay', $payload);
    }

    /**
     * Get trade type config.
     *
     * @return string
     */
    protected function getTradeType()
    {
        return 'MICROPAY';
    }
}
