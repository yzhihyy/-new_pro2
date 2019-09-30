<?php

namespace app\api\controller\v3_6_0\user;

use app\api\model\v3_0_0\UserModel;
use app\api\model\v3_6_0\SocialUserModel;
use app\api\Presenter;
use app\common\utils\easemob\EasemobHelper;
use Exception;

class User extends Presenter
{
    public function resetSocialPwd()
    {
        try {
            $userId = $this->getUserId();
            $model = new SocialUserModel();
            $socialUser = $model->alias('su')
                ->field([
                    'su.id',
                    'su.user_id',
                    'su.hx_uuid',
                    'su.hx_username',
                    'su.hx_nickname',
                ])
                ->where('user_id', '=', $userId)
                ->find();
            $easemobHelper = new EasemobHelper();
            $password = generateEasemobPassword();
            if (empty($socialUser)) {
                // 注册环信
                $user_prefix = config('easemob.user_prefix');
                $username = $user_prefix . 'user_' . $userId;
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
                    $model = new SocialUserModel();
                    $model::create($data);
                } else {
                    return apiError('注册环信用户失败');
                }
            } else {
                // 修改环信密码
                $result = $easemobHelper->resetPassword($socialUser['hx_username'], $password);
                if ($result) {
                    $socialUser->save([
                        'hx_password' => $password
                    ]);
                } else {
                    return apiError('重置环信用户密码失败');
                }
            }
            // 获取用户头像和昵称
            $userModel = new UserModel();
            $user = $userModel->alias('u')
                ->field([
                    'u.nickname',
                    'u.avatar',
                    'u.thumb_avatar'
                ])
                ->find($userId);
            // 返回环信
            $data = [
                'social_user_id' => $socialUser['id'],
                'social_hx_uuid' => $socialUser['hx_uuid'],
                'social_hx_username' => $socialUser['hx_username'],
                'social_hx_nickname' => $socialUser['hx_nickname'],
                'social_hx_password' => $password,
                'nickname' => $user['nickname'],
                'avatar' => getImgWithDomain($user['avatar']),
                'thumb_avatar' => getImgWithDomain($user['thumb_avatar']),
            ];
            return apiSuccess($data);
        } catch (Exception $e) {
            $logContent = '重置环信用户密码接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}
