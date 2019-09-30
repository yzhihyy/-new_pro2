<?php

namespace app\api\controller\v2_0_0\user;

use app\api\model\v3_0_0\UserInvitationModel;
use app\api\Presenter;
use app\api\logic\v2_0_0\user\UserLogic;
use app\api\model\v2_0_0\{CaptchaModel, UserModel, UserHasShopModel};
use app\api\validate\v2_0_0\LoginValidate;

class Login extends Presenter
{
    /**
     * 登录
     *
     * @return \think\response\Json
     *
     * @throws \Exception
     */
    public function index()
    {
        if ($this->request->isPost()) {
            try {
                // 校验参数
                $paramsArray = input();
                $loginType = $paramsArray['login_type'] ?? null;
                // 微信登录
                if ($loginType == 1) {
                    return $this->wechatLogin($paramsArray);
                } // 手机号登录
                elseif ($loginType == 2) {
                    return $this->phoneLogin($paramsArray);
                } // 非法登录
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
        // 校验参数
        $validate = validate(LoginValidate::class);
        $checkResult = $validate->scene('wechatLogin')->check($paramsArray);
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
        /* @var UserHasShopModel $userHasShopModel */
        $userHasShopModel = model(UserHasShopModel::class);
        $authorizedShop = $userHasShopModel->getAuthorizedShop([
            'userId' => $user['id']
        ]);
        $isBusiness = 0;
        if ($authorizedShop) {
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
        // 校验参数
        $validate = validate(LoginValidate::class);
        $checkResult = $validate->scene('phoneLogin')->check($paramsArray);
        if (!$checkResult) {
            $errorMsg = $validate->getError();
            return apiError($errorMsg);
        }
        // 校验验证码是否正确
        /* @var CaptchaModel $captchaModel */
        $captchaModel = model(CaptchaModel::class);
        $result = $captchaModel->checkLoginCode([
            'phone' => $paramsArray['phone'],
            'code' => $paramsArray['code']
        ]);
        if (empty($result)) {
            return apiError(config('response.msg6'));
        }
        // 判断用户是否已存在，如果不存在，则生成新用户
        /* @var UserModel $userModel */
        $userModel = model(UserModel::class);
        $user = $userModel->getUserByPhone($paramsArray['phone']);
        if (empty($user)) {
            $userLogic = new UserLogic();
            $user = $userLogic->createUserAndReturnInstance([
                'phone' => $paramsArray['phone']
            ]);
        } else { // 不是新注册用户判断是否是被邀请用户(在邀请的时候已经给手机号注册了用户，所以这边不是新用户)
            // TODO 20190121 产品临时要上线邀请功能，此部分代码为冗余代码，以 3.0 登录里的代码为准
            // 保存用户邀请信息
            $this->saveInvitationInfo($user, $userModel);
        }
        // 判断是否为商家
        /* @var UserHasShopModel $userHasShopModel */
        $userHasShopModel = model(UserHasShopModel::class);
        $authorizedShop = $userHasShopModel->getAuthorizedShop([
            'userId' => $user['id']
        ]);
        $isBusiness = 0;
        if ($authorizedShop) {
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
     * @throws \Exception
     *
     * @return UserModel|bool
     */
    private function checkWeChatHasBindPhone($unionid)
    {
        /* @var UserModel $user */
        $userModel = model(UserModel::class);
        $user = $userModel->alias('u')
            ->field([
                'u.id',
                'u.id as userId',
                'u.phone',
            ])
            ->where([
                'u.wechat_unionid' => $unionid
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
     * @param UserModel $user
     * @param array     $paramsArray
     *
     * @return void
     */
    private function updateLoginInfo($user, $paramsArray)
    {
        $data = [
            'login_time' => date('Y-m-d H:i:s')
        ];
        // 经纬度
        $longitude = $paramsArray['longitude'] ?? null;
        $latitude = $paramsArray['latitude'] ?? null;
        if ($longitude && $latitude) {
            $data['longitude'] = $longitude;
            $data['latitude'] = $latitude;
        }
        // 平台
        $platform = $this->request->header('platform');
        if ($platform) {
            $data['platform'] = $platform;
        }
        // H5不更新token，防止账号被踢
        if ($platform != 3) {
            $data['token'] = $paramsArray['token'];
        }
        // 版本号
        $version = $this->request->header('version');
        if ($version) {
            $data['version'] = $version;
        }
        // 设备ID
        $deviceId = $this->request->header('deviceId');
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
     * 保存用户邀请信息
     *
     * @param UserModel $user
     * @param UserModel $userModel
     *
     * @throws \Exception
     */
    private function saveInvitationInfo($user, $userModel)
    {
        // 在APP上登录且上一次登录是在H5登录且版本号为空，才判断更新邀请信息
        if (in_array($this->request->header('platform'), [1, 2]) && $user['platform'] == 3 && empty($user['version'])) {
            // 用户邀请记录模型
            $invitationModel = model(UserInvitationModel::class);
            $invitation = $invitationModel->where('invitee_user_id', $user['id'])->find();
            // 是被邀请用户则更新邀请记录
            if ($invitation) {
                $invitationData = [
                    'install_time' => date('Y-m-d H:i:s'),
                    'repeat_device_id' => 0 // 没有设备ID也算邀请成功
                ];
                $deviceId = $this->request->header('deviceId');
                if ($deviceId) {
                    $repeatDeviceId = $userModel->repeatDeviceIdCount(['userId' => $user['id'], 'deviceId' => $deviceId]);
                    if ($repeatDeviceId > 0) {
                        unset($invitationData['repeat_device_id']);
                    }
                    $invitationData['device_id'] = $deviceId;
                }
                $invitation->save($invitationData, ['id' => $invitation['id']]);
            }
        }
    }

    /**
     * 绑定用户的极光ID
     *
     * @return \think\response\Json
     */
    public function bindUserRegistrationId()
    {
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
            // 获取用户并判断是否存在
            $userModel = model(UserModel::class);
            $user = $userModel->find($userId);
            if (empty($user)) {
                return apiError(config('response.msg9'));
            }
            // 删除其它账号相同的极光ID
            $userModel::update(['registration_id' => ''], ['registration_id' => $uuid]);
            // 绑定极光ID
            $userModel::update(['registration_id' => $uuid], ['id' => $userId]);
            return apiSuccess();
        } catch (\Exception $e) {
            $logContent = '绑定用户极光接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 微信绑定手机
     *
     * @return \think\response\Json
     */
    public function wechatBindPhone()
    {
        if ($this->request->isPost()) {
            try {
                // 校验参数
                $paramsArray = input();
                $validate = validate(LoginValidate::class);
                $checkResult = $validate->scene('wechatBindPhone')->check($paramsArray);
                if (!$checkResult) {
                    $errorMsg = $validate->getError();
                    return apiError($errorMsg);
                }
                // 校验手机验证码是否正确
                /* @var CaptchaModel $captchaModel */
                $captchaModel = model(CaptchaModel::class);
                $result = $captchaModel->checkLoginCode([
                    'phone' => $paramsArray['phone'],
                    'code' => $paramsArray['code']
                ]);
                if (empty($result)) {
                    return apiError(config('response.msg6'));
                }
                // 校验微信是否已经被绑定过手机
                /* @var UserModel $userModel */
                $userModel = model(UserModel::class);
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
                        'userId' => $user['id']
                    ];
                    $token = jwtEncode($tokenData);
                    // 绑定账号
                    $data = [
                        'wechat_unionid' => $paramsArray['unionid'],
                    ];
                    if ($this->request->header('platform') != 3) {
                        $data['token'] = $token;
                    }
                    $where = [
                        'id' => $user->id
                    ];
                    $user->save($data, $where);
                } else {
                    $createData = [
                        'phone' => $paramsArray['phone'],
                        'wechat_unionid' => $paramsArray['unionid'],
                    ];
                    $userLogic = new UserLogic();
                    $user = $userLogic->createUserAndReturnInstance($createData);
                    // 生成token
                    $tokenData = [
                        'userId' => $user['id']
                    ];
                    $token = jwtEncode($tokenData);
                    // 更新token
                    if ($this->request->header('platform') != 3) {
                        $data = [
                            'token' => $token
                        ];
                        $where = [
                            'id' => $user['id']
                        ];
                        $user->save($data, $where);
                    }
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
        if ($this->request->isPost()) {
            try {
                $unionid = input('unionid');
                if (empty($unionid)) {
                    return apiError(config('response.msg45'));
                }
                // 获取用户ID
                $userId = $this->getUserId();
                /* @var UserModel $userModel */
                $userModel = model(UserModel::class);
                $user = $userModel->find($userId);
                if (empty($user)) {
                    return apiError(config('response.msg9'));
                }
                // 判断该用户是否绑定过微信
                if ($user['wechat_unionid']) {
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
                    'id' => $user['id']
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
