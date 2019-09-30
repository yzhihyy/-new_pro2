<?php

namespace app\api\controller\v1_1_0\center;

use app\api\Presenter;
use think\facade\Request;
use app\common\utils\string\StringHelper;

class Auth extends Presenter
{
    /**
     * 登录
     *
     * @return \think\response\Json
     */
    public function login()
    {
        if ($this->request->isPost()) {
            try {
                // 校验参数
                $paramsArray = input();
                $loginType = isset($paramsArray['login_type']) ? $paramsArray['login_type'] : null;
                // 微信登录
                if ($loginType == 1) {
                    return $this->wechatLogin($paramsArray);
                }
                // 手机号登录
                elseif ($loginType == 2) {
                    return $this->phoneLogin($paramsArray);
                }
                // 非法登录
                else {
                    return apiError();
                }
            } catch (\Exception $e) {
                $logContent = '登录接口异常信息：' . $e->getMessage();
                generateApiLog($logContent);
                return apiError(config('response.msg5'));
            }
        }
        return apiError(config('response.msg4'));
    }

    /**
     * 微信登录
     *
     * @param $paramsArray
     *
     * @throws
     *
     * @return \think\response\Json
     */
    private function wechatLogin($paramsArray)
    {
        /**
         * @var \app\api\model\Captcha $captchaModel
         * @var \app\api\model\User $userModel
         */
        // 校验参数
        $validate = validate('api/v1_1_0/Auth');
        $checkResult = $validate->scene('login1')->check($paramsArray);
        if (!$checkResult) {
            $errorMsg = $validate->getError();
            return apiError($errorMsg);
        }
        // 校验微信是否绑定过手机，若绑定过者直接登录成功，若未绑定报错，必须绑定手机才能登录。
        $user = $this->checkWeChatHasBindPhone($paramsArray['unionid']);
        if (!$user) {
            list($code, $msg) = explode('|', config('response.msg42'));
            return apiError($msg, $code);
        }
        // 判断是否为商家
        $userModel = model('api/User');
        $business = $userModel->getBusinessByPhone($user['phone']);
        $isBusiness = 0;
        if ($business && $business['accountStatus'] == 1 && $business['onlineStatus'] == 1) {
            $isBusiness = 1;
        }
        // 登录成功，生成token
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
            'user_type' => 1,
            'login_type' => $paramsArray['login_type'],
            'has_bind_wechat' => 1
        ];
        return apiSuccess($responseData);
    }

    /**
     * 手机号登录
     *
     * @param $paramsArray
     *
     * @return \think\response\Json
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function phoneLogin($paramsArray)
    {
        /**
         * @var \app\api\model\Captcha $captchaModel
         * @var \app\api\model\User $userModel
         */
        // 校验参数
        $validate = validate('api/v1_1_0/Auth');
        $checkResult = $validate->scene('login2')->check($paramsArray);
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
            $user = $userModel::create($createData);
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
            'user_type' => $paramsArray['user_type'],
            'login_type' => $paramsArray['login_type'],
            'has_bind_wechat' => empty($user['wechat_unionid']) ? 0 : 1
        ];
        return apiSuccess($responseData);
    }

    /**
     * 判断微信用户是否绑定过手机
     *
     * @param $unionid
     *
     * @throws
     *
     * @return \app\api\model\User|bool
     */
    private function checkWeChatHasBindPhone($unionid)
    {
        /**
         * @var \app\api\model\User $user
         */
        $userModel = model('api/User');
        $user = $userModel->alias('u')
            ->field([
                'u.id',
                'u.id as userId',
                'u.phone',
            ])
            ->where([
                'wechat_unionid' => $unionid
            ])
            ->find();
        if ($user) {
            return $user;
        } else {
            return false;
        }
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
            'login_time' => date('Y-m-d H:i:s')
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
        if ($platform) {
            $data['platform'] = $platform;
        }
        // H5不更新token，防止账号被踢
        if ($platform != 3) {
            $data['token'] = $paramsArray['token'];
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
        $user->save($data, $where);
    }

    /**
     * 微信绑定手机
     *
     * @return \think\response\Json
     */
    public function wechatBindPhone()
    {
        /**
         * @var \app\api\model\Captcha $captchaModel
         * @var \app\api\model\User $userModel
         */
        if ($this->request->isPost()) {
            try {
                // 校验参数
                $paramsArray = input();
                $validate = validate('api/v1_1_0/Auth');
                $checkResult = $validate->scene('wechatBindPhone')->check($paramsArray);
                if (!$checkResult) {
                    $errorMsg = $validate->getError();
                    return apiError($errorMsg);
                }
                // 校验手机验证码是否正确
                $captchaModel = model('api/Captcha');
                $result = $captchaModel->checkLoginCode([
                    'phone' => $paramsArray['phone'],
                    'code' => $paramsArray['code']
                ]);
                if (empty($result)) {
                    return apiError(config('response.msg6'));
                }
                // 校验微信是否已经被绑定过手机
                $userModel = model('api/User');
                $user = $userModel->alias('u')
                    ->where([
                        'wechat_unionid' => $paramsArray['unionid']
                    ])
                    ->find();
                if ($user) {
                    return apiError(config('response.msg43'));
                }
                // 校验手机是否已经被绑定过微信
                $user = $userModel->alias('u')
                    ->where([
                        'phone' => $paramsArray['phone']
                    ])
                    ->find();
                if ($user) {
                    if ($user['wechat_unionid']) {
                        return apiError(config('response.msg44'));
                    }
                    // 生成token
                    $tokenData = [
                        'userId' => $user->id
                    ];
                    $token = jwtEncode($tokenData);
                    // 绑定账号
                    $data = [
                        'wechat_unionid' => $paramsArray['unionid'],
                        'token' => $token
                    ];
                    $where = [
                        'id' => $user->id
                    ];
                    $user->save($data, $where);
                } else {
                    $createData = [
                        'phone' => $paramsArray['phone'],
                        'nickname' => StringHelper::hidePartOfString($paramsArray['phone']),
                        'avatar' => config('parameters.new_register_user_avatar'),
                        'thumb_avatar' => config('parameters.new_register_user_avatar'),
                        'login_time' => date('Y-m-d H:i:s'),
                        'account_status' => 1,
                        'generate_time' => date('Y-m-d H:i:s'),
                        'wechat_unionid'=> $paramsArray['unionid'],
                    ];
                    $user = $userModel::create($createData);
                    // 生成token
                    $tokenData = [
                        'userId' => $user->id
                    ];
                    $token = jwtEncode($tokenData);
                    // 更新token
                    $data = [
                        'token' => $token
                    ];
                    $where = [
                        'id' => $user->id
                    ];
                    $user->save($data, $where);
                }
                // 返回数据
                $responseData = [
                    'token' => $token
                ];
                return apiSuccess($responseData);
            } catch (\Exception $e) {
                $logContent = '微信绑定手机接口异常信息：' . $e->getMessage();
                generateApiLog($logContent);
                return apiError(config('response.msg5'));
            }
        }
        return apiError(config('response.msg4'));
    }

    /**
     * 手机绑定微信
     *
     * @return \think\response\Json
     */
    public function phoneBindWechat()
    {
        /**
         * @var \app\api\model\User $userModel
         */
        if ($this->request->isPost()) {
            try {
                $unionid = input('unionid');
                if (empty($unionid)) {
                    return apiError(config('response.msg45'));
                }
                // 获取用户ID
                $userId = $this->getUserId();
                $userModel = model('api/User');
                $user = $userModel->find($userId);
                if (empty($user)) {
                    return apiError(config('response.msg9'));
                }
                // 判断该用户是否绑定过微信
                if ($user->wechat_unionid) {
                    return apiError(config('response.msg44'));
                }
                // 判断该微信是否绑定过手机
                $userInfo = $userModel->alias('u')
                    ->field([
                        'u.id',
                        'u.phone',
                    ])
                    ->where([
                        'wechat_unionid' => $unionid
                    ])
                    ->find();
                if ($userInfo) {
                    return apiError(config('response.msg43'));
                }
                // 绑定账号
                $data = [
                    'wechat_unionid' => $unionid
                ];
                $where = [
                    'id' => $user->id
                ];
                $result = $user->force()->save($data, $where);
                return $result ? apiSuccess() : apiError();
            } catch (\Exception $e) {
                $logContent = '手机绑定微信接口异常信息：' . $e->getMessage();
                generateApiLog($logContent);
                return apiError(config('response.msg5'));
            }
        }
        return apiError(config('response.msg4'));
    }
}
