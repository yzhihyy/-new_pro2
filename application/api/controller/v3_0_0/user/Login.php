<?php

namespace app\api\controller\v3_0_0\user;

use app\api\logic\v3_0_0\RobotLogic;
use app\api\logic\v3_6_0\EasemobLogic;
use app\api\model\v3_0_0\UserInvitationModel;
use app\api\Presenter;
use app\api\logic\v2_0_0\user\UserLogic;
use app\api\model\v2_0_0\{CaptchaModel, UserModel};
use app\api\service\UploadService;
use app\api\validate\v3_0_0\LoginValidate;
use app\common\utils\string\StringHelper;
use app\common\utils\xxtea\xxtea;
use Hashids\Hashids;

class Login extends Presenter
{

    /**
     * 登录.
     *
     * @return \think\response\Json
     */
    public function index()
    {
        if ($this->request->isPost()) {
            try {
                // 校验参数
                $paramsArray = input();
                $loginType = $paramsArray['login_type'] ?? null;
                // 不同登录类型执行不同操作
                if ($loginType == 1) {
                    // APP手机号登录
                    return $this->phoneLogin($paramsArray);
                } elseif ($loginType == 2) {
                    // 微信登录
                    return $this->wechatLogin($paramsArray);
                } elseif ($loginType == 3) {
                    // QQ登录
                    return $this->qqLogin($paramsArray);
                } elseif ($loginType == 4) {
                    // H5邀请登录
                    return $this->h5InviteLogin($paramsArray);
                }
                else {
                    // 非法登录
                    return apiError('非法登录类型');
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
     * APP手机号登录.
     *
     * @param array $paramsArray
     *
     * @return \think\response\Json
     * @throws \Exception
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
            'code' => $paramsArray['code'],
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
                'phone' => $paramsArray['phone'],
                'bind_invite_code' => $paramsArray['invite_code'] ?? '',
            ]);
            // 处理机器人逻辑
            $this->handleRobotLogic($user['id']);
        } else { // 不是新注册用户判断是否是被邀请用户(在邀请的时候已经给手机号注册了用户，所以这边不是新用户)
            // 保存用户邀请信息
            $this->saveInvitationInfo($user, $userModel);
        }

        // 绑定邀请码
        $result = $this->handleInviteLogic($user['id'], $paramsArray);
        if ($result !== true) {
            return $result;
        }

        // 生成token
        $tokenData = [
            'userId' => $user['id'],
        ];
        $token = jwtEncode($tokenData);
        $paramsArray['token'] = $token;
        // 更新用户信息
        $this->updateLoginInfo($user, $paramsArray);

        // 获取社交信息
        $logic = new EasemobLogic();
        $socialInfo = $logic->getEasemobInfo($user['id']);

        // 响应信息
        $responseData = [
            'token' => $token,
            'user_id' => $user['id'],
            'nickname' => $user['nickname'],
            'avatar' => getImgWithDomain($user['avatar']),
            'thumb_avatar' => getImgWithDomain($user['thumb_avatar']),
            'wechat_nickname' => empty($user['wechatNickname']) ? '' : $user['wechatNickname'],
            'has_bind_wechat' => empty($user['wechatUnionid']) ? 0 : 1,
            'qq_nickname' => empty($user['qqNickname']) ? '' : $user['qqNickname'],
            'has_bind_qq' => empty($user['qqUnionid']) ? 0 : 1,
            'bind_invite_code' => $paramsArray['invite_code'] ?? '',
            'social_info' => $socialInfo,
        ];
        return apiSuccess($responseData);
    }

