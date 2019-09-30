<?php

namespace app\api\model\v3_5_0;

use app\common\model\AbstractModel;

class VideoAlbumModel extends AbstractModel
{
    protected $name = 'video_album';

    /**
     * 获取随记图片
     *
     * @param $where
     *
     * @return array
     */
    public function getVideoAlbum($where)
    {
        $query = $this->alias('va')
            ->field('va.image')
            ->where([
                ['va.video_id', '=', $where['videoId']]
            ])
            ->order('va.sort', 'asc')
            ->order('va.id', 'asc');

        return $query->select()->toArray();
    }
}
