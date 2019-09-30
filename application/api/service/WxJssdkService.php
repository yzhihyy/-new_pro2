<?php
namespace app\api\service;

use app\api\Presenter;
use app\common\utils\curl\CurlHelper;
use app\common\utils\string\StringHelper;
use think\facade\Request;

class WxJssdkService extends Presenter
{
    /**
     * 获取 jssdk 所需的配置信息
     *
     * @param array $jsApiList
     * @param string|null $url
     * @param bool $debug
     * @param bool $json
     *
     * @return array|string
     */
    public function buildConfig(array $jsApiList, string $url = null, bool $debug = false, bool $json = false)
    {
        $config = array_merge(compact('debug', 'jsApiList'), $this->configSignature($url));

        return $json ? json_encode($config) : $config;
    }

    /**
     * 构建签名参数
     *
     * @param string|null $url
     * @param string|null $nonce
     * @param int|null $timestamp
     *
     * @return array
     */
    protected function configSignature(string $url = null, string $nonce = null, $timestamp = null)
    {
        $url = $url ?: Request::url(true);
        $nonce = $nonce ?: StringHelper::uniqueCode();
        $timestamp = $timestamp ?: time();

        return [
            'appId' => config('wechat.mp_appid'),
            'nonceStr' => $nonce,
            'timestamp' => $timestamp,
            'url' => $url,
            'signature' => $this->getTicketSignature($this->getTicket(), $nonce, $timestamp, $url),
        ];
    }

    /**
     * 签名
     *
     * @param string $ticket
     * @param string $nonce
     * @param int $timestamp
     * @param string $url
     *
     * @return string
     */
    public function getTicketSignature($ticket, $nonce, $timestamp, $url)
    {
        return sha1(sprintf('jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s', $ticket, $nonce, $timestamp, $url));
    }

    /**
     * 获取 jsapi_ticket
     *
     * @param bool $refresh
     *
     * @return string|void
     */
    public function getTicket(bool $refresh = false)
    {
        $cacheKey = 'wechat_mp_jssdk_jsapi_ticket';
        $ticket = getCustomCache($cacheKey);
        if (!$refresh && $ticket) {
            return $ticket;
        }

        $accessTokenLogic = new WxAccessTokenService();
        $accessToken = $accessTokenLogic->getToken($refresh);

        $url = config('wechat.mp_jssdk_getticket_url');
        $url = str_replace('access_token_str', $accessToken, $url);
        $curl = new CurlHelper();
        $result = $curl->curlRequest($url);
        $resultArray = json_decode($result, true);
        if (empty($resultArray)) {
            throw new \RuntimeException('WeChat not responding!');
        }
        if ($resultArray['errcode'] != 0) {
            throw new \LogicException('Request jsapi_ticket fail: ' . $result);

        }

        $ticket = $resultArray['ticket'];
        if (!setCustomCache($cacheKey, $ticket, $resultArray['expires_in'] - 500)) {
            throw new \RuntimeException('Failed to cache jsapi_ticket.');
        }

        return $ticket;
    }
}
