<?php

namespace app\common\utils\payment\kernel\contracts;

use app\common\utils\payment\kernel\supports\Collection;

interface GatewayInterface
{
    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return string|Collection
     */
    public function pay($endpoint, array $payload);
}
