<?php

namespace app\api\model\v1_1_0;

use app\common\model\AbstractModel;

class ShopActivity extends AbstractModel
{
    /**
     * 获取Query
     *
     * @return $this
     */
    public function getQuery()
    {
        $query = $this->alias('sa')
            ->field([
                'sa.id',
                'sa.id as shopActivityId', // 活动ID
                'sa.shop_id as shopId', // 店铺ID
                'sa.content', // 活动内容
                'sa.pageviews', // 浏览量
                'sa.status',
                'sa.generate_time as generateTime'
            ]);
        return $query;
    }

    /**
     * 获取店铺活动列表
     *
     * @param $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopActivity($where)
    {
        $query = $this->getQuery();
        $query->where([
            'shop_id' => $where['shopId'],
            'status' => 1
        ])->order([
            'id' => 'desc'
        ])->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }

    /**
     * 获取店铺活动详情
     *
     * @param $where
     *
     * @return array|null|\PDOStatement|string|\think\Model
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopActivityInfo($where)
    {
        $query = $this->getQuery();
        $query->join('shop s', 'sa.shop_id = s.id', 'inner')
            ->join('shop_category sc', 'sc.id = s.shop_category_id', 'left')
            ->field([
                's.shop_name as shopName', // 店铺名称
                's.shop_image as shopImage', // 店铺logo
                's.shop_thumb_image as shopThumbImage', // 店铺logo缩略图
                'sc.name as shopCategoryName', // 店铺分类
                's.shop_address_poi as shopAddressPoi', // 店铺周边
                "CASE WHEN (s.latitude is null or s.longitude is null) 
                THEN 0 
                ELSE (GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667)
                END as distance", // 距离
            ])->where([
                's.account_status' => 1,
                's.online_status' => 1,
                'sa.status' => 1,
                'sa.id' => $where['shopActivityId']
            ]);
        return $query->find();
    }
}
