<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class UserVideoHistoryModel extends AbstractModel
{
    protected $name = 'user_video_history';

    /**
     * @return $this
     */
    public function getQuery()
    {
        return $this->alias('uvh')
            ->field([
                'uvh.id',
                'uvh.id AS videoHistoryId',
                'uvh.user_id AS userId',
                'uvh.video_id AS videoId',
                'uvh.play_count AS playCount',
                'uvh.play_finished AS playFinished',
                'uvh.generate_time AS generateTime',
            ]);
    }

    /**
     * 获取用户的视频播放历史
     *
     * @param array $where
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserVideoHistory(array $where = [])
    {
        $query = $this->getQuery()
            ->where('uvh.user_id', $where['userId']);

        if (isset($where['videoIds']) && is_array($where['videoIds'])) {
            $query->whereIn('uvh.video_id', $where['videoIds']);
        }

        return $query->select()->toArray();
    }
}
