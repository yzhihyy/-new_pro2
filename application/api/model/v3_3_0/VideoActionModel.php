<?php

namespace app\api\model\v3_3_0;

use app\common\model\AbstractModel;

class VideoActionModel extends AbstractModel
{
    protected $name = 'video_action';

    /**
     * 获取我喜欢的视频列表.
     *
     * @param array $where
     *
     * @throws \Exception
     *
     * @return array|\PDOStatement|string|\think\Collection
     */
    public function getMyJoyVideoList($where)
    {
        $query = $this->alias('va')
            ->join('video v', 'va.video_id = v.id and v.status = 1 and v.visible = 1 and v.audit_status = 1')
            ->join('user u', 'v.user_id = u.id')
            ->leftJoin('shop s', 'v.shop_id = s.id')
            ->leftJoin('follow_relation fr', "fr.rel_type = 1 and fr.from_user_id = '{$where['userId']}' 
            and (IF(v.shop_id, fr.to_shop_id = s.id, fr.to_user_id = u.id))")
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
                '1 as videoIsLike', // 是否点赞
                'v.is_top as isTop', // 是否置顶
                'if(v.shop_id, s.shop_image, u.thumb_avatar) as avatar', // 头像
                'if(v.shop_id, s.shop_name, u.nickname) as nickname', // 昵称
                'if(v.shop_id, 2, 1) as videoType', // 视频归属，1用户，2店铺
                'v.location',
                'v.location AS videoLocation',
                'v.generate_time as generateTime', // 视频发布时间
            ])
            ->where([
                'va.action_type' => 1,
                'va.status' => 1,
                'va.user_id' => $where['userId'],
            ])
            ->order([
                'va.generate_time' => 'desc',
                'v.is_top' => 'desc'
            ]);
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
}
