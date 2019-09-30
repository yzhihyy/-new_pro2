<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/20
 * Time: 9:01
 */
namespace app\api\logic\v3_7_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v3_7_0\UserModel;

class UserLogic extends BaseLogic
{
    /**
     * 邀请列表
     * @param $params
     * @return mixed
     */
    public static function userInviteList($params)
    {
        return model(UserModel::class)->alias('u')
            ->field([
                'u.phone',
                'u.generate_time as generateTime',
                'u.generate_time as installTime',
            ])
            ->where('u.bind_invite_code', $params['invite_code'])
            ->order('u.id', 'desc')
            ->limit($params['page'] * $params['limit'], $params['limit'])
            ->select()
            ->toArray();
    }
}