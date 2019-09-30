<?php

namespace app\common\traits;

use think\facade\Request;

trait RequestTrait
{
    /**
     * 获取登录认证信息
     *
     * @return null
     */
    public function getAuthInfo()
    {
        $token = Request::header('token');
        if (empty($token)) {
            return null;
        }
        try {
            $authInfo = jwtDecode($token);
            return $authInfo;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 获取用户ID
     *
     * @return null
     */
    public function getUserId()
    {
        $authInfo = $this->getAuthInfo();
        return $authInfo['userId'] ?? null;
    }
}
