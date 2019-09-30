<?php

namespace app\api\model\v3_3_0;

use app\common\model\AbstractModel;

class VideoModel extends AbstractModel
{
    protected $name = 'video';

    /**
     * 获取首页视频列表
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getHomeVideoList(array $where)
    {
        $query = $this->alias('v')
            ->field([
                'v.id AS videoId',
                'v.type',
                'v.title AS videoTitle',
                'v.cover_url AS coverUrl',
                'v.video_url AS videoUrl',
                'v.video_width AS videoWidth',
                'v.video_height AS videoHeight',
                'v.play_count AS playCount',
                'IF(v.shop_id AND s.id, s.shop_name, u.nickname) AS nickname',
                'IF(v.shop_id AND s.id, s.shop_thumb_image, u.thumb_avatar) AS avatar',
            ])
            ->join('user u', 'u.id = v.user_id AND u.account_status = 1')
            ->leftJoin('shop s', 's.id = v.shop_id');

        $type = $where['type'] ?? 1;
        switch ($type) {
            // 首页 - 推荐
            case 1:
                $query->whereIn('v.id', $where['videoIds']);
                break;
            // 首页 - 地区
            case 2:
                $query->where('v.city_id', $where['cityId'])
                    ->order('v.generate_time', 'DESC')
                    ->order('v.id', 'DESC')
                    ->limit($where['page'] * $where['limit'], $where['limit']);
                break;
            default:
                return [];
        }

        // 获取指定标签下的视频或随记
        if (isset($where['tagIds'])) {
            $query->join('video_tag_relation vtr', "vtr.video_id = v.id AND vtr.tag_id IN ({$where['tagIds']})");
        }

        return $query->where('v.status', 1)
            ->where('v.audit_status', 1)
            ->where('v.visible', 1)
            // 店铺被禁用或下线后,不显示店铺视频
            ->where('IF(s.id, s.account_status = 1 AND s.online_status = 1, 1)')
            ->select();
    }

    /**
     * 获取视频或随记
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getVideoList(array $where)
    {
        $query = $this->alias('v')
            ->field([
                'v.id AS videoId',
                'v.user_id AS videoUserId',
                'IF(v.shop_id, 2, 1) AS videoType',
                'v.type',
                'v.title AS videoTitle',
                'v.content AS videoContent',
                'v.cover_url AS coverUrl',
                'v.video_url AS videoUrl',
                'v.video_width AS videoWidth',
                'v.video_height AS videoHeight',
                'v.like_count AS likeCount',
                'v.comment_count AS commentCount',
                'v.share_count AS shareCount',
                'v.location AS videoLocation',
                'v.generate_time AS generateTime',
                's.id AS shopId',
                'IF(v.shop_id AND s.id, s.shop_name, u.nickname) AS nickname',
                'IF(v.shop_id AND s.id, s.shop_thumb_image, u.thumb_avatar) AS avatar',
                'u.gender',
                'u.age',
                'v.relation_shop_id AS relationShopId',
                's.qq',
                's.wechat',
                's.setting',
                'v.audit_status AS auditStatus',
            ])
            ->join('user u', 'u.id = v.user_id AND u.account_status = 1')
            ->leftJoin('shop s', 's.id = v.shop_id')
            ->where('v.status', 1)
            ->where('v.visible', 1)
            // 店铺被禁用或下线后,不显示店铺视频
            ->where('IF(s.id, s.account_status = 1 AND s.online_status = 1, 1)');

        if(isset($where['audit_status'])){
            $query->where('v.audit_status', $where['audit_status']);
        }
        // 获取视频或随记
        if (in_array($where['type'], [1, 2])) {
            $query->where('v.type', $where['type']);
        }

        // 获取指定标签下的视频或随记
        if (isset($where['tagIds'])) {
            $query->join('video_tag_relation vtr', "vtr.video_id = v.id AND vtr.tag_id IN ({$where['tagIds']})");
        }

        // 获取指定随记详情
        if ($where['mode'] == 3) {
            return $query->where('v.id', $where['videoId'])
                ->limit(1)
                ->select();
        } else {
            $query->where('v.id', '<=', $where['videoId']);
        }

        // 获取指定城市下的视频和随记
        if (isset($where['cityId']) && !empty($where['cityId'])) {
            $query->where('v.city_id', $where['cityId']);
        }

        // 若用户已登录
        if (!empty($where['userId'])) {
            // 关注|点赞
            $query->field([
                'IF(fr.id, 1, 0) AS isFollow',
                'IF(va.id, 1, 0) AS videoIsLike',
            ])
                ->leftJoin('follow_relation fr', "fr.from_user_id = {$where['userId']} AND fr.rel_type = 1 AND IF(v.shop_id > 0, v.shop_id = fr.to_shop_id, v.user_id = fr.to_user_id)")
                ->leftJoin('video_action va', "va.action_type = 1 AND va.video_id = v.id AND va.user_id = {$where['userId']} AND va.status = 1");

            // 拉黑过滤
            $query->leftJoin('user_black_list ubl', "ubl.user_id = {$where['userId']} AND IF(v.shop_id > 0, ubl.to_shop_id = v.shop_id, ubl.to_user_id = v.user_id)")
                ->where('IF(ubl.id, ubl.status = 2, 1)');
        }

        return $query->order('v.generate_time', 'DESC')
            ->order('v.id', 'DESC')
            ->limit($where['page'] * $where['limit'], $where['limit'])
            ->select();
    }

    /**
     * 获取我的视频列表.
     *
     * @param array $where
     *
     * @throws \Exception
     *
     * @return array
     */
    public function getMyVideoList($where)
    {
        $query = $this->alias('v')
            ->join('user u', 'v.user_id = u.id')
            ->leftJoin('shop s', 's.user_id = u.id and s.account_status = 1 and s.online_status = 1')
            ->leftJoin('follow_relation fr', "fr.rel_type = 1 and fr.from_user_id = '{$where['userId']}' 
            and (IF(v.shop_id, fr.to_shop_id = s.id, fr.to_user_id = u.id))")
            ->leftJoin('video_action va', "va.action_type = 1 and va.video_id = v.id and va.status = 1 and va.user_id = '{$where['userId']}'")
            ->field([
                'v.type', // 类型，1视频，2随记
                'v.id as videoId', // 视频ID
                'v.title as videoTitle', // 视频标题
                'v.content AS videoContent', // 视频简介
                'v.user_id as videoUserId', // 发视频用户ID
                'v.cover_url as coverUrl', // 封面
                'v.video_url as videoUrl', // 视频地址
                'v.video_width AS videoWidth', // 视频宽度
                'v.video_height AS videoHeight', // 视频高度
                'v.like_count as likeCount', // 点赞数量
                'v.comment_count as commentCount', // 评论数量
                'v.share_count as shareCount', // 转发数量
                'v.play_count as playCount', // 浏览量
                's.id as shopId', // 店铺ID
                's.shop_image as shopImage', // 店铺图片
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                's.shop_name as shopName', // 店铺ID
                's.setting', // 关联配置
                's.qq', // qq
                's.wechat', // 微信
                's.shop_address as shopAddress', // 店铺地址
                'v.relation_shop_id AS relationShopId',
                'IF(fr.id, 1, 0) as isFollow', // 是否关注
                'IF(va.id, 1, 0) as videoIsLike', // 是否点赞
                'v.is_top as isTop', // 是否置顶
                'if(v.shop_id, s.shop_image, u.thumb_avatar) as avatar', // 头像
                'if(v.shop_id, s.shop_name, u.nickname) as nickname', // 昵称
                'if(v.shop_id, 2, 1) as videoType',  // 视频归属，1用户，2店铺
                'v.location',
                'v.location AS videoLocation',
                'v.generate_time as generateTime', // 视频发布时间
            ])
            ->where([
                'v.user_id' => $where['userId'],
                'v.shop_id' => 0,
                'v.status' => 1,
                'v.visible' => 1,
            ])
            ->order([
                'v.is_top' => 'desc',
                'v.id' => 'desc'
            ])
            ->group('v.id');
        // 筛选类型
        if (!empty($where['type'])) {
            $query->where('v.type', '=', $where['type']);
        }
        // 统计总数量
        $_query = clone $query;
        $totalCount = $_query->select()->count();
        // 分页
        $videoList = $query->limit($where['page'] * $where['limit'], $where['limit'])->select();
        return [
            'totalCount' => $totalCount,
            'videoList' => $videoList
        ];
    }

    /**
     * 获取用户的视频列表.
     *
     * @param array $where
     *
     * @throws \Exception
     *
     * @return array
     */
    public function getUserVideoList($where)
    {
        // 获取视频列表
        $query = $this->alias('v')
            ->field([
                'v.type', // 类型，1视频，2随记
                'v.id as videoId', // 视频ID
                'v.title as videoTitle', // 视频标题
                'v.content AS videoContent', // 视频简介
                'v.user_id as videoUserId', // 发视频用户ID
                'v.cover_url as coverUrl', // 封面
                'v.video_url as videoUrl', // 视频地址
                'v.video_width AS videoWidth', // 视频宽度
                'v.video_height AS videoHeight', // 视频高度
                'v.like_count as likeCount', // 点赞数量
                'v.comment_count as commentCount', // 评论数量
                'v.share_count as shareCount', // 转发数量
                'v.play_count as playCount', // 浏览量
                's.id as shopId', // 店铺ID
                's.shop_image as shopImage', // 店铺图片
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                's.shop_name as shopName', // 店铺ID
                's.setting', // 关联配置
                's.qq', // qq
                's.wechat', // 微信
                's.shop_address as shopAddress', // 店铺地址
                'v.relation_shop_id AS relationShopId',
                'v.is_top as isTop', // 是否置顶
                'if(v.shop_id, s.shop_image, u.thumb_avatar) as avatar', // 头像
                'if(v.shop_id, s.shop_name, u.nickname) as nickname', // 昵称
                'if(v.shop_id, 2, 1) as videoType',  // 视频归属，1用户，2店铺
                'v.location', // 发布视频的地理位置
                'v.location',
                'v.location AS videoLocation',
                'v.generate_time as generateTime', // 视频发布时间
            ])
            ->join('user u', '(v.user_id = u.id and u.account_status = 1)')
            ->leftJoin('shop s', '(s.user_id = u.id and s.account_status = 1 and s.online_status = 1)');
        // 筛选类型
        if (!empty($where['type'])) {
            $query->where('v.type', '=', $where['type']);
        }
        // 当用户是登录状态下判断是否点赞过视频
        if (isset($where['loginUserId']) && !empty($where['loginUserId'])) {
            $joinCondition = "va.action_type = 1 and va.video_id = v.id and va.status = 1 and va.user_id = '{$where['loginUserId']}'";
            $query->leftJoin('video_action va', $joinCondition)
                ->field('IF(va.id, 1, 0) as videoIsLike');
        } else {
            $query->field('0 as videoIsLike');
        }
        // 当用户是登录状态下判断是否关注
        if (isset($where['loginUserId']) && !empty($where['loginUserId'])) {
            $joinCondition = "(fr.rel_type = 1 and fr.from_user_id = '{$where['loginUserId']}' 
            and IF(v.shop_id, fr.to_shop_id = s.id, fr.to_user_id = u.id))";
            $query->leftJoin('follow_relation fr', $joinCondition)
                ->field('IF(fr.id, 1, 0) as isFollow');
        } else {
            $query->field('0 as isFollow');
        }

        // 如果不是自己看自己，则过滤未审核的视频
        if (empty($where['loginUserId']) || ($where['loginUserId'] != $where['userId'])) {
            $query->where('v.audit_status', '=', 1);
        }

        // 条件筛选
        $query->where([
            'v.user_id' => $where['userId'],
            'v.shop_id' => 0,
            'v.status' => 1,
            'v.visible' => 1,
        ])
            ->order([
                'v.is_top' => 'desc',
                'v.id' => 'desc'
            ])
            ->group('v.id');
        // 统计总数量
        $_query = clone $query;
        $totalCount = $_query->select()->count();
        // 分页
        $videoList = $query->limit($where['page'] * $where['limit'], $where['limit'])->select();
        return [
            'totalCount' => $totalCount,
            'videoList' => $videoList
        ];
    }
}
