<?php

namespace app\api\model\v3_7_0;

use app\common\model\AbstractModel;

class VideoModel extends AbstractModel
{
    protected $name = 'video';

   /**
     * 获取我发布的视频列表
     *
     * @param array $where
     *
     * @return array
     */
    public function getMyVideoList($where)
    {
        $query = $this->alias('v')
            ->field([
                'v.id as videoId',
                'v.cover_url',
                'v.video_url'
            ])
            ->where([
                ['v.type', '=', '1'],
                ['v.user_id', '=', $where['userId']],
                ['v.status', '=', '1']
            ])
            ->order('v.generate_time', 'desc')
            ->order('v.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }
}
