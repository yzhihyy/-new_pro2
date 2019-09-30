<?php

namespace app\api\model\v2_0_0;

use app\common\model\AbstractModel;

class PhotoAlbumModel extends AbstractModel
{
    protected $name = 'photo_album';

    /**
     * 获取Query
     *
     * @return $this
     */
    public function getQuery()
    {
        return $this->alias('pa')
            ->field([
                'pa.id',
                'pa.id as photoAlbumId',
                'pa.shop_id as shopId',
                'pa.relation_id AS relationId',
                'pa.type',
                'pa.name',
                'pa.image',
                'pa.thumb_image as thumbImage',
                'pa.status',
                'pa.generate_time as generateTime'
            ]);
    }

    /**
     * 获取店铺相册图片
     *
     * @param $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopPhotoAlbum(array $where = [])
    {
        $query = $this->getQuery()
            ->where([
                ['pa.shop_id', '=', $where['shopId']],
                ['pa.type', '=', $where['type']],
                ['pa.status', '=', 1],
            ]);
        if (isset($where['relationId']) && is_numeric($where['relationId'])) {
            $query->where('pa.relation_id', $where['relationId']);
        }
        return $query->select();
    }
}