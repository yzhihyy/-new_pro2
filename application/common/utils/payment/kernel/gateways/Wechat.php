<?php

namespace app\common\utils\payment\kernel\gateways;

use app\common\utils\payment\kernel\contracts\GatewayApplicationInterface;
use app\common\utils\payment\kernel\contracts\GatewayInterface;
use app\common\utils\payment\kernel\exceptions\GatewayException;
use app\common\utils\payment\kernel\exceptions\InvalidArgumentException;
use app\common\utils\payment\kernel\exceptions\InvalidGatewayException;
use app\common\utils\payment\kernel\exceptions\InvalidSignException;
use app\common\utils\payment\kernel\gateways\wechat\Support;
use app\common\utils\payment\kernel\supports\Config;
use app\common\utils\payment\kernel\supports\Collection;
use app\common\utils\payment\kernel\supports\Str;
use think\Response;

/**
 * @method \app\common\utils\payment\kernel\gateways\wechat\AppGateway app(array $config) APP 支付
 * @method \app\common\utils\payment\kernel\gateways\wechat\GroupRedpackGateway groupRedpack(array $config) 分裂红包
 * @method \app\common\utils\payment\kernel\gateways\wechat\MiniappGateway miniapp(array $config) 小程序支付
 * @method \app\common\utils\payment\kernel\gateways\wechat\MpGateway mp(array $config) 公众号支付
 * @method \app\common\utils\payment\kernel\gateways\wechat\PosGateway pos(array $config) 刷卡支付
 * @method \app\common\utils\payment\kernel\gateways\wechat\RedpackGateway redpack(array $config) 普通红包
 * @method \app\common\utils\payment\kernel\gateways\wechat\ScanGateway scan(array $config) 扫码支付
 * @method \app\common\utils\payment\kernel\gateways\wechat\TransferGateway transfer(array $config) 企业付款
 * @method \app\common\utils\payment\kernel\gateways\wechat\WapGateway wap(array $config) H5 支付
 */
class Wechat implements GatewayApplicationInterface
{
    const MODE_NORMAL = 'normal';   // 普通模式
    const MODE_DEV = 'dev';         // 沙箱模式
    const MODE_HK = 'hk';           // 香港钱包
    const MODE_SERVICE = 'service'; // 服务商

    /**
     * Config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Mode
     *
     * @var string
     */
    protected $mode;

    /**
     * Wechat payload.
     *
     * @var array
     */
    protected $payload;

    /**
     * Wechat gateway.
     *
     * @var string
     */
    protected $gateway;

    /**
     * Bootstrap.
     *
     * @param Config $config
     *
     * @throws \Exception
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->mode = $this->config->get('mode', self::MODE_NORMAL);
        $this->gateway = Support::baseUri($this->mode);
        $this->payload = [
            'appid' => $this->config->get('app_id', ''),
            'mch_id' => $this->config->get('mch_id', ''),
            'nonce_str' => Str::random(),
            'notify_url' => $this->config->get('notify_url', ''),
            'sign' => '',
            'trade_type' => '',
            'spbill_create_ip' => Support::getClientIp(),
        ];

        if ($this->mode === static::MODE_SERVICE) {
            $this->payload = array_merge($this->payload, [
                'sub_mch_id' => $this->config->get('sub_mch_id'),
                'sub_appid' => $this->config->get('sub_app_id', ''),
            ]);
        }
    }

    /**
     * Pay an order.
     *
     * @param string $gateway
     * @param array $params
     *
     * @return Response|Collection
     *
     * @throws InvalidGatewayException
     */
    public function pay($gateway, $params = [])
    {
        $this->payload = array_merge($this->payload, $params);
        $gateway = Str::lower(get_class($this)) . '\\' . Str::studly($gateway) . 'Gateway';
        if (class_exists($gateway)) {
            $app = new $gateway($this->config);
            if ($app instanceof GatewayInterface) {
                return $app->pay($this->gateway, $this->payload);
            }

            throw new InvalidGatewayException("Pay Gateway [{$gateway}] Must Be An Instance Of GatewayInterface");
        }

        throw new InvalidGatewayException("Pay Gateway [{$gateway}] Not Exists");
    }

    /**
     * Verify data.
     *
     * @param string|null $content
     *
     * @return Collection
     *
     * @throws InvalidSignException|\Exception
     */
    public function verify($content = null)
    {
        $content = $content ?: file_get_contents('php://input');
        $data = Support::fromXml($content);

        if (Support::generateSign($data, $this->config->get('key')) === $data['sign']) {
            return new Collection($data);
        }

        throw new InvalidSignException('Wechat Sign Verify FAILED', $data);
    }

    /**
     * Query an order.
     *
     * @param string|array $order
     *
     * @return Collection
     *
     * @throws \Exception
     */
    public function find($order)
    {
        $this->payload = Support::filterPayload($this->payload, $order, $this->config);
        return Support::requestApi('pay/orderquery', $this->payload, $this->config->get('key'));
    }

    /**
     * Refund an order.
     *
     * @param array $order
     *
     * @return Collection
     *
     * @throws \Exception
     */
    public function refund($order)
    {
        $this->payload = Support::filterPayload($this->payload, $order, $this->config, true);
        return Support::requestApi(
            'secapi/pay/refund',
            $this->payload,
            $this->config->get('key'),
            ['cert' => $this->config->get('cert_client'), 'sslkey' => $this->config->get('cert_key')]
        );
    }

    /**
     * Cancel an order.
     *
     * @param string|array $order
     *
     * @throws GatewayException
     */
    public function cancel($order)
    {
        throw new GatewayException('Wechat Do Not Have Cancel API! please use Close API!');
    }

    /**
     * Close an order.
     *
     * @param string|array $order
     *
     * @return Collection
     *
     * @throws \Exception
     */
    public function close($order)
    {
        unset($this->payload['spbill_create_ip']);
        $this->payload = Support::filterPayload($this->payload, $order, $this->config);

        return Support::requestApi('pay/closeorder', $this->payload, $this->config->get('key'));
    }

    /**
     * Echo success to server.
     *
     * @return Response
     *
     * @throws InvalidArgumentException
     */
    public function success()
    {
        return Response::create(
            Support::toXml(['return_code' => 'SUCCESS']),
            'Content-Type:application/xml',
            200
        );
    }

    /**
     * Echo fail to server.
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function fail()
    {
        return Response::create(
            Support::toXml(['return_code' => 'FAIL']),
            'Content-Type:application/xml',
            200
        );
    }

    /**
     * Magic pay.
     *
     * @param string $method
     * @param array $params
     *
     * @return string|Collection
     *
     * @throws \Exception
     */
    public function __call($method, $params)
    {
        return $this->pay($method, ...$params);
    }
}
