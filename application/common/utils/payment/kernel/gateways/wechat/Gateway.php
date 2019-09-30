<?php

namespace app\common\utils\payment\kernel\gateways\wechat;

use app\common\utils\payment\kernel\contracts\GatewayInterface;
use app\common\utils\payment\kernel\gateways\Wechat;
use app\common\utils\payment\kernel\supports\Config;
use app\common\utils\payment\kernel\supports\Collection;

abstract class Gateway implements GatewayInterface
{
    /**
     * Config
     *
     * @var Config
     */
    protected $config;

    /**
     * Mode.
     *
     * @var string
     */
    protected $mode;

    /**
     * Bootstrap
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->mode = $this->config->get('mode', Wechat::MODE_NORMAL);
    }

    /**
     * Pay an order
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return Collection|string
     */
    abstract public function pay($endpoint, array $payload);

    /**
     * Get trade type config
     *
     * @return string
     */
    abstract protected function getTradeType();

    /**
     * Preorder an order.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return Collection
     *
     * @throws \Exception
     */
    protected function preOrder($endpoint, $payload)
    {
        $payload['sign'] = Support::generateSign($payload, $this->config->get('key'));

        return Support::requestApi($endpoint, $payload, $this->config->get('key'));
    }
}
