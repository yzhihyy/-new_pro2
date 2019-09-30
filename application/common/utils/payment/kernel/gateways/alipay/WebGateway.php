<?php

namespace app\common\utils\payment\kernel\gateways\alipay;

use app\common\utils\payment\kernel\contracts\GatewayInterface;
use app\common\utils\payment\kernel\supports\Config;

class WebGateway implements GatewayInterface
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
     * @return string
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

        if ('GET' == strtoupper($this->config->get('jump_method'))) {
            return $this->buildPayUrl($endpoint, $payload);
        }

        return $this->buildPayHtml($endpoint, $payload);
    }

    /**
     * Build Html response.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return string
     */
    protected function buildPayHtml($endpoint, $payload)
    {
        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='" . $endpoint . "' method='POST'>";
        foreach ($payload as $key => $val) {
            $val = str_replace("'", '&apos;', $val);
            $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }
        $sHtml .= "<input type='submit' value='ok' style='display:none;'></form>";
        $sHtml .= "<script>document.forms['alipaysubmit'].submit();</script>";

        return $sHtml;
    }

    /**
     * Build url response.
     *
     * @param string $endpoint
     * @param array $payload
     *
     * @return string
     */
    protected function buildPayUrl($endpoint, $payload)
    {
        ksort($payload);
        // 转换成目标字符集
        $payload = Support::encoding($payload, 'utf-8', $payload['charset']);
        $stringToBeSigned = '';
        foreach ($payload as $k => $v) {
            if ($v !== '' && !is_null($v) && '@' != substr($v, 0, 1)) {
                $stringToBeSigned .= $k . '=' . urlencode($v) . '&';
            }
        }
        $stringToBeSigned = trim($stringToBeSigned, '&');

        return $endpoint . '?' . $stringToBeSigned;
    }

    /**
     * Get method config.
     *
     * @return string
     */
    protected function getMethod()
    {
        return 'alipay.trade.page.pay';
    }

    /**
     * Get productCode config.
     *
     * @return string
     */
    protected function getProductCode()
    {
        return 'FAST_INSTANT_TRADE_PAY';
    }
}
