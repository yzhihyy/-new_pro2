<?php

namespace app\api\controller\v2_0_0\user;

use app\api\Presenter;

use app\api\logic\v2_0_0\user\OauthLogic;
use app\api\validate\v2_0_0\OauthValidate;
use app\common\utils\curl\CurlHelper;
use app\api\model\v2_0_0\UserModel;

class Oauth extends Presenter
{
    /**
     * 获取微信授权API
     */
    public function getWxAuth()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(OauthValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('getWxAuth')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 微信授权code
            $code = $paramsArray['wx_code'];
            // 通过code换取网页授权access_token
            // 使用curl请求微信授权access_token、refresh_token、openid、unionid
            $curl = new CurlHelper();
            $oauthLogic = new OauthLogic();
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
            $userModel = model(UserModel::class);
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

    /**
     * 获取小程序授权
     *
     * @return \think\response\Json
     */
    public function miniProgramGetInfoByCode()
    {
        try {
            $jsCode = input('js_code/s', '');
            if (!$jsCode) {
                return apiError('js_code不可为空');
            }
            // 通过code换取信息
            $curl = new CurlHelper();
            $oauthLogic = new OauthLogic();
            $url = $oauthLogic->getJsCode2SessionUrl($jsCode);
            $result = $curl->curlRequest($url);
            $resultArray = json_decode($result, true);
            if (empty($resultArray)) {
                throw new \Exception('获取access_token时未返回任何信息');
            }
            // 出现错误
            if (isset($resultArray['errcode']) && $resultArray['errcode'] !== 0) {
                throw new \Exception('小程序根据code换取信息出错：' . $result);
            }
            // 根据unionid判断该该微信是否绑定过手机
            $unionid = $resultArray['unionid'];
            $userModel = model(UserModel::class);
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
                'openid' => $resultArray['openid'],
                'unionid' => $resultArray['unionid'],
                'has_bind_phone' => $user['phone'] ? 1 : 0,
                'token' => $token
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            generateApiLog('小程序根据code换取信息接口异常：' . $e->getMessage());
        }
        return apiError();
    }
}
