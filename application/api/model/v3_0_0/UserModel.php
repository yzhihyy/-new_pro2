<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class UserModel extends AbstractModel
{
    protected $name = 'user';

    /**
     * 获取机器人用户
     *
     * @param array $where
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRobotUsers(array $where = [])
    {
        $query = $this->alias('u')
            ->field([
                'u.id AS userId',
                'u.nickname',
            ])
            ->where('u.is_robot', 1)
            ->limit($where['limit']);

        $type = $where['type'] ?? 1;
        switch ($type) {
            // 获取机器人用户
            case 1:
                break;
            // 获取未关注的机器人用户
            case 2:
                $query->leftJoin('follow_relation fr', "fr.to_user_id = u.id AND fr.from_user_id = {$where['userId']}")
                    ->where('fr.id', 'null');
                break;
            // 获取未关注指定用户的机器人用户
            case 3:
                $query->leftJoin('follow_relation fr', "fr.from_user_id = u.id AND fr.to_user_id = {$where['userId']}")
                    ->where('fr.id', 'null');
                break;
            // 获取未关注指定商家的机器人用户
            case 4:
                $query->leftJoin('follow_relation fr', "fr.from_user_id = u.id AND fr.to_shop_id = {$where['shopId']}")
                    ->where('fr.id', 'null');
                break;
            default:
                return [];
        }

        $robotMapName = model(RobotMapModel::class)->getName();
        $randScopeSql = $this->name($robotMapName)->field(['MAX(id) - MIN(id)'])->buildSql();
        $minSql = $this->name($robotMapName)->field(['MIN(id)'])->buildSql();
        $randQuery = $this->name($robotMapName)
            ->fieldRaw("ROUND(RAND() * {$randScopeSql} + {$minSql} - {$where['limit']}) AS rnd")
            ->limit($where['limit'])
            ->buildSql();
        $subQuery = $this->name($robotMapName)
            ->alias('pm')
            ->join([
                $randQuery => 'pmr'
            ], 'pm.id = pmr.rnd')
            ->limit($where['limit'])
            ->buildSql();
        $query->join([
            $subQuery => 'pm'
        ], 'u.id = pm.user_id');

        return $query->select()
            ->toArray();
    }

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
                'COUNT(DISTINCT (v.id)) AS videoCount',
                'COUNT(DISTINCT (fr.id)) AS fansCount',
                'COUNT(DISTINCT (va.id)) AS likeCount',
                'frr.id AS followId',
                'IF(frr.id AND frr.rel_type = 1, 1, 0) AS isFollow',
            ])
            ->leftJoin('video v', 'v.user_id = u.id AND v.shop_id = 0 AND v.status = 1')
            ->leftJoin('follow_relation fr', 'fr.to_user_id = u.id AND fr.rel_type = 1')
            ->leftJoin('video_action va', 'va.video_id = v.id AND va.action_type = 1 AND va.status = 1')
            ->leftJoin('follow_relation frr', "frr.from_user_id = {$where['userId']} AND frr.to_user_id = u.id AND frr.rel_type IN(1,2)")
            ->where('u.account_status = 1')
            ->where('u.is_robot', 0)
            ->whereIn('u.phone', $where['phones'])
            ->group('u.id');

        return $query->select()
            ->toArray();
    }

    /**
     * 获取用户的作品数量.
     *
     * @param array $userIdArr
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \Exception
     */
    public function getUserVideoCount($userIdArr)
    {
        $query = $this->alias('u')
            ->leftJoin('video v', 'v.user_id = u.id')
            ->field([
                'u.id as userId',
                'count(distinct v.id, v.shop_id = 0 and v.visible = 1 and v.status = 1 or null) as videoCount'
            ])
            ->where([
                'u.id' => $userIdArr
            ])
            ->group('u.id');
        return $query->select();
    }

    /**
     * 获取用户的粉丝数量.
     * @param array $userIdArr
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \Exception
     */
    public function getUserFansCount($userIdArr)
    {
        $query = $this->alias('u')
            ->leftJoin('follow_relation fr', 'fr.to_shop_id = 0 and fr.to_user_id = u.id and fr.rel_type = 1')
            ->join('user _u', 'fr.from_user_id = _u.id and _u.account_status = 1')
            ->field([
                'u.id as userId',
                'count(fr.id) as fansCount',
            ])
            ->where([
                'u.id' => $userIdArr,
            ])
            ->group('u.id');
        return $query->select();
    }

    /**
     * 获取用户的关注数量.
     * @param array $userIdArr
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \Exception
     */
    public function getUserFollowCount($userIdArr)
    {
        $query = $this->alias('u')
            ->leftJoin('follow_relation fr', "fr.from_user_id = u.id and fr.rel_type = 1 and 
            ((fr.to_user_id > 0 and fr.to_shop_id = 0) or (fr.to_user_id = 0 and fr.to_shop_id > 0))")
            ->field([
                'u.id as userId',
                'count(fr.id) as followCount',
            ])
            ->where([
                'u.id' => $userIdArr,
            ])
            ->group('u.id');
        return $query->select();
    }

    /**
     * 获取用户的获赞数.
     * @param array $userIdArr
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \Exception
     */
    public function getUserLikeCount($userIdArr)
    {
        $query = $this->alias('u')
            ->leftJoin('video v', 'v.user_id = u.id and v.shop_id = 0 and v.status = 1')
            ->field([
                'u.id as userId',
                'sum(v.like_count) as likeCount'
            ])
            ->where([
                'u.id' => $userIdArr
            ])
            ->group('u.id');
        return $query->select();
    }

    /**
     * 判断是否在黑名单.
     *
     * @param array $where
     *
     * @return bool
     * @throws \Exception
     */
    public function isInBlackList($where)
    {
        if (!(isset($where['loginUserId']) && $where['loginUserId'])) {
            return false;
        }
        if ($where['type'] == 1) {
            $map = [
                'ubl.user_id' => $where['loginUserId'],
                'ubl.to_user_id' => $where['userId'],
                'ubl.to_shop_id' => 0,
                'ubl.status' => 1,
            ];
        } else {
            $map = [
                'ubl.user_id' => $where['loginUserId'],
                'ubl.to_user_id' => 0,
                'ubl.to_shop_id' => $where['shopId'],
                'ubl.status' => 1,
            ];
        }
        $item = $this->name('user_black_list')
            ->alias('ubl')
            ->where($map)
            ->find();
        return !empty($item);
    }
}
