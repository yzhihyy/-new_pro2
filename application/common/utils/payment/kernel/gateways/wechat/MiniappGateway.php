<?php

namespace app\common\utils\payment\kernel\gateways\wechat;

use app\common\utils\payment\kernel\gateways\Wechat;
use app\common\utils\payment\kernel\supports\Collection;

class MiniappGateway extends MpGateway
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
        $payload['appid'] = $this->config->get('miniapp_id');
        $this->mode !== Wechat::MODE_SERVICE ?: $payload['sub_appid'] = $this->config->get('sub_miniapp_id');

        return parent::pay($endpoint, $payload);
    }
}
