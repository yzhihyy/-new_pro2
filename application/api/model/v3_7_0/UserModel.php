<?php

namespace app\api\model\v3_7_0;

use app\common\model\AbstractModel;

class UserModel extends AbstractModel
{
    protected $name = 'user';

    /**
     * 模糊搜索用户昵称
     * @param array $where
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function searchNickname(array $where = [])
    {
        $query = $this->alias('u')
            ->field([
                'u.id AS user_id',
                'u.phone',
                'u.nickname',
                'u.thumb_avatar AS avatar',
                'su.user_id AS social_user_id',
                'su.hx_uuid AS social_hx_uuid',
                'su.hx_username AS social_hx_username',
                'su.hx_nickname AS social_hx_nickname',
                'su.hx_password AS social_hx_password',
            ])
            ->leftJoin('social_user su', 'su.user_id = u.id')
            ->where('u.account_status', 1);

        if(isset($where['keyword']) && !empty($where['keyword'])) {
            $query->where('u.nickname', 'like', '%'.$where['keyword'].'%');
        }

        return $query->order('u.id', 'desc')->limit($where['page'] * $where['limit'], $where['limit'])->select();

    }
}
