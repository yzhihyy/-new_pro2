<?php

namespace app\api\controller\center;

use app\api\Presenter;
use app\common\utils\string\StringHelper;
use think\facade\Request;

class Auth extends Presenter
{
    /**
     * 登录
     *
     * @return \think\response\Json
     */
    public function login()
    {
        /**
         * @var \app\api\model\Captcha $captchaModel
         * @var \app\api\model\User $userModel
         */
        if ($this->request->isPost()) {
            try {
                // 校验参数
                $paramsArray = input();
                $validate = validate('api/Auth');
                $checkResult = $validate->scene('login')->check($paramsArray);
                if (!$checkResult) {
                    $errorMsg = $validate->getError();
                    return apiError($errorMsg);
                }
                // 校验验证码是否正确
                $captchaModel = model('api/Captcha');
                $result = $captchaModel->checkLoginCode([
                    'phone' => $paramsArray['phone'],
                    'code' => $paramsArray['code']
                ]);
                if (empty($result)) {
                    return apiError(config('response.msg6'));
                }
                // 判断用户是否已存在，如果不存在，则生成新用户
                $userModel = model('api/User');
                $user = $userModel->getUserByPhone($paramsArray['phone']);
                if (empty($user)) {
                    $timeStr = date('Y-m-d H:i:s');
                    $createData = [
                        'phone' => $paramsArray['phone'],
                        'nickname' => StringHelper::hidePartOfString($paramsArray['phone']),
                        'avatar' => config('parameters.new_register_user_avatar'),
                        'thumb_avatar' => config('parameters.new_register_user_avatar'),
                        'login_time' => $timeStr,
                        'account_status' => 1,
                        'generate_time' => $timeStr
                    ];
                    $user = $userModel->create($createData);
                }
                // 判断是否为商家
                $business = $userModel->getBusinessByPhone($paramsArray['phone']);
                $isBusiness = 0;
                if ($business && $business['accountStatus'] == 1 && $business['onlineStatus'] == 1) {
                    $isBusiness = 1;
                }
                // 生成token
                $tokenData = [
                    'userId' => $user['id']
                ];
                $token = jwtEncode($tokenData);
                $paramsArray['token'] = $token;
                // 更新用户信息
                $this->updateLoginInfo($user, $paramsArray);
                // 响应信息
                $responseData = [
                    'token' => $token,
                    'is_business' => $isBusiness,
                    'login_type' => $paramsArray['type']
                ];
                return apiSuccess($responseData);
            } catch (\Exception $e) {
                $logContent = '登录接口异常信息：' . $e->getMessage();
                generateApiLog($logContent);
                return apiError(config('response.msg5'));
            }
        }
        return apiError(config('response.msg4'));
    }

    /**
     * 更新登录信息
     *
     * @param \app\api\model\User $user
     *
     * @param array $paramsArray
     */
    private function updateLoginInfo($user, $paramsArray)
    {
        $data = [
            'login_time' => date('Y-m-d H:i:s', time())
        ];
        // 经纬度
        $longitude = isset($paramsArray['longitude']) ? isset($paramsArray['longitude']) : null;
        $latitude = isset($paramsArray['latitude']) ? isset($paramsArray['latitude']) : null;
        if ($longitude && $latitude) {
            $data['longitude'] = $longitude;
            $data['latitude'] = $latitude;
        }
        // 平台
        $platform = Request::header('platform');
        if ($platform != 3) {
            $data['token'] = $paramsArray['token'];
        }
        if ($platform) {
            $data['platform'] = $platform;
        }
        // 版本号
        $version = Request::header('version');
        if ($version) {
            $data['version'] = $version;
        }
        // 设备ID
        $deviceId = Request::header('deviceId');
        if ($deviceId) {
            $data['device_id'] = $deviceId;
        }
        // 更新条件
        $where = [
            'id' => $user['id']
        ];
        $user->update($data, $where);
    }

    /**
     * 绑定用户的极光ID
     *
     * @return \think\response\Json
     */
    public function bindUserRegistrationId()
    {
        if ($this->request->isPost()) {
            try {
                $userId = $this->getUserId();
                if (empty($userId)) {
                    return apiError(config('response.msg9'));
                }
                // 获取极光ID/设备ID
                $uuid = input('uuid');
                if (empty($uuid)) {
                    return apiError(config('response.msg17'));
                }
                // 判断用户是否存在
                $userModel = model('api/User');
                $user = $userModel->find($userId);
                if (empty($user)) {
                    return apiError(config('response.msg9'));
                }
                // 删除其它账号相同的极光ID
                $userModel::update(['registration_id' => ''], ['registration_id' => $uuid]);
                // 绑定极光ID
                $data = [
                    'registration_id' => $uuid
                ];
                $where = ['id' => $userId];
                $userModel::update($data, $where);
                return apiSuccess();
            } catch (\Exception $e) {
                $logContent = '绑定用户极光接口异常信息：' . $e->getMessage();
                generateApiLog($logContent);
                return apiError(config('response.msg5'));
            }
        }
        return apiError(config('response.msg4'));
    }
}