    /**
     * 微信登录.
     *
     * @param array $paramsArray
     *
     * @return \think\response\Json
     * @throws \Exception
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
        // 登录成功，生成token
        $tokenData = [
            'userId' => $user['id'],
        ];
        $token = jwtEncode($tokenData);
        $paramsArray['token'] = $token;
        // 更新用户信息
        $this->updateLoginInfo($user, $paramsArray);

        // 获取社交信息
        $logic = new EasemobLogic();
        $socialInfo = $logic->getEasemobInfo($user['id']);

        // 响应信息
        $responseData = [
            'token' => $token,
            'user_id' => $user['id'],
            'nickname' => $user['nickname'],
            'avatar' => getImgWithDomain($user['avatar']),
            'thumb_avatar' => getImgWithDomain($user['thumb_avatar']),
            'wechat_nickname' => $user['wechatNickname'],
            'has_bind_wechat' => $user['wechatUnionid'] ? 1 : 0,
            'qq_nickname' => $user['qqNickname'],
            'has_bind_qq' => $user['qqUnionid'] ? 1 : 0,
            'bind_invite_code' => $user['bindInviteCode'] ?? '',
            'social_info' => $socialInfo,
        ];
        return apiSuccess($responseData);
    }

    /**
     * QQ登录.
     *
     * @param array $paramsArray
     *
     * @return \think\response\Json
     * @throws \Exception
     */
    private function qqLogin($paramsArray)
    {
        // 校验参数
        $validate = validate(LoginValidate::class);
        $checkResult = $validate->scene('qqLogin')->check($paramsArray);
        if (!$checkResult) {
            $errorMsg = $validate->getError();
            return apiError($errorMsg);
        }
        // 校验QQ是否绑定过手机，若绑定过者直接登录成功，若未绑定报错，必须绑定手机才能登录。
        $user = $this->checkQQHasBindPhone($paramsArray['unionid']);
        if (!$user) {
            list($code, $msg) = explode('|', config('response.msg73'));
            return apiError($msg, $code);
        }
        // 登录成功，生成token
        $tokenData = [
            'userId' => $user['id'],
        ];
        $token = jwtEncode($tokenData);
        $paramsArray['token'] = $token;
        // 更新用户信息
        $this->updateLoginInfo($user, $paramsArray);
        // 获取社交信息
        $logic = new EasemobLogic();
        $socialInfo = $logic->getEasemobInfo($user['id']);
        // 响应信息
        $responseData = [
            'token' => $token,
            'user_id' => $user['id'],
            'nickname' => $user['nickname'],
            'avatar' => getImgWithDomain($user['avatar']),
            'thumb_avatar' => getImgWithDomain($user['thumb_avatar']),
            'wechat_nickname' => $user['wechatNickname'],
            'has_bind_wechat' => $user['wechatUnionid'] ? 1 : 0,
            'qq_nickname' => $user['qqNickname'],
            'has_bind_qq' => $user['qqUnionid'] ? 1 : 0,
            'bind_invite_code' => $user['bindInviteCode'] ?? '',
            'social_info' => $socialInfo,
        ];
        return apiSuccess($responseData);
    }

    /**
     * H5邀请登录.
     *
     * @param array $paramsArray
     *
     * @return \think\response\Json
     * @throws \Exception
     */
    private function h5InviteLogin($paramsArray)
    {
        // 校验参数
        $validate = validate(LoginValidate::class);
        $checkResult = $validate->scene('h5InviteLogin')->check($paramsArray);
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
        // 用户已存在
        if (!empty($user)) {
            return apiSuccess(['is_new_user' => 0]);
        }

        $nowTime = date('Y-m-d H:i:s');
        $userLogic = new UserLogic();
        $user = $userLogic->createUserAndReturnInstance([
            'phone' => $paramsArray['phone'],
            'platform' => 3 // 平台为H5
        ]);

        // 有传邀请码，判断是否是被邀请用户
        if (isset($paramsArray['invite_code'])) {
            $inviteCode = xxtea::decrypt($paramsArray['invite_code'], config('share.invitation_info.invite_code_salt'));
            // 邀请码正确，增加邀请记录
            if ($inviteCode) {
                UserInvitationModel::create([
                    'user_id' => model(UserModel::class)->where('invite_code', $inviteCode)->value('id'),
                    'invitee_user_id' => $user['id'],
                    'generate_time' => $nowTime
                ]);
            }
        }

        // 登录成功，生成token
        $tokenData = [
            'userId' => $user['id'],
        ];
        $token = jwtEncode($tokenData);
        $paramsArray['token'] = $token;
        // 更新用户信息
        $this->updateLoginInfo($user, $paramsArray);

        // 获取社交信息
        $logic = new EasemobLogic();
        $socialInfo = $logic->getEasemobInfo($user['id']);

        // 响应信息
        $responseData = [
            'is_new_user' => 1,
            'token' => $token,
            'user_id' => $user['id'],
            'nickname' => $user['nickname'],
            'avatar' => getImgWithDomain($user['avatar']),
            'thumb_avatar' => getImgWithDomain($user['thumb_avatar']),
            'wechat_nickname' => '',
            'has_bind_wechat' => 0,
            'qq_nickname' => '',
            'has_bind_qq' => 0,
            'bind_invite_code' => $user['bindInviteCode'] ?? '',
            'social_info' => $socialInfo,
        ];

        return apiSuccess($responseData);
    }

