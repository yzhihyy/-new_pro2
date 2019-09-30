<?php

namespace app\api\middleware;

use app\api\model\v2_0_0\UserModel;
use app\api\model\v3_6_0\SocialUserModel;
use app\common\utils\easemob\EasemobHelper;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;

class CheckToken
{
    /**
     * 校验token中间件
     *
     * @param \think\Request $request
     * @param \Closure       $next
     *
     * @return mixed|\think\response\Redirect
     */
    public function handle($request, \Closure $next)
    {
        $token = $request->header('token', null);
        // 缺少token
        if (empty($token)) {
            list($code, $msg) = explode('|', config('response.msg1'));
            return apiError($msg, $code);
        }
        // 校验token
        try {
            $authInfo = jwtDecode($token);
            $userId = $authInfo['userId'];
            // 获取用户并判断是否存在
            $user = $this->getUserById($userId);
            if (empty($user)) {
                return apiError(config('response.msg9'));
            }

            // 判断用户是否被禁用
            $isDisable = $this->checkUserIsDisable($user);
            if ($isDisable) {
                list($code, $msg) = explode('|', config('response.msg49'));
                return apiError($msg, $code);
            }

            // 注册环信用户
            $this->registerEasemobUser($user);

            $request->user = $user;
            // 判断token是否一致，若不一致说明在其他设备登录
            $platform = $request->header('platform', null);
            if (in_array($platform, [3, 4])) {
                return $next($request);
            }
            if ($user['token'] != $token) {
                list($code, $msg) = explode('|', config('response.msg18'));
                return apiError($msg, $code);
            }
        } catch (SignatureInvalidException $e) {
            // token错误
            list($code, $msg) = explode('|', config('response.msg3'));
            return apiError($msg, $code);
        } catch (BeforeValidException $e) {
            // token不可用
            list($code, $msg) = explode('|', config('response.msg3'));
            return apiError($msg, $code);
        } catch (ExpiredException $e) {
            // token过期
            list($code, $msg) = explode('|', config('response.msg2'));
            return apiError($msg, $code);
        } catch (\Exception $e) {
            $logContent = '校验token中间件异常日志：' . $e->getMessage() . '出错文件：' . $e->getFile() . '出错行号：' . $e->getLine();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }

        return $next($request);
    }

    /**
     * 根据ID获取用户
     *
     * @param $userId
     *
     * @return array|bool|null|\PDOStatement|string|\think\Model
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getUserById($userId)
    {
        /** @var UserModel $userModel */
        $userModel = model(UserModel::class);
        $user = $userModel->alias('u')
            ->leftJoin('social_user su', 'u.id = su.user_id')
            ->field([
                'u.id',
                'u.id as userId',
                'u.lock_version AS lockVersion',
                'u.nickname',
                'u.avatar',
                'u.thumb_avatar as thumbAvatar',
                'u.phone',
                'u.money',
                'u.balance',
                'u.wechat_unionid AS wechatUnionid',
                'u.wechat_app_openid AS wechatAppOpenid',
                'u.account_status as accountStatus',
                'u.disable_expire_time as disableExpireTime',
                'u.token',
                'su.hx_uuid',
                'su.hx_username',
                'su.hx_nickname',
                'su.hx_password',
                'u.copper_coin',
                'u.invite_code',
                'u.bind_invite_code',
            ])
            ->find($userId);
        if (empty($user)) {
            return false;
        }
        return $user;
    }

    /**
     * 判断用户是否被禁用
     *
     * @param $user
     *
     * @return bool
     */
    private function checkUserIsDisable($user)
    {
        if ($user['accountStatus'] == 0) {
            return true;
        } elseif ($user['accountStatus'] == 1) {
            $nowTime = time();
            $disableExpireTime = $user['disableExpireTime'] ? strtotime($user['disableExpireTime']) : 0;
            if ($disableExpireTime > $nowTime) {
                return true;
            }
        }
        return false;
    }

    /**
     * 注册环信用户.
     *
     * @param $user
     */
    private function registerEasemobUser($user)
    {
        if (empty($user['hx_uuid'])) {
            $easemobHelper = new EasemobHelper();
            $user_prefix = config('easemob.user_prefix');
            $username = $user_prefix . 'user_' . $user->id;
            $password = generateEasemobPassword();
            $nickname = '';
            $easemobUser = $easemobHelper->authSingleRegister($username, $password, $nickname);
            if ($easemobUser) {
                // 保存环信用户
                $data = [
                    'user_id' => $user->id,
                    'hx_uuid' => $easemobUser['uuid'],
                    'hx_username' => $easemobUser['username'],
                    'hx_nickname' => $nickname,
                    'hx_password' => $password,
                    'generate_time' => date('Y-m-d H:i:s')
                ];
                $model = new SocialUserModel();
                $model::create($data);
            }
        }
    }
}
