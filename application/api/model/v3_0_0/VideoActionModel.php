<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class VideoActionModel extends AbstractModel
{
    protected $name = 'video_action';

    /**
     * 获取用户点赞过的视频
     *
     * @param array $where
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserLikeActionVideo(array $where = [])
    {
        return $this->alias('va')
            ->distinct(true)
            ->field([
                'va.video_id AS videoId'
            ])
            ->where([
                'va.user_id' => $where['userId'],
                'action_type' => 1
            ])
            ->select()
            ->toArray();
    }

    /**
     * 获取我喜欢的视频列表
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMyJoyVideoList($where)
    {
        $query = $this->alias('va')
            ->join('video v', 'va.video_id = v.id')
            ->join('user u', 'v.user_id = u.id')
            ->leftJoin('shop s', 'v.shop_id = s.id')
            ->leftJoin('follow_relation fr', "fr.rel_type = 1 and fr.from_user_id = '{$where['userId']}' and (IF(v.shop_id, fr.to_shop_id = s.id, fr.to_user_id = u.id))")
            ->field([
                'v.id as videoId', // 视频ID
                'v.title as videoTitle', // 视频标题
                'v.user_id as videoUserId', // 发视频用户ID
                'v.cover_url as coverUrl', // 封面
                'v.video_url as videoUrl', // 视频地址
                'v.video_width AS videoWidth', // 视频宽度
                'v.video_height AS videoHeight', // 视频高度
                'v.like_count as likeCount', // 点赞数量
                'v.comment_count as commentCount', // 评论数量
                'v.share_count as shareCount', // 转发数量
                's.id as shopId', // 店铺ID
                's.shop_image as shopImage', // 店铺图片
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                'IF(fr.id, 1, 0) as isFollow', // 是否关注
                '1 as isLike', // 是否点赞
                'v.is_top as isTop', // 是否置顶
                'u.id as videoUserId',
                'if(v.shop_id, s.shop_image, u.thumb_avatar) as avatar',
                'if(v.shop_id, s.shop_name, u.nickname) as nickname',
                'if(v.shop_id, 2, 1) as videoType',
            ])
            ->where([
                'va.action_type' => 1,
                'va.status' => 1,
                'va.user_id' => $where['userId'],
                'v.status' => 1,
                'v.visible' => 1,
                'v.type' => 1,
            ])
            ->order([
                'va.generate_time' => 'desc',
                'v.is_top' => 'desc'
            ]);
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
