<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class VideoCommentModel extends AbstractModel
{
    protected $name = 'video_comment';

    /**
     * 获取用户评论过的视频
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserCommentVideo(array $where = [])
    {
        return $this->alias('vc')
            ->distinct(true)
            ->field([
                'vc.video_id AS videoId'
            ])
            ->where('vc.from_user_id', $where['userId'])
            ->select()
            ->toArray();
    }

    /**
     * 获取视频一级评论列表
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTopLevelCommentList($where = [])
    {
        $query = $this->alias('vc')
            ->field([
                'vc.video_id as videoId', // 视频ID
                'vc.id as commentId', // 评论ID
                'vc.like_count as likeCount', // 评论点赞数量
                'vc.content as content', // 评论内容
                'vc.generate_time as generateTime', // 评论日期
                'u.id as userId', // 用户ID
                's.id as shopId', // 店铺ID
                'CASE WHEN s.id > 0 THEN s.shop_name ELSE u.nickname END as nickname',
                'CASE WHEN s.id > 0 THEN s.shop_thumb_image ELSE u.thumb_avatar END as avatar',
            ])
            ->join('user u', 'u.id = vc.from_user_id')
            ->leftJoin('shop s', 's.id = vc.from_shop_id')
            ->where([
                'vc.video_id' => $where['videoId'],
                'vc.top_comment_id' => 0,
                'vc.status' => 1
            ]);

        // 该评论是否已经点赞
        if (isset($where['userId']) && $where['userId']) {
            $query->leftJoin('video_action va', "va.action_type = 1 AND va.comment_id = vc.id AND va.user_id = {$where['userId']} AND va.status = 1")
                ->field('IF(va.id, 1, 0) as isLike');
        } else {
            $query->field('0 as isLike');
        }

        $query->order('vc.like_count', 'desc')
            ->order('vc.generate_time', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);

        return $query->select()->toArray();
    }

    /**
     * 获取视频评论点赞最多或最新的一条回复
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function findOneHotCommentReply($where = [])
    {
        $subQuery = $this->alias('vc')
            ->distinct(true)
            ->field([
                'vc.video_id as videoId', // 视频ID
                'vc.id as commentId', // 评论ID
                'vc.top_comment_id as topCommentId', // 顶级评论ID
                'vc.parent_comment_id as parentCommentId', // 上级评论ID
                'vc.like_count as likeCount', // 评论点赞数量
                'vc.content as content', // 评论内容
                'vc.generate_time as generateTime', // 评论日期
                'u.id as userId', // 用户ID
                's.id as shopId', // 店铺ID
                'CASE WHEN s.id > 0 THEN s.shop_thumb_image ELSE u.thumb_avatar END as avatar',
                'CASE WHEN s.id > 0 THEN s.shop_name ELSE u.nickname END as nickname',
            ])
            ->join('user u', 'u.id = vc.from_user_id')
            ->leftJoin('shop s', 's.id = vc.from_shop_id')
            ->where('vc.status', 1)
            ->whereIn('vc.parent_comment_id', $where['commentIds'])
            ->order([
                'vc.like_count' => 'desc',
                'vc.generate_time' => 'desc'
            ]);

        // 该回复是否已经点赞
        if (isset($where['userId']) && $where['userId']) {
            $subQuery->leftJoin('video_action va', "va.action_type = 1 AND va.comment_id = vc.id AND va.user_id = {$where['userId']} AND va.status = 1")
                ->field('IF(va.id, 1, 0) as isLike');
        } else {
            $subQuery->field('0 as isLike');
        }
        $subQuery = $subQuery->buildSql();
        $query = $this->table($subQuery)
            ->alias('subQuery')
            ->field([
                'subQuery.videoId',
                'subQuery.commentId',
                'subQuery.topCommentId',
                'subQuery.parentCommentId',
                'subQuery.content',
                'subQuery.generateTime',
                'subQuery.likeCount',
                'subQuery.userId',
                'subQuery.avatar',
                'subQuery.nickname',
                'subQuery.shopId',
                'subQuery.isLike',
            ])
            ->group('subQuery.parentCommentId');

        return $query->select()->toArray();
    }

    /**
     * 获取指定评论的回复数量
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCommentReplyCount($where = [])
    {
        return $this->field(['top_comment_id as topCommentId', 'COUNT(1) as replyCount'])
            ->whereIn('top_comment_id', $where['commentIds'])
            ->group('top_comment_id')
            ->select()
            ->toArray();
    }

    /**
     * 获取视频评论下的回复列表
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCommentReplyList($where = [])
    {
        $query = $this->alias('vc')
            ->field([
                'vc.id as replyId',
                'vc.top_comment_id as topCommentId', // 顶级评论ID
                'vc.parent_comment_id as parentCommentId', // 上级评论ID
                'vc.like_count as likeCount', // 评论点赞数量
                'vc.content as content', // 评论内容
                'vc.generate_time as generateTime', // 评论日期
                'u.id as fromUserId',
                'uu.id as toUserId',
                'CASE WHEN s.id > 0 THEN s.shop_name ELSE u.nickname END as fromNickname',
                'CASE WHEN s.id > 0 THEN s.shop_thumb_image ELSE u.thumb_avatar END as fromAvatar',
                'CASE WHEN ss.id > 0 THEN ss.shop_name ELSE uu.nickname END as toNickname',
                'CASE WHEN ss.id > 0 THEN ss.shop_thumb_image ELSE uu.thumb_avatar END as toAvatar',
            ])
            ->join('user u', 'u.id = vc.from_user_id')
            ->join('user uu', 'uu.id = vc.to_user_id')
            ->leftJoin('shop s', 's.id = vc.from_shop_id')
            ->leftJoin('shop ss', 'ss.id = vc.to_shop_id')
            ->where([
                ['vc.id', '<>', $where['hotReplyId']],
                ['vc.top_comment_id', '=', $where['commentId']],
                ['vc.status', '=', 1]
            ]);

        // 该回复是否已经点赞
        if (isset($where['userId']) && $where['userId']) {
            $query->leftJoin('video_action va', "va.action_type = 1 AND va.comment_id = vc.id AND va.user_id = {$where['userId']} AND va.status = 1")
                ->field('IF(va.id, 1, 0) as isLike');
        } else {
            $query->field('0 as isLike');
        }

        $query->order('vc.like_count', 'desc')
            ->order('vc.generate_time', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);

        return $query->select()->toArray();
    }

    /**
     * 我的评论回复列表.
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMyCommentAndReplyList($where)
    {
        // 获取别人回复我的评论列表
        $query1 = $this->alias('vc')
            ->join('video v', 'vc.video_id = v.id')
            ->join('user u', 'vc.from_user_id = u.id')
            ->leftJoin('shop s', 'vc.from_shop_id = s.id and s.account_status = 1 and s.online_status = 1')
            ->field([
                'u.id as userId', // 用户ID
                'if(s.id, s.shop_name, u.nickname) as nickname', // 用户昵称
                'if(s.id, s.shop_image, u.avatar) as avatar', // 用户头像
                'if(s.id, s.shop_thumb_image, u.thumb_avatar) as thumbAvatar', // 用户头像缩略图
                'v.cover_url as coverUrl', // 视频封面
                '1 as type', // 类型，1评论回复，2评论点赞，3评论视频，4点赞视频
                'vc.content', // 回复内容
                'vc.generate_time as generateTime',
                'v.id as videoId',
                'vc.id as commentId',
            ])
            ->where([
                //['v.status', '=', 1],
                ['vc.from_user_id', '<>', $where['userId']], // 自己回复自己的评论不显示
                ['vc.to_user_id', '=', $where['userId']],
                ['vc.to_shop_id', '=', 0],
                ['vc.status', '=', 1],
                ['vc.top_comment_id', '>', 0],
            ])
            ->group('vc.id')
            ->buildSql();
        // 获取别人点赞我的评论列表
        $query2 = $this->alias('vc')
            ->join('video v', 'vc.video_id = v.id')
            ->leftJoin('video_action va', 'va.action_type = 1 and va.comment_id = vc.id and va.status = 1')
            ->join('user u', 'va.user_id = u.id')
            ->leftJoin('shop s', 's.user_id = u.id and s.account_status = 1 and s.online_status = 1')
            ->field([
                'u.id as userId', // 用户ID
                'if(s.id, s.shop_name, u.nickname) as nickname', // 用户昵称
                'if(s.id, s.shop_image, u.avatar) as avatar', // 用户头像
                'if(s.id, s.shop_thumb_image, u.thumb_avatar) as thumbAvatar', // 用户头像缩略图
                'v.cover_url as coverUrl', // 视频封面
                '2 as type', // 类型，1评论回复，2评论点赞，3评论视频，4点赞视频
                'vc.content', // 回复内容
                'va.generate_time as generateTime',
                'v.id as videoId',
                'vc.id as commentId',
            ])
            ->where([
                //['v.status', '=', 1],
                ['vc.from_user_id', '=', $where['userId']],
                ['vc.status', '=', 1],
                ['va.user_id', '<>', $where['userId']], // 点赞自己的评论不显示
            ])
            ->group('va.id')
            ->fetchSql()
            ->select();
        // 获取别人评论我的视频列表
        $query3 = $this->alias('vc')
            ->join('video v', 'vc.video_id = v.id and v.shop_id = 0')
            ->join('user u', 'vc.from_user_id = u.id')
            ->leftJoin('shop s', 'vc.from_shop_id = s.id and s.account_status = 1 and s.online_status = 1')
            ->field([
                'u.id as userId', // 用户ID
                'if(s.id, s.shop_name, u.nickname) as nickname', // 用户昵称
                'if(s.id, s.shop_image, u.avatar) as avatar', // 用户头像
                'if(s.id, s.shop_thumb_image, u.thumb_avatar) as thumbAvatar', // 用户头像缩略图
                'v.cover_url as coverUrl', // 视频封面
                '3 as type', // 类型，1评论回复，2评论点赞，3评论视频，4点赞视频
                'vc.content', // 回复内容
                'vc.generate_time as generateTime',
                'v.id as videoId',
                'vc.id as commentId',
            ])
            ->where([
                //['v.status', '=', 1],
                ['v.user_id', '=', $where['userId']],
                ['vc.from_user_id', '<>', $where['userId']], // 自己评论自己的视频不显示
                ['vc.to_user_id', '=', $where['userId']],
                ['vc.status', '=', 1],
                ['vc.top_comment_id', '=', 0],
            ])
            ->group('vc.id')
            ->fetchSql()
            ->select();
        // 获取别人点赞我的视频
        $query4 = $this->name('video')
            ->alias('v')
            ->leftJoin('video_action va', 'va.action_type = 1 and va.video_id = v.id and va.status = 1')
            ->join('user u', 'va.user_id = u.id')
            ->leftJoin('shop s', 's.user_id = u.id and s.account_status = 1 and s.online_status = 1')
            ->field([
                'u.id as userId', // 用户ID
                'if(s.id, s.shop_name, u.nickname) as nickname', // 用户昵称
                'if(s.id, s.shop_image, u.avatar) as avatar', // 用户头像
                'if(s.id, s.shop_thumb_image, u.thumb_avatar) as thumbAvatar', // 用户头像缩略图
                'v.cover_url as coverUrl', // 视频封面
                '4 as type', // 类型，1评论回复，2评论点赞，3评论视频，4点赞视频
                'null as content', // 回复内容
                'va.generate_time as generateTime',
                'v.id as videoId',
                '0 as commentId',
            ])
            ->where([
                //['v.status', '=', 1],
                ['v.user_id', '=', $where['userId']],
                ['v.shop_id', '=', 0],
                ['va.user_id', '<>', $where['userId']], // 自己点赞自己的视频不显示
            ])
            ->group('va.id')
            ->fetchSql()
            ->select();
        $query = $this->field('*')
            ->table($query1)
            ->alias('query1')
            ->union($query2)
            ->union($query3)
            ->union($query4)
            ->order([
                'generateTime' => 'desc'
            ])
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select();
    }

    /**
     * 统计我的评论回复未读数量.
     *
     * @param array $where
     *
     * @return int
     * @throws \Exception
     */
    public function countMyCommentAndReplyOfUnread($where)
    {
        // 统计别人回复我的评论列表未读数量
        $count1 = $this->alias('vc')
            ->join('video v', 'vc.video_id = v.id')
            ->join('user u', 'vc.from_user_id = u.id')
            ->field('vc.id')
            ->where([
                ['vc.from_user_id', '<>', $where['userId']], // 自己回复自己的评论不统计
                ['vc.to_user_id', '=', $where['userId']],
                ['vc.to_shop_id', '=', 0],
                ['vc.status', '=', 1],
                ['vc.top_comment_id', '>', 0],
                ['vc.read_status', '=', 0]
            ])
            ->group('vc.id')
            ->count();
        // 统计别人点赞我的评论列表未读数量
        $count2 = $this->alias('vc')
            ->join('video v', 'vc.video_id = v.id')
            ->leftJoin('video_action va', 'va.action_type = 1 and va.comment_id = vc.id and va.status = 1')
            ->join('user u', 'va.user_id = u.id')
            ->field('va.id')
            ->where([
                ['vc.from_user_id', '=', $where['userId']],
                ['vc.status', '=', 1],
                ['va.user_id', '<>', $where['userId']], // 自己点赞自己的评论不统计
                ['va.read_status', '=', 0]
            ])
            ->group('va.id')
            ->count();
        // 统计别人评论我的视频列表未读数量
        $count3 = $this->alias('vc')
            ->join('video v', 'vc.video_id = v.id and v.shop_id = 0')
            ->join('user u', 'vc.from_user_id = u.id')
            ->field('vc.id')
            ->where([
                ['v.user_id', '=', $where['userId']],
                ['vc.from_user_id', '<>', $where['userId']], // 自己评论自己的视频不统计
                ['vc.to_user_id', '=', $where['userId']],
                ['vc.status', '=', 1],
                ['vc.top_comment_id', '=', 0],
                ['vc.read_status', '=', 0],
            ])
            ->group('vc.id')
            ->count();
        // 统计别人点赞我的视频列表未读数量
        $count4 = $this->name('video')
            ->alias('v')
            ->leftJoin('video_action va', 'va.action_type = 1 and va.video_id = v.id and va.status = 1')
            ->join('user u', 'va.user_id = u.id')
            ->leftJoin('shop s', 's.user_id = u.id and s.account_status = 1 and s.online_status = 1')
            ->field('va.id')
            ->where([
                ['v.user_id', '=', $where['userId']],
                ['v.shop_id', '=', 0],
                ['va.user_id', '<>', $where['userId']], // 自己点赞自己的视频不统计
                ['va.read_status', '=', 0]
            ])
            ->group('va.id')
            ->count();
        $count = array_sum([$count1, $count2, $count3, $count4]);
        return $count;
    }

    /**
     * 设置我的评论回复列表为已读
     *
     * @param $where
     */
    public function setMyCommentAndReplyHasRead($where)
    {
        // 别人回复我的评论列表
        $this->alias('vc')
            ->join('video v', 'vc.video_id = v.id')
            ->where([
                //['v.status', '=', 1],
                ['vc.from_user_id', '<>', $where['userId']], // 自己回复自己的不显示
                ['vc.to_user_id', '=', $where['userId']],
                ['vc.to_shop_id', '=', 0],
                ['vc.status', '=', 1],
                ['vc.top_comment_id', '>', 0],
            ])
            ->update([
                'vc.read_status' => 1,
            ]);
        // 别人点赞我的评论列表
        $this->alias('vc')
            ->join('video v', 'vc.video_id = v.id')
            ->leftJoin('video_action va', 'va.action_type = 1 and va.comment_id = vc.id and va.status = 1')
            ->where([
                //['v.status', '=', 1],
                ['vc.from_user_id', '=', $where['userId']],
                ['vc.status', '=', 1],
                ['va.user_id', '<>', $where['userId']], // 点赞自己的评论不显示
            ])
            ->update([
                'va.read_status' => 1,
            ]);
        // 别人评论我的视频列表
        $this->alias('vc')
            ->join('video v', 'vc.video_id = v.id and v.shop_id = 0')
            ->where([
                //['v.status', '=', 1],
                ['v.user_id', '=', $where['userId']],
                ['vc.from_user_id', '<>', $where['userId']], // 自己评论自己的视频不显示
                ['vc.to_user_id', '=', $where['userId']],
                ['vc.status', '=', 1],
                ['vc.top_comment_id', '=', 0],
            ])
            ->update([
                'vc.read_status' => 1,
            ]);
        // 别人点赞我的视频
        $this->name('video')
            ->alias('v')
            ->leftJoin('video_action va', 'va.action_type = 1 and va.video_id = v.id and va.status = 1')
            ->where([
                //['v.status', '=', 1],
                ['v.user_id', '=', $where['userId']],
                ['v.shop_id', '=', 0],
                ['va.user_id', '<>', $where['userId']], // 自己点赞自己的视频不显示
            ])
            ->update([
                'va.read_status' => 1,
            ]);
    }
}
