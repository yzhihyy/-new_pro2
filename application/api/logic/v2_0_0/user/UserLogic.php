<?php

namespace app\api\logic\v2_0_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v2_0_0\UserModel;
use app\common\utils\string\StringHelper;

class UserLogic extends BaseLogic
{
    /**
     * 创建用户并返回实例
     *
     * @param $createData
     *
     * @return UserModel
     */
    public function createUserAndReturnInstance($createData)
    {
        $timeStr = date('Y-m-d H:i:s');
        $defaultData = [
            'nickname' => StringHelper::hidePartOfString($createData['phone']),
            'avatar' => config('parameters.new_register_user_avatar'),
            'thumb_avatar' => config('parameters.new_register_user_avatar'),
            'login_time' => $timeStr,
            'account_status' => 1,
            'generate_time' => $timeStr
        ];
        $data = array_merge($defaultData, $createData);
        /** @var UserModel $userModel */
        $userModel = model(UserModel::class);
        $user = $userModel::create($data);

        return $user;
    }
}
