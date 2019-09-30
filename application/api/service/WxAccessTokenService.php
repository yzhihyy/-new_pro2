<?php

namespace app\api\service;

use app\common\utils\curl\CurlHelper;
use app\api\Presenter;

class WxAccessTokenService extends Presenter
{
    /**
     * 刷新 access_token
     *
     * @return string|void
     */
    public function getRefreshedToken()
    {
        return $this->getToken(true);
    }

    /**
     * 获取 access_token
     *
     * @param bool $refresh
     *
     * @return string|void
     */
    public function getToken(bool $refresh = false)
    {
        $cacheKey = 'wechat_mp_access_token';
        $token = getCustomCache($cacheKey);
        if (!$refresh && $token) {
            return $token;
        }

        $url = config('wechat.mp_access_token_url');
        $url = str_replace(
            ['mp_appid_str', 'mp_app_secret_str'],
            [config('wechat.mp_appid'), config('wechat.mp_app_secret')],
            $url
        );
        $curl = new CurlHelper();
        $result = $curl->curlRequest($url);
        $resultArray = json_decode($result, true);
        if (empty($resultArray)) {
            throw new \RuntimeException('WeChat not responding!');
        }
        if (isset($resultArray['errcode'])) {
            throw new \LogicException('Request access_token fail: ' . $result);
        }

        $token = $resultArray['access_token'];
        if (!setCustomCache($cacheKey, $token, $resultArray['expires_in'] ?? 7200)) {
            throw new \RuntimeException('Failed to cache access_token.');
        }

        return $token;
    }
}
