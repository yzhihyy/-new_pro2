<?php

namespace app\common\utils\payment\kernel\gateways;

use app\common\utils\payment\kernel\contracts\GatewayApplicationInterface;
use app\common\utils\payment\kernel\contracts\GatewayInterface;
use app\common\utils\payment\kernel\exceptions\InvalidGatewayException;
use app\common\utils\payment\kernel\exceptions\InvalidConfigException;
use app\common\utils\payment\kernel\exceptions\InvalidSignException;
use app\common\utils\payment\kernel\gateways\alipay\Support;
use app\common\utils\payment\kernel\supports\Config;
use app\common\utils\payment\kernel\supports\Collection;
use app\common\utils\payment\kernel\supports\Str;
use think\Response;

/**
 * @method \app\common\utils\payment\kernel\gateways\alipay\AppGateway app(array $config) APP 支付
 * @method \app\common\utils\payment\kernel\gateways\alipay\PosGateway pos(array $config) 刷卡支付
 * @method \app\common\utils\payment\kernel\gateways\alipay\ScanGateway scan(array $config) 扫码支付
 * @method \app\common\utils\payment\kernel\gateways\alipay\TransferGateway transfer(array $config) 帐户转账
 * @method \app\common\utils\payment\kernel\gateways\alipay\WapGateway wap(array $config) 手机网站支付
 * @method \app\common\utils\payment\kernel\gateways\alipay\WebGateway web(array $config) 电脑支付
 */
class Alipay implements GatewayApplicationInterface
{
    /**
     * Config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Alipay payload.
     *
     * @var array
     */
    protected $payload;

    /**
     * Alipay gateway.
     *
     * @var string
     */
    protected $gateway;

    /**
     * Bootstrap.
     *
     * @param Config $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->gateway = Support::baseUri($this->config->get('mode', 'normal'));
        $this->payload = [
            'app_id' => $this->config->get('app_id'),
            'method' => '',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'version' => '1.0',
            'return_url' => $this->config->get('return_url'),
            'notify_url' => $this->config->get('notify_url'),
            'timestamp' => date('Y-m-d H:i:s'),
            'biz_content' => '',
            'sign' => '',
        ];
    }

    /**
     * Pay an order.
     *
     * @param string $gateway
     * @param array $params
     *
     * @return string|Collection
     *
     * @throws InvalidGatewayException
     */
    public function pay($gateway, $params = [])
    {
        $this->payload['biz_content'] = json_encode($params);
        $gateway = Str::lower(get_class($this)) . '\\' . Str::studly($gateway) . 'Gateway';
        if (class_exists($gateway)) {
            $app = new $gateway($this->config);
            if ($app instanceof GatewayInterface) {
                return $app->pay($this->gateway, $this->payload);
            }

            throw new InvalidGatewayException("Pay Gateway [{$gateway}] Must Be An Instance Of GatewayInterface");
        }

        throw new InvalidGatewayException("Pay Gateway [{$gateway}] not exists");
    }

    /**
     * Verfiy sign.
     *
     * @return Collection
     * @throws InvalidConfigException
     * @throws InvalidSignException
     */
    public function verify()
    {
        $data = !empty($_POST) ? $_POST : $_GET;
        $data = Support::encoding($data, 'utf-8', isset($data['charset']) ? $data['charset'] : 'gb2312');

        if (Support::verifySign($data, $this->config->get('alipay_public_key'))) {
            return new Collection($data);
        }

        throw new InvalidSignException('Alipay Sign Verify FAILED', $data);
    }

    /**
     * Query an order.
     *
     * @param string|array $order
     *
     * @return collection
     *
     * @throws \Exception
     */
    public function find($order)
    {
        $this->payload['method'] = 'alipay.trade.query';
        $this->payload['biz_content'] = json_encode(is_array($order) ? $order : ['out_trade_no' => $order]);
        $this->payload['sign'] = Support::generateSign($this->payload, $this->config->get('rsa_private_key'));

        return Support::requestApi($this->payload, $this->config->get('alipay_public_key'));
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
        $this->payload['method'] = 'alipay.trade.refund';
        $this->payload['biz_content'] = json_encode($order);
        $this->payload['sign'] = Support::generateSign($this->payload, $this->config->get('rsa_private_key'));

        return Support::requestApi($this->payload, $this->config->get('alipay_public_key'));
    }

    /**
     * Cancel an order.
     *
     * @param string|array $order
     *
     * @return Collection
     *
     * @throws \Exception
     */
    public function cancel($order)
    {
        $this->payload['method'] = 'alipay.trade.cancel';
        $this->payload['biz_content'] = json_encode(is_array($order) ? $order : ['out_trade_no' => $order]);
        $this->payload['sign'] = Support::generateSign($this->payload, $this->config->get('rsa_private_key'));

        return Support::requestApi($this->payload, $this->config->get('alipay_public_key'));
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
        $this->payload['method'] = 'alipay.trade.close';
        $this->payload['biz_content'] = json_encode(is_array($order) ? $order : ['out_trade_no' => $order]);
        $this->payload['sign'] = Support::generateSign($this->payload, $this->config->get('rsa_private_key'));

        return Support::requestApi($this->payload, $this->config->get('alipay_public_key'));
    }

    /**
     * Download bill.
     *
     * @param string|array $bill
     *
     * @return Collection
     *
     * @throws \Exception
     */
    public function download($bill)
    {
        $this->payload['method'] = 'alipay.data.dataservice.bill.downloadurl.query';
        $this->payload['biz_content'] = json_encode(is_array($bill) ? $bill : ['bill_type' => 'trade', 'bill_date' => $bill]);
        $this->payload['sign'] = Support::generateSign($this->payload, $this->config->get('rsa_private_key'));

        $result = Support::requestApi($this->payload, $this->config->get('alipay_public_key'));

        return ($result instanceof Collection) ? $result->bill_download_url : '';
    }

    /**
     * Reply success to alipay
     *
     * @return Response
     */
    public function success()
    {
        return Response::create('success');
    }

    /**
     * Reply fail to alipay
     *
     * @return Response
     */
    public function fail()
    {
        return Response::create('fail');
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
