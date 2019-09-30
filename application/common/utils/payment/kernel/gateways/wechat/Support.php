<?php

namespace app\common\utils\payment\kernel\gateways\wechat;

use app\common\utils\payment\kernel\gateways\Wechat;
use app\common\utils\payment\kernel\exceptions\InvalidArgumentException;
use app\common\utils\payment\kernel\exceptions\GatewayException;
use app\common\utils\payment\kernel\exceptions\InvalidSignException;
use app\common\utils\payment\kernel\supports\Collection;
use app\common\utils\payment\kernel\supports\Config;
use app\common\utils\payment\kernel\supports\Traits\HttpRequest;

class Support
{
    use HttpRequest;

    /**
     * Instance.
     *
     * @var Support
     */
    private static $instance;

    /**
     * Wechat gateway.
     *
     * @var string
     */
    protected $baseUri = 'https://api.mch.weixin.qq.com/';

    /**
     * Get instance.
     *
     * @return Support
     */
    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Request wechat api.
     *
     * @param string      $endpoint
     * @param array       $data
     * @param string|null $key
     * @param array       $cert
     *
     * @return Collection
     *
     * @throws \Exception
     */
    public static function requestApi($endpoint, $data, $key = null, $cert = [])
    {
        $result = self::getInstance()->post($endpoint, self::toXml($data), [], $cert);
        $result = is_array($result) ? $result : self::fromXml($result);
        if (!isset($result['return_code']) || $result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS') {
            throw new GatewayException(
                'Get Wechat API Error:' . $result['return_msg'] . (isset($result['err_code_des']) ? $result['err_code_des'] : ''),
                $result,
                20000
            );
        }

        if (strpos($endpoint, 'mmpaymkttransfers') !== false || self::generateSign($result, $key) === $result['sign']) {
            return new Collection($result);
        }

        throw new InvalidSignException('Wechat Sign Verify FAILED', $result);
    }

    /**
     * Filter payload.
     *
     * @param array $payload
     * @param array|string $order
     * @param Config $config
     * @param bool $preserveNotifyUrl
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public static function filterPayload($payload, $order, $config, $preserveNotifyUrl = false)
    {
        $payload = array_merge($payload, is_array($order) ? $order : ['out_trade_no' => $order]);
        $type = isset($order['type']) ? $order['type'] . ($order['type'] == 'app' ? '' : '_') . 'id' : 'app_id';
        $payload['appid'] = $config->get($type, '');
        $mode = $config->get('mode', Wechat::MODE_NORMAL);
        if ($mode === Wechat::MODE_SERVICE) {
            $payload['sub_appid'] = $config->get('sub_' . $type, '');
        }

        unset($payload['trade_type'], $payload['type']);
        if (!$preserveNotifyUrl) {
            unset($payload['notify_url']);
        }

        $payload['sign'] = self::generateSign($payload, $config->get('key'));

        return $payload;
    }

    /**
     * Generate wechat sign.
     *
     * @param array $data
     * @param string $key
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public static function generateSign($data, $key = null)
    {
        if (is_null($key)) {
            throw new InvalidArgumentException('Missing Wechat Config -- [key]');
        }

        ksort($data);
        $string = md5(self::getSignContent($data) . '&key=' . $key);

        return strtoupper($string);
    }

    /**
     * Generate sign content.
     *
     * @param array $data
     *
     * @return string
     */
    public static function getSignContent($data)
    {
        $buff = '';
        foreach ($data as $k => $v) {
            $buff .= ($k != 'sign' && $v != '' && !is_array($v)) ? $k . '=' . $v . '&' : '';
        }

        return trim($buff, '&');
    }

    /**
     * Convert array to xml.
     *
     * @param array $data
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public static function toXml($data)
    {
        if (!is_array($data) || count($data) <= 0) {
            throw new InvalidArgumentException('Convert To Xml Error! Invalid Array!');
        }

        $xml = '<xml>';
        foreach ($data as $key => $val) {
            $xml .= is_numeric($val) ? '<' . $key . '>' . $val . '</' . $key . '>' : '<' . $key . '><![CDATA[' . $val . ']]></' . $key . '>';
        }
        $xml .= '</xml>';

        return $xml;
    }

    /**
     * Convert xml to array.
     *
     * @param string $xml
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public static function fromXml($xml)
    {
        if (!$xml) {
            throw new InvalidArgumentException('Convert To Array Error! Invalid Xml!');
        }

        libxml_disable_entity_loader(true);

        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * Get client ip.
     *
     * @return string
     */
    public static function getClientIp()
    {
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            // for php-cli(phpunit etc.)
            $ip = defined('PHPUNIT_RUNNING') ? '127.0.0.1' : gethostbyname(gethostname());
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ?: '127.0.0.1';
    }

    /**
     * Wechat gateway.
     *
     * @param string $mode
     *
     * @return string
     */
    public static function baseUri($mode = null)
    {
        switch ($mode) {
            case Wechat::MODE_DEV:
                self::getInstance()->baseUri = 'https://api.mch.weixin.qq.com/sandboxnew/';
                break;
            case Wechat::MODE_HK:
                self::getInstance()->baseUri = 'https://apihk.mch.weixin.qq.com/';
                break;
            default:
                break;
        }

        return self::getInstance()->baseUri;
    }
}