    /**
     * 判断微信用户是否绑定过手机.
     *
     * @param string $unionid
     *
     * @return UserModel|bool
     * @throws \Exception
     */
    private function checkWeChatHasBindPhone($unionid)
    {
        $where = ['u.wechat_unionid' => $unionid];
        return $this->checkThirdPartyUserHasBindPhone($where);
    }

    /**
     * 判断QQ用户是否绑定过手机.
     *
     * @param string $unionid
     *
     * @return UserModel|bool
     * @throws \Exception
     */
    private function checkQQHasBindPhone($unionid)
    {
        $where = ['u.qq_unionid' => $unionid];
        return $this->checkThirdPartyUserHasBindPhone($where);
    }

    /**
     * 判断第三方用户是否绑定过手机.
     *
     * @param array $where
     *
     * @return UserModel|bool
     * @throws \Exception
     */
    private function checkThirdPartyUserHasBindPhone($where)
    {
        /* @var UserModel $user */
        $userModel = model(UserModel::class);
        $user = $userModel->alias('u')
            ->field([
                'u.id',
                'u.id as userId',
                'u.phone',
                'u.nickname',
                'u.avatar',
                'u.thumb_avatar',
                'u.wechat_nickname as wechatNickname',
                'u.wechat_unionid as wechatUnionid',
                'u.qq_nickname as qqNickname',
                'u.qq_unionid as qqUnionid',
                'u.bind_invite_code as bindInviteCode',
            ])
            ->where($where)
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
     * @param array $paramsArray
     *
     */
    private function updateLoginInfo($user, $paramsArray)
    {
        $data = [
            'login_time' => date('Y-m-d H:i:s'),
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
        if(empty($user['bindInviteCode']) && isset($paramsArray['invite_code']) && !empty($paramsArray['invite_code'])){
            $data['bind_invite_code'] = $paramsArray['invite_code'];
        }
        // 更新条件
        $where = [
            'id' => $user['id'],
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
     * 第三方登录绑定手机.
     *
     * @return \think\response\Json
     */
    public function thirdPartyBindPhone()
    {
        try {
            // 获取参数并校验
            $paramsArray = input();
            $validate = validate(LoginValidate::class);
            $checkResult = $validate->scene('thirdPartyBindPhone')->check($paramsArray);
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
            // 绑定类型，2微信，3QQ
            $thirdPartyType = $paramsArray['third_party_type'];
            switch ($thirdPartyType) {
                case 2:
                    return $this->weChatBindPhone($paramsArray);
                    break;
                case 3:
                    return $this->qqBindPhone($paramsArray);
                    break;
                default:
                    return apiError('类型错误');
                    break;
            }
        } catch (\Exception $e) {
            $logContent = '第三方登录绑定手机接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 微信绑定手机.
     *
     * @param array $paramsArray
     *
     * @return \think\response\Json
     */
    private function weChatBindPhone($paramsArray)
    {
        try {
            // 校验微信是否已经被绑定过手机
            /* @var UserModel $userModel */
            $userModel = model(UserModel::class);
            $where['wechat_unionid'] = $paramsArray['unionid'];
            !empty($paramsArray['openid']) && $where['wechat_app_openid'] = $paramsArray['openid'];
            $user = $userModel->alias('u')->where($where)->find();
            if ($user) {
                return apiError(config('response.msg43'));
            }
            // 校验手机是否已经注册过
            $user = $userModel->alias('u')->where('phone', $paramsArray['phone'])->find();
            if ($user) {
                // 校验手机是否已经被绑定过微信
                if ((!empty($paramsArray['openid']) && ($user['wechat_unionid'] && $user['wechat_app_openid'])) || ($user['wechat_unionid'])) {
                    return apiError(config('response.msg44'));
                }
                // 生成token
                $tokenData = ['userId' => $user['id']];
                $token = jwtEncode($tokenData);
                // 绑定账号
                $data = [
                    'wechat_nickname' => $paramsArray['nickname'],
                    'wechat_unionid' => $paramsArray['unionid'],
                    'token' => $token,
                ];
                !empty($paramsArray['openid']) && $data['wechat_app_openid'] = $paramsArray['openid'];
            } else {
                // 注册用户并绑定该微信
                $createData = [
                    'phone' => $paramsArray['phone'],
                    'wechat_nickname' => $paramsArray['nickname'],
                    'wechat_unionid' => $paramsArray['unionid'],
                ];
                !empty($paramsArray['openid']) && $createData['wechat_app_openid'] = $paramsArray['openid'];
                $userLogic = new UserLogic();
                $user = $userLogic->createUserAndReturnInstance($createData);
                // 生成token
                $tokenData = [
                    'userId' => $user['id']
                ];
                $token = jwtEncode($tokenData);
                // 更新token
                $data = ['token' => $token];
                // 处理机器人逻辑
                $this->handleRobotLogic($user['id']);
            }

            // 默认昵称修改
            $checkNickname = StringHelper::hidePartOfString($user['phone']);
            if($checkNickname == $user['nickname']){
                $data['nickname'] = $paramsArray['nickname'];
            }
            // 更新头像
            $default = config('parameters.new_register_user_avatar');
            if(isset($paramsArray['avatar']) && !empty($paramsArray['avatar']) && $user['avatar'] == $default){
                $domain = config('resources_domain');
                $avatar = str_replace($domain, '', $paramsArray['avatar']);
                $uploadService = new UploadService();
                $data['avatar'] = $avatar;
                $data['thumb_avatar'] = $uploadService->getImageThumbName($avatar);
            }

            // 绑定邀请码
            $result = $this->handleInviteLogic($user['id'], $paramsArray);
            if ($result !== true) {
                return $result;
            }
            $where = ['id' => $user['id']];
            $user->save($data, $where);

            // 返回数据
            $responseData = [
                'token' => $token,
                'user_id' => $user['id'],
                'wechat_nickname' => $paramsArray['nickname'],
                'has_bind_wechat' => 1,
                'qq_nickname' => empty($user['qq_nickname']) ? '' : $user['qq_nickname'],
                'has_bind_qq' => empty($user['qq_unionid']) ? 0 : 1,
            ];
            return apiSuccess($responseData);
        } catch (\Exception $exception) {
            $logContent = '微信绑定手机异常信息：' . $exception->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * QQ绑定手机.
     *
     * @param array $paramsArray
     *
     * @return \think\response\Json
     */
    private function qqBindPhone($paramsArray)
    {
        try {
            // 校验QQ是否已经被绑定过手机
            /* @var UserModel $userModel */
            $userModel = model(UserModel::class);
            $user = $userModel->alias('u')->where('qq_unionid', $paramsArray['unionid'])->find();
            if ($user) {
                return apiError(config('response.msg74'));
            }
            // 校验手机是否已经注册过
            $user = $userModel->alias('u')->where('phone', $paramsArray['phone'])->find();
            if ($user) {
                // 校验手机是否已经被绑定过QQ
                if ($user['qq_unionid']) {
                    return apiError(config('response.msg75'));
                }
                // 生成token
                $tokenData = ['userId' => $user['id']];
                $token = jwtEncode($tokenData);
                // 绑定账号
                $data = [
                    'token'     => $token,
                    'qq_nickname' => $paramsArray['nickname'],
                    'qq_unionid' => $paramsArray['unionid'],
                    'user_id' => $user['id'],
                ];

            } else {
                // 注册用户并绑定该QQ
                $createData = [
                    'phone' => $paramsArray['phone'],
                    'qq_nickname' => $paramsArray['nickname'],
                    'qq_unionid' => $paramsArray['unionid'],
                ];
                $userLogic = new UserLogic();
                $user = $userLogic->createUserAndReturnInstance($createData);
                // 生成token
                $tokenData = [
                    'userId' => $user['id']
                ];
                $token = jwtEncode($tokenData);
                // 更新token
                $data = ['token' => $token];

                // 处理机器人逻辑
                $this->handleRobotLogic($user['id']);
            }
            // 默认昵称修改
            $checkNickname = StringHelper::hidePartOfString($user['phone']);
            if($checkNickname == $user['nickname']){
                $data['nickname'] = $paramsArray['nickname'];
            }
            // 更新头像
            $default = config('parameters.new_register_user_avatar');
            if(isset($paramsArray['avatar']) && !empty($paramsArray['avatar']) &&  $user['avatar'] == $default){
                $domain = config('resources_domain');
                $avatar = str_replace($domain, '', $paramsArray['avatar']);
                $uploadService = new UploadService();
                $data['avatar'] = $avatar;
                $data['thumb_avatar'] = $uploadService->getImageThumbName($avatar);
            }

            // 绑定邀请码
            $result = $this->handleInviteLogic($user['id'], $paramsArray);
            if ($result !== true) {
                return $result;
            }

            $where = ['id' => $user['id']];
            $user->save($data, $where);
            // 返回数据
            $responseData = [
                'token' => $token,
                'user_id' => $user['id'],
                'wechat_nickname' => empty($user['wechat_nickname']) ? '' : $user['wechat_nickname'],
                'has_bind_wechat' => empty($user['wechat_unionid']) ? 0 : 1,
                'qq_nickname' => $paramsArray['nickname'],
                'has_bind_qq' => 1,
            ];
            return apiSuccess($responseData);
        } catch (\Exception $exception) {
            $logContent = 'QQ绑定手机异常信息：' . $exception->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 绑定第三方.
     *
     * @return \think\response\Json
     */
    public function bindThirdParty()
    {
        try {
            // 获取参数并校验
            $paramsArray = input();
            $validate = validate(LoginValidate::class);
            $checkResult = $validate->scene('phoneBindThirdParty')->check($paramsArray);
            if (!$checkResult) {
                $errorMsg = $validate->getError();
                return apiError($errorMsg);
            }
            // 绑定类型，2微信，3QQ
            $thirdPartyType = $paramsArray['third_party_type'];
            switch ($thirdPartyType) {
                case 2:
                    return $this->bindWeChat($paramsArray);
                    break;
                case 3:
                    return $this->bindQQ($paramsArray);
                    break;
                default:
                    return apiError('类型错误');
                    break;
            }
        } catch (\Exception $e) {
            $logContent = '绑定第三方接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 绑定微信.
     *
     * @param array $paramsArray
     *
     * @return \think\response\Json
     */
    private function bindWeChat($paramsArray)
    {
        try {
            // 获取用户并判断是否存在
            $userId = $this->getUserId();
            /* @var UserModel $userModel */
            $userModel = model(UserModel::class);
            $user = $userModel->find($userId);
            if (empty($user)) {
                return apiError(config('response.msg9'));
            }
            // 判断该用户是否绑定过微信
            if ((!empty($paramsArray['openid']) && ($user['wechat_unionid'] && $user['wechat_app_openid'])) || ($user['wechat_unionid'])) {
                return apiError(config('response.msg44'));
            }
            // 判断该微信是否绑定过手机
            $where['wechat_unionid'] = $paramsArray['unionid'];
            (!empty($paramsArray['openid'])) && $where['wechat_app_openid'] = $paramsArray['openid'];
            $userInfo = $userModel->alias('u')
                ->field([
                    'u.id',
                    'u.phone',
                ])
                ->where($where)
                ->find();
            if ($userInfo) {
                return apiError(config('response.msg43'));
            }
            // 绑定账号
            $data = [
                'wechat_nickname' => $paramsArray['nickname'],
                'wechat_unionid' => $paramsArray['unionid'],
            ];
            (!empty($paramsArray['openid'])) && $data['wechat_app_openid'] = $paramsArray['openid'];
            // 默认昵称修改
            $checkNickname = StringHelper::hidePartOfString($user['phone']);
            if($checkNickname == $user['nickname']){
                $data['nickname'] = $paramsArray['nickname'];
            }
            // 更新头像
            $default = config('parameters.new_register_user_avatar');
            if(isset($paramsArray['avatar']) && !empty($paramsArray['avatar']) &&  $user['avatar'] == $default){
                $domain = config('resources_domain');
                $avatar = str_replace($domain, '', $paramsArray['avatar']);
                $uploadService = new UploadService();
                $data['avatar'] = $avatar;
                $data['thumb_avatar'] = $uploadService->getImageThumbName($avatar);
            }

            // 绑定邀请码
            $result = $this->handleInviteLogic($user['id'], $paramsArray);
            if ($result !== true) {
                return $result;
            }

            $where = ['id' => $user['id']];
            $result = $user->force()->save($data, $where);
            return $result ? apiSuccess() : apiError();
        } catch (\Exception $e) {
            $logContent = '手机绑定微信异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 绑定QQ.
     *
     * @param array $paramsArray
     *
     * @return \think\response\Json
     */
    private function bindQQ($paramsArray)
    {
        try {
            // 获取用户并判断是否存在
            $userId = $this->getUserId();
            /* @var UserModel $userModel */
            $userModel = model(UserModel::class);
            $user = $userModel->find($userId);
            if (empty($user)) {
                return apiError(config('response.msg9'));
            }
            // 判断该用户是否绑定过QQ
            if ($user['qq_unionid']) {
                return apiError(config('response.msg75'));
            }
            // 判断该QQ是否绑定过手机
            $userInfo = $userModel->alias('u')
                ->field([
                    'u.id',
                    'u.phone',
                ])
                ->where('qq_unionid', $paramsArray['unionid'])
                ->find();
            if ($userInfo) {
                return apiError(config('response.msg74'));
            }
            // 绑定账号
            $data = [
                'qq_nickname' => $paramsArray['nickname'],
                'qq_unionid' => $paramsArray['unionid']
            ];
            // 默认昵称修改
            $checkNickname = StringHelper::hidePartOfString($user['phone']);
            if($checkNickname == $user['nickname']){
                $data['nickname'] = $paramsArray['nickname'];
            }
            // 更新头像
            $default = config('parameters.new_register_user_avatar');
            if(isset($paramsArray['avatar']) && !empty($paramsArray['avatar']) &&  $user['avatar'] == $default){
                $domain = config('resources_domain');
                $avatar = str_replace($domain, '', $paramsArray['avatar']);
                $uploadService = new UploadService();
                $data['avatar'] = $avatar;
                $data['thumb_avatar'] = $uploadService->getImageThumbName($avatar);
            }

            // 绑定邀请码
            $result = $this->handleInviteLogic($user['id'], $paramsArray);
            if ($result !== true) {
                return $result;
            }

            $where = ['id' => $user['id']];
            $result = $user->force()->save($data, $where);
            return $result ? apiSuccess() : apiError();
        } catch (\Exception $e) {
            $logContent = '手机绑定QQ异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 解绑第三方.
     *
     * @return \think\response\Json
     */
    public function unBindThirdParty()
    {
        try {
            // 获取参数并校验
            $paramsArray = input();
            $validate = validate(LoginValidate::class);
            $checkResult = $validate->scene('unBindThirdParty')->check($paramsArray);
            if (!$checkResult) {
                $errorMsg = $validate->getError();
                return apiError($errorMsg);
            }
            // 获取用户并判断是否存在
            $userId = $this->getUserId();
            /* @var UserModel $userModel */
            $userModel = model(UserModel::class);
            $user = $userModel->find($userId);
            if (empty($user)) {
                return apiError(config('response.msg9'));
            }
            // 解绑类型，2微信，3QQ
            $thirdPartyType = $paramsArray['third_party_type'];
            switch ($thirdPartyType) {
                case 2:
                    // 解绑微信
                    $data = [
                        'wechat_nickname' => '',
                        'wechat_unionid' => '',
                        'wechat_app_openid' => ''
                    ];
                    $where = [
                        'id' => $user['id']
                    ];
                    $user->save($data, $where);
                    return apiSuccess();
                    break;
                case 3:
                    // 解绑QQ
                    $data = [
                        'qq_nickname' => '',
                        'qq_unionid' => ''
                    ];
                    $where = [
                        'id' => $user['id']
                    ];
                    $user->save($data, $where);
                    return apiSuccess();
                    break;
                default:
                    return apiError('类型错误');
                    break;
            }
        } catch (\Exception $e) {
            $logContent = '解绑第三方接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 处理机器人逻辑.
     *
     * @param int $userId
     *
     * @throws \Exception
     */
    private function handleRobotLogic($userId)
    {
        $logic = new RobotLogic();
        $logic->handleUserRegister($userId);
    }

    private function handleInviteLogic($userId, $paramsArray)
    {
        if (!empty($paramsArray['invite_code'])) {
            $userModel = new UserModel();
            $user = $userModel->find($userId);
            // 判断邀请码是否存在
            $data = $userModel->where('invite_code', '=', $paramsArray['invite_code'])->find();
            if (empty($data)) {
                return apiError('该邀请码不存在');
            }
            // 判断账号是否已绑定过邀请码
            if (!empty($user['bind_invite_code'])) {
                return apiError('该账号已绑定过邀请码');
            }
            // 判断是否为自己的邀请码
            if ($paramsArray['invite_code'] == $user['invite_code']) {
                return apiError('不能绑定自己的邀请码');
            }
            // 绑定邀请码
            $user->save([
                'bind_invite_code' => $paramsArray['invite_code']
            ]);
        }
        return true;
    }
}
