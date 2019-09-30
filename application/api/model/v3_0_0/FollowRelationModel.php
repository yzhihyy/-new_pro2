<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class FollowRelationModel extends AbstractModel
{
    protected $name = 'follow_relation';

    /**
     * 获取用户关注的商家列表.
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \Exception
     */
    public function getUserFollowShopList($where)
    {
        $query = $this->alias('fr')
            ->join('shop s', 'fr.to_shop_id = s.id')
            ->leftJoin('video v', 's.id = v.shop_id')
            ->field([
                's.id as shopId', // 店铺ID
                's.shop_name as shopName', // 店铺名称
                's.shop_thumb_image as shopThumbImage', // 店铺logo
                's.shop_address_poi as shopAddressPoi', // 店铺周边
                'count(v.id and v.status = 1 and v.visible = 1 or null) as videoCount', // 视频数量
            ])
            ->where([
                'fr.from_user_id' => $where['userId'],
                'fr.rel_type' => 1,
                's.account_status' => 1,
                's.online_status' => 1
            ])
            ->group('fr.to_shop_id');
        return $query->order('fr.generate_time', 'desc')->limit($where['page'] * $where['limit'], $where['limit'])->select();
    }

    /**
     * 我的粉丝列表
     *
     * @param array $where
     *
     * @return array
     * @throws \Exception
     */
    public function getMyFansList($where)
    {
        $query = $this->alias('fr')
            ->join('user u', 'fr.from_user_id = u.id')
            ->field([
                'u.id as userId',
                'u.nickname',
                'u.avatar',
                'u.thumb_avatar as thumbAvatar',
                'fr.generate_time as followTime'
            ])
            ->where('fr.rel_type', 1)
            ->order('fr.generate_time', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);

        if (isset($where['userId']) && $where['userId']) {
            $query->where('fr.to_user_id', $where['userId']);
        }

        if (isset($where['shopId']) && $where['shopId']) {
            $query->where('fr.to_shop_id', $where['shopId']);
        }

        if (isset($where['newFollow']) && $where['newFollow']) {
            $query->where('fr.is_new_follow', 1);
        }

        return $query->select()->toArray();
    }

    /**
     * 获取用户关注的用户列表.
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \Exception
     */
    public function getUserFollowUserList($where)
    {
        $query = $this->alias('fr')
            ->join('user u', 'fr.to_user_id = u.id')
            ->leftJoin('social_user su', 'su.user_id = u.id')
            ->field([
                'u.id as userId',
                'u.thumb_avatar as avatar',
                'u.nickname',
                'u.phone',
                'su.id as social_user_id',
                'su.hx_uuid as social_hx_uuid',
                'su.hx_username as social_hx_username',
                'su.hx_nickname as social_hx_nickname',
                'su.hx_password as social_hx_password',
            ])
            ->where([
                'fr.from_user_id' => $where['userId'],
                'fr.rel_type' => 1,
                'u.account_status' => 1
            ])
            ->group('fr.to_user_id');
        if (isset($where['selfUserId'])) {
            $query->leftJoin('follow_relation _fr_', "_fr_.from_user_id = '{$where['selfUserId']}' and _fr_.to_user_id = u.id and _fr_.rel_type = 1")
                ->field('if(_fr_.id, 1, 0) as isFollow');
        } else {
            $query->field('0 as isFollow');
        }
        return $query->order('fr.generate_time', 'desc')->limit($where['page'] * $where['limit'], $where['limit'])->select();
    }

    /**
     * 获取用户的粉丝列表.
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \Exception
     */
    public function getUserFansList($where)
    {
        $query = $this->alias('fr')
            ->join('user u', 'fr.from_user_id = u.id')
            ->leftJoin('follow_relation _fr_', "_fr_.from_user_id = '{$where['myUserId']}' and _fr_.to_user_id = u.id and _fr_.rel_type = 1")
            ->field([
                'u.id as userId',
                'u.thumb_avatar as avatar',
                'u.nickname',
                'u.phone',
                'if(_fr_.id, 1, 0) as isFollow', // 是否已关注该粉丝
            ])
            ->where([
                'fr.to_user_id' => $where['userId'],
                'fr.to_shop_id' => 0,
                'fr.rel_type' => 1,
                'u.account_status' => 1
            ])
            ->group('fr.from_user_id')
            ->order('fr.generate_time', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);

        return $query->select();
    }

    /**
     * 统计我关注的用户数量和店铺数量.
     *
     * @param array $where
     *
     * @return array
     * @throws \Exception
     */
    public function countUserFollow($where)
    {
        // 统计我关注的店铺数量
        $shopTotal = $this->alias('fr')
            ->join('shop s', 'fr.to_shop_id = s.id and fr.to_user_id = 0')
            ->where([
                'fr.from_user_id' => $where['userId'],
                'fr.rel_type' => 1,
                's.account_status' => 1,
                's.online_status' => 1
            ])
            ->group('fr.id')
            ->select()
            ->count();
        // 统计我关注的用户数量
        $userTotal = $this->alias('fr')
            ->join('user u', 'fr.to_user_id = u.id and fr.to_shop_id = 0')
            ->where([
                'fr.from_user_id' => $where['userId'],
                'fr.rel_type' => 1,
                'u.account_status' => 1
            ])
            ->group('fr.id')
            ->select()
            ->count();
        return [
            'user_total' => $userTotal,
            'shop_total' => $shopTotal
        ];
    }

    /**
     * 获取用户(商家)的粉丝极光ID(不包含机器人)
     *
     * @param array $where
     *
     * @return array
     */
    public function getFansRegistrationId(array $where)
    {
        $query = $this->alias('fr')
            ->join('user u', 'fr.from_user_id = u.id AND u.account_status = 1 AND u.is_robot = 0')
            ->where('fr.rel_type', 1);

        $type = $where['type'] ?? 1;
        switch ($type) {
            // 获取用户粉丝
            case 1:
                $query->where('fr.to_user_id', $where['id']);
                break;
            // 获取商家粉丝
            case 2:
                $query->where('fr.to_shop_id', $where['id']);
                break;
            default:
                return [];
        }

        return $query->column('u.registration_id');
    }
}
