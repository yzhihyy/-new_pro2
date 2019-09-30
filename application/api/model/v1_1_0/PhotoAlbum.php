<?php

namespace app\api\model\v1_1_0;

use app\common\model\AbstractModel;

class PhotoAlbum extends AbstractModel
{
    /**
     * 获取Query
     *
     * @return $this
     */
    public function getQuery()
    {
        $query = $this->alias('pa')
            ->field([
                'pa.id',
                'pa.id as photoAlbumId',
                'pa.shop_id as shopId',
                'pa.activity_id as activityId',
                'pa.type',
                'pa.name',
                'pa.image',
                'pa.thumb_image as thumbImage',
                'pa.status',
                'pa.generate_time as generateTime'
            ]);
        return $query;
    }

    /**
     * 获取店铺活动相册
     *
     * @param $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopActivityPhotoAlbum($where)
    {
        $query = $this->getQuery();
        $query->where([
            'status' => 1,
            'type' => 3,
            'shop_id' => $where['shopIdArray'],
            'activity_id' => $where['shopActivityIdArray']
        ]);
        return $query->select()->toArray();
    }

    /**
     * 获取店铺推荐列表
     *
     * @param $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRecommendPhotoAlbum($where)
    {
        $query = $this->getQuery();
        $query->where([
            'shop_id' => $where['shopId'],
            'type' => 2,
            'status' => 1
        ])->order([
            'id' => 'desc'
        ])->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }

    /**
     * 获取店铺相册列表
     *
     * @param $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopPhotoAlbum($where)
    {
        $query = $this->getQuery();
        $query->where([
            'shop_id' => $where['shopId'],
            'type' => 1,
            'status' => 1
        ])->order([
            'id' => 'desc'
        ])->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }
}