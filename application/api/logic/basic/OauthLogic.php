<?php

namespace app\api\logic\basic;

use app\api\Presenter;

class OauthLogic extends Presenter
{
    /**
     * 获取微信授权url
     *
     * @param array $params
     * @param string $state
     *
     * @return string
     */
    public function getWxAuthUrl($params = [], $state = '')
    {
        // 微信公众号授权url
        $signalUrl = config('wechat.mp_signal_url');
        // 回调url
        $redirectUrl = url('api.wxAuth', $params);
        // 生成授权url
        $authUrl = str_replace(['mp_appid_str', 'redirect_uri_str'], [config('wechat.mp_appid'), urlencode($redirectUrl)], $signalUrl);
        if (!empty($state)) {
            $authUrl = str_replace('state_code', $state, $authUrl);
        }

        return $authUrl;
    }

    /**
     * 获取微信授权access_token url
     *
     * @param string $code
     *
     * @return mixed
     */
    public function getWxAccessTokenUrl($code)
    {
        $url = config('wechat.mp_oauth_access_token_url');
        $url = str_replace(
            ['mp_appid_str', 'mp_app_secret_str', 'code_str'],
            [config('wechat.mp_appid'), config('wechat.mp_app_secret'), $code],
            $url
        );

        return $url;
    }

    /**
     * 获取用户信息url
     *
     * @param string $accessToken
     * @param string $openId
     *
     * @return string
     */
    public function getWxUserInfoUrl($accessToken, $openId)
    {
        $url = config('wechat.mp_get_userinfo_url');
        $url = str_replace(['access_token_str', 'openid_str'], [$accessToken, $openId], $url);

        return $url;
    }
}
