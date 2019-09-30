<?php

namespace app\api\model\v3_3_0;

use app\common\model\AbstractModel;

class TopicModel extends AbstractModel
{
    protected $name = 'topic';

    /**
     * 获取话题列表
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTopicList(array $where)
    {
        $query = $this->alias('t')
            ->field([
                't.id AS topicId',
                'GROUP_CONCAT(tvr.video_id ORDER BY tvr.sort DESC, tvr.generate_time DESC) AS videoIds',
                'COUNT(1) AS videoCount',
            ])
            ->join('topic_video_relation tvr', 'tvr.topic_id = t.id')
            ->join('video v', 'v.id = tvr.video_id AND v.status = 1 AND v.visible = 1')
            ->join('user u', 'u.id = v.user_id AND u.account_status = 1')
            ->leftJoin('shop s', 's.id = v.shop_id')
            ->where('t.delete_status', 0)
            // 店铺被禁用或下线后,不显示店铺视频
            ->where('IF(s.id, s.account_status = 1 AND s.online_status = 1, 1)')
            ->group('t.id')
            ->having('videoCount > 0');

        // 是否推荐
        if (isset($where['isRecommend'])) {
            $query->where('t.is_recommend', $where['isRecommend']);
        }

        // 指定城市
        if (isset($where['cityId'])) {
            $query->where('v.city_id', $where['cityId']);
        }

        // 获取指定标签下的视频或随记
        if (isset($where['tagIds'])) {
            $query->join('video_tag_relation vtr', "vtr.video_id = v.id AND vtr.tag_id IN ({$where['tagIds']})");
        }

        return $query->orderRand()
                ->limit($where['limit'])
                ->select();
    }

    /**
     * 获取指定话题下的视频和随记
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTopicVideoList(array $where)
    {
        $query = $this->alias('t')
            ->field([
                'tvr.id AS topicVideoRelationId',
                'tvr.sort',
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
                'v.relation_shop_id AS relationShopId',
                's.wechat AS relatedWechat',
                's.qq AS relatedQq',
                's.setting',
                'v.audit_status AS auditStatus',
            ])
            ->join('topic_video_relation tvr', 'tvr.topic_id = t.id')
            ->join('video v', 'v.id = tvr.video_id AND v.status = 1 AND v.visible = 1')
            ->join('user u', 'u.id = v.user_id AND u.account_status = 1')
            ->leftJoin('shop s', 's.id = v.shop_id')
            ->where('t.id', $where['topicId'])
            ->where('t.delete_status', 0)
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

        return $query->order('tvr.sort', 'DESC')
            ->order('tvr.generate_time', 'DESC')
            ->limit($where['page'] * $where['limit'], $where['limit'])
            ->select();
    }
}
