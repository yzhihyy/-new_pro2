<?php

namespace app\api\logic\v3_6_0;

use app\api\logic\BaseLogic;
use app\api\model\v3_6_0\SocialUserModel;
use app\common\utils\easemob\EasemobHelper;

class EasemobLogic extends BaseLogic
{
    public function getEasemobInfo($userId)
    {
        $return = [];
        // 获取环信用户
        $model = new SocialUserModel();
        $socialUser = $model->alias('su')
            ->field([
                'su.id as social_user_id',
                'su.user_id',
                'su.hx_uuid as social_hx_uuid',
                'su.hx_username as social_hx_username',
                'su.hx_nickname as social_hx_nickname',
                'su.hx_password as social_hx_password'
            ])
            ->where('user_id', '=', $userId)
            ->find();
        // 如果环信用户不存在，则注册用户
        if (empty($socialUser['social_hx_uuid'])) {
            $easemobHelper = new EasemobHelper();
            $user_prefix = config('easemob.user_prefix');
            $username = $user_prefix . 'user_' . $userId;
            $password = generateEasemobPassword();
            $nickname = '';
            $easemobUser = $easemobHelper->authSingleRegister($username, $password, $nickname);
            if ($easemobUser) {
                // 保存环信用户
                $data = [
                    'user_id' => $userId,
                    'hx_uuid' => $easemobUser['uuid'],
                    'hx_username' => $easemobUser['username'],
                    'hx_nickname' => $nickname,
                    'hx_password' => $password,
                    'generate_time' => date('Y-m-d H:i:s')
                ];
                $socialUser = $model::create($data);
                // 返回环信用户信息
                $return = [
                    'social_user_id' => (int)$socialUser['id'] ?? 0,
                    'social_hx_uuid' => $socialUser['hx_uuid'] ?? '',
                    'social_hx_username' => $socialUser['hx_username'] ?? '',
                    'social_hx_nickname' => $socialUser['hx_nickname'] ?? '',
                    'social_hx_password' => $socialUser['hx_password'] ?? '',
                ];
            }
        } else {
            $return = [
                'social_user_id' => (int)$socialUser['social_user_id'] ?? 0,
                'social_hx_uuid' => $socialUser['social_hx_uuid'] ?? '',
                'social_hx_username' => $socialUser['social_hx_username'] ?? '',
                'social_hx_nickname' => $socialUser['social_hx_nickname'] ?? '',
                'social_hx_password' => $socialUser['social_hx_password'] ?? '',
            ];
        }
        return $return;
    }

    /**
     * 获取用户环信信息
     * @param int $userId
     * @return mixed
     * @throws
     */
    public static function getSocialUserInfo($userId = 0)
    {
        $info = model(SocialUserModel::class)
            ->field(['id', 'hx_uuid', 'hx_username', 'hx_nickname', 'hx_password'])
            ->where('user_id', $userId)
            ->find();
        $response = [
            'social_user_id' => (int)$info['id'],
            'social_hx_uuid' => $info['hx_uuid'] ?? '',
            'social_hx_username' => $info['hx_username'] ?? '',
            'social_hx_nickname' => $info['hx_nickname'] ?? '',
            'social_hx_password' => $info['hx_password'] ?? '',
        ];
        return $response;
    }
}