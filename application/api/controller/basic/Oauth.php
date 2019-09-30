<?php

namespace app\api\controller\basic;

use app\api\Presenter;
use app\api\logic\basic\OauthLogic;
use app\common\utils\curl\CurlHelper;

class Oauth extends Presenter
{
    public function wxAuth()
    {
        try {
            // 微信授权code
            $code = input('code');
            $oauthLogic = new OauthLogic();
            // 微信授权code空的情况跳转到微信授权画面
            if (empty($code)) {
                // 微信授权url
                $redirectUrl = $oauthLogic->getWxAuthUrl();
                return redirect($redirectUrl);
            }
            // 通过code换取网页授权access_token
            // 使用curl请求微信授权access_token、refresh_token和openid
            $curl = new CurlHelper();
            $accessTokenUrl = $oauthLogic->getWxAccessTokenUrl($code);
            $result = $curl->curlRequest($accessTokenUrl);
            $resultArray = json_decode($result, true);
            if (empty($resultArray)) {
                throw new \Exception('获取access_token时未返回任何信息');
            }
            // 出现错误
            if (isset($resultArray['errcode'])) {
                throw new \Exception('获取access_token出错：' . $result);
            }

            // 设置openid、access_token、refresh_token到session中
            session('wechat_openid', $resultArray['openid']);
            session('wechat_access_token', $resultArray['access_token']);
            session('wechat_refresh_token', $resultArray['refresh_token']);
            // 访问的上一个页面
            $referer = session('oauth_referer');
            // 跳转回自己服务器的链接
            if (!empty($referer)) {
                return redirect($referer);
            }
        } catch (\Exception $e) {
            generateApiLog('微信授权异常信息：' . $e->getMessage());
        }

        return view('error.twig');
    }

    /**
     * 获取微信授权API
     */
    public function getWxAuth()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate('api/Oauth');
            // 验证请求参数
            $checkResult = $validate->scene('getWxAuth')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 微信授权code
            $code = $paramsArray['wx_code'];
            $oauthLogic = new OauthLogic();
            // 通过code换取网页授权access_token
            // 使用curl请求微信授权access_token、refresh_token和openid
            $curl = new CurlHelper();
            $accessTokenUrl = $oauthLogic->getWxAccessTokenUrl($code);
            $result = $curl->curlRequest($accessTokenUrl);
            $resultArray = json_decode($result, true);
            if (empty($resultArray)) {
                throw new \Exception('获取access_token时未返回任何信息');
            }
            // 出现错误
            if (isset($resultArray['errcode'])) {
                throw new \Exception('获取access_token出错：' . $result);
            }
            // 根据unionid判断该该微信是否绑定过手机
            $unionid = $resultArray['unionid'];
            $userModel = model('api/User');
            $user = $userModel->where(['wechat_unionid' => $unionid])->find();
            $token = '';
            if ($user) {
                $tokenData = [
                    'userId' => $user['id']
                ];
                $token = jwtEncode($tokenData);
            }
            // 返回数据
            $responseData = [
                'wechat_mp_openid' => $resultArray['openid'],
                'unionid' => $resultArray['unionid'],
                'has_bind_phone' => $user['phone'] ? 1 : 0,
                'token' => $token
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            generateApiLog('获取微信授权接口异常：' . $e->getMessage());
        }
        return apiError();
    }
}
