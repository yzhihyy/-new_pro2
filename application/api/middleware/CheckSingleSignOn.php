<?php

namespace app\api\middleware;

use app\api\model\v2_0_0\UserModel;

class CheckSingleSignOn
{
    /**
     * 校验单点登录
     *
     * @param \think\Request $request
     * @param \Closure       $next
     *
     * @return mixed|\think\response\Redirect
     */
    public function handle($request, \Closure $next)
    {
        $platform = $request->header('platform', null);
        if ($platform == 3) {
            return $next($request);
        }
        $token = $request->header('token', null);
        if (empty($token)) {
            return $next($request);
        } else {
            try {
                // 如果token找不到，说明在其它设备登录
                $user = $this->getUserByToken($token);
                if (empty($user)) {
                    list($code, $msg) = explode('|', config('response.msg18'));
                    return apiError($msg, $code);
                }
                // 判断用户是否被禁用
                $isDisable = $this->checkUserIsDisable($user);
                if ($isDisable) {
                    list($code, $msg) = explode('|', config('response.msg49'));
                    return apiError($msg, $code);
                }
            } catch (\Exception $e) {
                $logContent = '异常日志：' . $e->getMessage() . '出错文件：' . $e->getFile() . '出错行号：' . $e->getLine();
                generateApiLog($logContent);
                return apiError(config('response.msg5'));
            }
            return $next($request);
        }
    }

    /**
     * 根据token获取用户
     *
     * @param $token
     *
     * @return array|bool|null|\PDOStatement|string|\think\Model
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function getUserByToken($token)
    {
        /** @var UserModel $userModel */
        $userModel = model(UserModel::class);
        $user = $userModel->alias('u')
            ->field([
                'u.id',
                'u.id as userId',
                'u.account_status as accountStatus',
                'u.disable_expire_time as disableExpireTime',
                'u.token'
            ])
            ->where('u.token', $token)
            ->find();
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
}
