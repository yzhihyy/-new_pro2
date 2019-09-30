<?php

namespace app\api\model\v3_6_0;

use app\common\model\AbstractModel;

class VideoModel extends AbstractModel
{
    protected $name = 'video';

    /**
     * 获取店铺视频列表
     *
     * @param $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \Exception
     */
    public function getShopVideoList($where)
    {
        $query = $this->alias('v')
            ->leftJoin('shop s', 'v.shop_id = s.id and s.account_status = 1 and s.online_status = 1')
            ->join('user u', '(v.user_id = u.id and u.account_status = 1)')
            ->field([
                'v.id as videoId', // 视频ID
                'v.type', // 视频类型 1:视频,2:随记
                'v.title as videoTitle', // 视频标题
                'v.content AS videoContent', // 视频简介
                'v.user_id as videoUserId', // 视频用户ID
                'v.cover_url as coverUrl', // 封面
                'v.video_url as videoUrl', // 视频地址
                'v.like_count as likeCount', // 点赞数量
                'v.comment_count as commentCount', // 评论数量
                'v.share_count as shareCount', // 转发数量
                'v.play_count as playCount', // 浏览量
                'v.video_width as videoWidth', // 视频宽度
                'v.video_height as videoHeight', // 视频高度
                'v.is_top as isTop', // 是否置顶
                'if(v.shop_id, s.shop_image, u.thumb_avatar) as avatar', // 头像
                'if(v.shop_id, s.shop_name, u.nickname) as nickname', // 昵称
                'if(v.shop_id, 2, 1) as videoType',  // 视频归属，1用户，2店铺
                'v.location', // 发布视频的地理位置
                'v.location AS videoLocation', // 发布视频的地理位置
                'v.generate_time as generateTime', // 视频发布时间
                'v.relation_shop_id AS relationShopId', // 视频关联店铺id
                's.id as shopId', // 店铺ID
                's.shop_name as shopName', // 店铺ID
                's.shop_image as shopImage', // 店铺图片
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                's.setting', // 关联配置
                's.qq', // qq
                's.wechat', // 微信
                's.shop_address as shopAddress', // 店铺地址
                'v.audit_status as auditStatus', // 审核状态
            ])
            ->where([
                ['v.status', '=', 1],
                ['v.visible', '=', 1],
            ])
            ->whereRaw("v.shop_id = {$where['shopId']} or v.relation_shop_id = {$where['shopId']}")
            ->whereRaw("IF(v.shop_id, (s.account_status = 1 and s.online_status = 1), 1)")
            ->order([
                'v.is_top' => 'desc',
                'v.id' => 'desc',
            ])
            ->limit($where['page'] * $where['limit'], $where['limit']);
        // 筛选类型
        if (!empty($where['type'])) {
            $query->where('v.type', '=', $where['type']);
        }
        // 如果是商家看自己的店铺视频列表则不筛选已审核
        if (empty($where['seeMySelf']) or $where['seeMySelf'] == 0) {
            $query->where('v.audit_status', '=', 1);
        }
        // 获取是否点赞过，是否关注过
        if (isset($where['userId']) && $where['userId']) {
            $query->leftJoin('follow_relation fr', "fr.rel_type = 1 and fr.from_user_id = {$where['userId']} 
            and IF(v.shop_id, fr.to_shop_id = v.shop_id, fr.to_user_id = v.user_id)")
                ->leftJoin('video_action va', "va.action_type = 1 and va.video_id = v.id and va.user_id = {$where['userId']} AND va.status = 1")
                ->field([
                    'IF(fr.id, 1, 0) as isFollow', // 是否关注
                    'IF(va.id, 1, 0) as isLike', // 是否点赞
                ]);
        }
        $query->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select();
    }
}
