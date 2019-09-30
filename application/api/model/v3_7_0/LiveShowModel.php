<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/30
 * Time: 16:05
 */

namespace app\api\model\v3_7_0;
use app\common\model\AbstractModel;

class LiveShowModel extends AbstractModel
{
    protected $name = 'live_show';

   /**
     * 获取我发起的直播记录
     *
     * @param array $where
     *
     * @return array
     */
    public function getMyLiveRecord($where)
    {
        $query = $this->alias('ls')
            ->join('user u', 'ls.anchor_user_id = u.id')
            ->field([
                'ls.start_time',
                'ls.end_time',
                'ls.anchor_user_id',
                'u.nickname',
                'u.thumb_avatar'
            ])
            ->where([
                ['ls.send_user_id', '=', $where['userId']],
                ['ls.status', 'in', '3,8']
            ])
            ->where('ls.start_time','not null')
            ->where('ls.end_time','not null')
            ->order('ls.generate_time', 'desc')
            ->order('ls.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }

   /**
     * 获取他人向我发起的视频记录
     *
     * @param array $where
     *
     * @return array
     */
    public function getOtherLiveRecord($where)
    {
        $query = $this->alias('ls')
            ->join('user u', 'ls.send_user_id = u.id')
            ->field([
                'ls.start_time',
                'ls.end_time',
                'ls.send_user_id',
                'u.nickname',
                'u.thumb_avatar'
            ])
            ->where([
                ['ls.anchor_user_id', '=', $where['userId']],
                ['ls.status', 'in', '3,8']
            ])
            ->where('ls.start_time','not null')
            ->where('ls.end_time','not null')
            ->order('ls.generate_time', 'desc')
            ->order('ls.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }
}