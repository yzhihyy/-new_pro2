<?php

namespace app\api\model\v3_6_0;

use app\common\model\AbstractModel;

class UserModel extends AbstractModel
{
    protected $name = 'user';

    /**
     * 查询好友关系
     *
     * @param array $where
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function queryFriendRelationship(array $where = [])
    {
        $query = $this->alias('u')
            ->field([
                'u.id AS userId',
                'u.nickname',
                'u.phone',
                'u.avatar',
                'u.thumb_avatar AS thumbAvatar',
                'frr.id AS followId',
                'IF(frr.id AND frr.rel_type = 1, 1, 0) AS isFollow',
            ])
            ->leftJoin('follow_relation frr', "frr.from_user_id = {$where['userId']} AND frr.to_user_id = u.id")
            ->where('u.account_status = 1')
            ->where('u.is_robot', 0)
            ->whereIn('u.phone', $where['phones'])
            ->group('u.id');

        return $query->select()
            ->toArray();
    }
}
