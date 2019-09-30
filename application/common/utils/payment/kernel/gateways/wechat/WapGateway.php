<?php

namespace app\common\utils\payment\kernel\gateways\wechat;

class WapGateway extends Gateway
{
    /**
     * Pay an order.
     *
     * @param string $endpoint
     * @param array  $payload
     *
     * @return string
     *
     * @throws \Exception
     */
    public function pay($endpoint, array $payload)
    {
        $payload['trade_type'] = $this->getTradeType();
        $data = $this->preOrder('pay/unifiedorder', $payload);
        $url = is_null($this->config->get('return_url')) ? $data->mweb_url : $data->mweb_url . '&redirect_url=' . urlencode($this->config->get('return_url'));

        return $url;
    }

    /**
     * Get trade type config.
     *
     * @return string
     */
    protected function getTradeType()
    {
        return 'MWEB';
    }
}
