<?php

namespace app\common\utils\payment;

use app\common\utils\payment\kernel\contracts\GatewayApplicationInterface;
use app\common\utils\payment\kernel\exceptions\InvalidGatewayException;
use app\common\utils\payment\kernel\supports\Config;
use app\common\utils\payment\kernel\supports\Str;

/**
 * @method static Alipay alipay(array $config) 支付宝
 * @method static Wechat wechat(array $config) 微信
 */
class Payment
{
    /**
     * Config
     *
     * @var Config
     */
    protected $config;

    /**
     * Bootstrap
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    /**
     * Create a instance
     *
     * @param string $name
     *
     * @return GatewayApplicationInterface
     *
     * @throws \Exception|InvalidGatewayException
     */
    protected function create($name)
    {
        $gateway = __NAMESPACE__ . '\\kernel\\gateways\\' . Str::studly($name);
        if (class_exists($gateway)) {
            $app = new $gateway($this->config);
            if ($app instanceof GatewayApplicationInterface) {
                return $app;
            }

            throw new InvalidGatewayException("Gateway [$gateway] Must Be An Instance Of GatewayApplicationInterface");
        }

        throw new InvalidGatewayException("Gateway [{$name}] Not Exists");
    }

    /**
     * Magic call static
     *
     * @param string $name
     * @param array $arguments
     *
     * @return GatewayApplicationInterface
     *
     * @throws InvalidGatewayException
     */
    public static function __callStatic($name, $arguments)
    {
        $class = new self(...$arguments);
        return $class->create($name);
    }
}
