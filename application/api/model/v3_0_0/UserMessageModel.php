<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class UserMessageModel extends AbstractModel
{
    protected $name = 'user_message';

    /**
     * 消息首页
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMessageIndex($where = [])
    {
        $subQuery = $this->field(true)
            ->distinct(true)
            ->where('delete_status', 0)
            ->order('read_status', 'asc')
            ->order('generate_time', 'desc')
            ->order('id', 'desc');

        if (isset($where['toUserId']) && $where['toUserId']) {
            $subQuery->where('to_user_id', $where['toUserId']);
        }

        if (isset($where['toShopId']) && $where['toShopId']) {
            $subQuery->where('msg_type','in',  [1, 6]);//只查询商家收款和新增粉丝的数据
            $subQuery->where('to_shop_id', $where['toShopId']);
        }

        $subQuery = $subQuery->buildSql();

        $query = $this->table($subQuery)
            ->alias('um')
            ->field([
                'um.msg_type as msgType',
                'COUNT(IF(um.read_status = 0, true, null)) as unreadMsgCount', // 统计未读消息数量
                'um.content',
                'um.generate_time as generateTime',
                'CASE WHEN s.id > 0 THEN s.shop_name ELSE u.nickname END as nickname'
            ])
            ->leftJoin('user u', 'u.id = um.from_user_id')
            ->leftJoin('shop s', 's.id = um.from_shop_id')
            // 视频评论和评论回复归为一组，视频点赞和评论点赞归为一组
            ->group('IF(um.msg_type in (2,5), 2, IF(um.msg_type in (3,4), 3, um.msg_type))');

        return $query->select()->toArray();
    }

    /**
     * 根据消息类型获取商家消息列表
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMerchantMsgListByType($where = [])
    {
        $subQuery = $this->field(true)
            ->distinct(true)
            ->where([
                'to_shop_id' => $where['toShopId'],
                'msg_type' => $where['msgType'],
                'delete_status' => 0
            ])
            ->order('read_status', 'asc')
            ->order('generate_time', 'desc')
            ->order('id', 'desc')
            ->buildSql();

        $query = $this->table($subQuery)
            ->alias('um')
            ->field([
                'um.id',
                'um.group_id as groupId',
                'um.msg_type as msgType',
                'um.title',
                'um.content',
                'um.read_status as readStatus',
                'um.generate_time as generateTime',
                'COUNT(um.id) as msgGroupCount',
                'u.id as userId',
                'u.nickname',
                'u.avatar',
                'u.thumb_avatar as thumbAvatar',
            ])
            ->leftJoin('user u', 'u.id = um.from_user_id');

        // 评论和点赞
        if (array_intersect($where['msgType'], [2, 3, 4, 5])) {
            $query->field([
                'v.id as videoId',
                'v.video_url as videoUrl',
                'v.cover_url as coverUrl',
                'v.status as videoStatus',
                'vc.id as commentId'
            ])
            ->leftJoin('video v', 'v.id = um.video_id')
            ->leftJoin('video_comment vc', 'vc.id = um.comment_id AND vc.status = 1');
        }

        $query->group("IF(um.group_id != '', um.group_id, um.id)")
            ->order('um.generate_time', 'desc')
            ->order('um.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);

        return $query->select()->toArray();
    }

    /**
     * 根据消息类型获取用户消息列表
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserMsgListByType($where = [])
    {
        $query = $this->alias('um')
            ->field([
                'um.id',
                'um.msg_type as msgType',
                'um.title',
                'um.content',
                'um.read_status as readStatus',
                'um.generate_time as generateTime',
                'u.id as userId',
                's.id as shopId',
                'CASE WHEN s.id > 0 THEN s.shop_name ELSE u.nickname END as nickname',
                'CASE WHEN s.id > 0 THEN s.shop_image ELSE u.avatar END as avatar',
                'CASE WHEN s.id > 0 THEN s.shop_thumb_image ELSE u.thumb_avatar END as thumbAvatar',
            ])
            ->leftJoin('user u', 'u.id = um.from_user_id')
            ->leftJoin('shop s', 's.id = um.from_shop_id');

        // 评论和点赞
        if (array_intersect($where['msgType'], [2, 3, 4, 5])) {
            $query->field([
                'v.id as videoId',
                'v.type as videoType',
                'v.video_url as videoUrl',
                'v.cover_url as coverUrl',
                'v.status as videoStatus',
                'vc.id as commentId'
            ])
                ->leftJoin('video v', 'v.id = um.video_id')
                ->leftJoin('video_comment vc', 'vc.id = um.comment_id AND vc.status = 1');
        }

        $query->where([
            'um.to_user_id' => $where['toUserId'],
            'um.msg_type' => $where['msgType'],
            'um.delete_status' => 0
        ])
            ->order('um.read_status', 'asc')
            ->order('um.generate_time', 'desc')
            ->order('um.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);

        return $query->select()->toArray();
    }

    /**
     * 获取未读消息组
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUnreadMessageGroup($where = [])
    {
        $query = $this->field(['GROUP_CONCAT(id) AS msgId'])
            ->where([
                'group_id' => '',
                'read_status' => 0,
                'delete_status' => 0,
                'to_shop_id' => $where['toShopId']
                ])
        // 视频相关的消息使用 video_id，评论相关的消息使用 comment_id
        ->group('IF(msg_type IN (2,3), video_id, comment_id), msg_type');

        return $query->select()->toArray();
    }

    /**
     * 获取消息分组下的用户信息
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGroupUser($where = [])
    {
        $query = $this->alias('um')
            ->field([
                'um.id',
                'um.group_id as groupId',
                'u.id as userId',
                'u.thumb_avatar as thumbAvatar'
            ])
            ->join('user u', 'u.id = um.from_user_id AND u.account_status = 1')
            ->leftJoin('user_message umg', 'umg.group_id = um.group_id AND um.id < umg.id')
            ->where('um.delete_status', 0)
            ->whereIn('um.group_id', $where['groupId'])
            ->whereNotIn('um.id', $where['excludeId'])
            ->group('um.id, um.group_id')
            ->having('COUNT(umg.id) < 4')
            ->order('um.generate_time', 'desc')
            ->order('um.id', 'desc');

        return $query->select()->toArray();
    }
}
