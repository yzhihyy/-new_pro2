<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class MusicModel extends AbstractModel
{
    protected $name = 'music';

   /**
     * 获取音乐列表
     *
     * @param array $where
     *
     * @return array
     */
    public function getMusicList($where)
    {
        $sql = '';
        if (!empty($where['keyword'])) {
            $sql .= " AND (m.music_name LIKE '%{$where['keyword']}%' OR m.artist_name LIKE '%{$where['keyword']}%')";
        }
        $query = $this->alias('m')
            ->where('m.status = 1'.$sql)
            ->order('m.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }
}
