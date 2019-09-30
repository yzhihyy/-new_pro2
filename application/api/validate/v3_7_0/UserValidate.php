<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/10
 * Time: 14:15
 */

namespace app\api\validate\v3_7_0;

use app\api\model\v3_7_0\UserModel;
use think\Validate;

class UserValidate extends Validate
{
    protected $message = [
        'invite_code.require' => '邀请码不能为空',
        'invite_code.length' => '邀请码长度必须为6位',
        'invite_code.inviteCodeCheck' => '邀请码不存在',
    ];

    public function sceneInviteCode()
    {
        return $this->append([
            'invite_code' => 'require|length:6|inviteCodeCheck',
        ]);
    }

    /**
     * 校验邀请码
     * @param $code
     * @return bool
     */
    protected function inviteCodeCheck($code)
    {
        $find = model(UserModel::class)->where('invite_code', $code)->value('id');
        if(!empty($find)){
            return true;
        }
        return false;
    }
}