<?php

namespace app\common\utils\payment\kernel\gateways\alipay;

use app\common\utils\payment\kernel\contracts\GatewayInterface;
use app\common\utils\payment\kernel\supports\Config;
use app\common\utils\payment\kernel\supports\Collection;

class TransferGateway implements GatewayInterface
{
    /**
     * Config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Bootstrap.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

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
        $payload['method'] = $this->getMethod();
        $payload['biz_content'] = json_encode(array_merge(
            json_decode($payload['biz_content'], true),
            ['product_code' => $this->getProductCode()]
        ));
        $payload['sign'] = Support::generateSign($payload, $this->config->get('rsa_private_key'));

        return Support::requestApi($payload, $this->config->get('alipay_public_key'));
    }

    /**
     * Get method config.
     *
     * @return string
     */
    protected function getMethod()
    {
        return 'alipay.fund.trans.toaccount.transfer';
    }

    /**
     * Get productCode config.
     *
     * @return string
     */
    protected function getProductCode()
    {
        return '';
    }
}
