<?php

namespace app\api\model\v2_0_0;

use app\common\model\AbstractModel;

class ShopRecommendModel extends AbstractModel
{
    protected $name = 'shop_recommend';

    /**
     * 获取Query
     *
     * @return $this
     */
    public function getQuery()
    {
        return $this->alias('sr')
            ->field([
                'sr.id',
                'sr.id AS recommendId',
                'sr.shop_id AS shopId',
                'sr.content',
                'sr.image',
                'sr.thumb_image AS thumbImage',
                'sr.pageviews',
                'sr.generate_time AS generateTime',
            ]);
    }

    /**
     * 获取店铺推荐列表
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopRecommendList(array $where = [])
    {
        return $this->getQuery()
            ->where('sr.shop_id', $where['shopId'])
            ->order('sr.generate_time', 'DESC')
            ->order('sr.id', 'DESC')
            ->limit($where['page'] * $where['limit'], $where['limit'])
            ->select()
            ->toArray();
    }

    /**
     * 获取店铺推荐详情
     *
     * @param array $where
     *
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopRecommendInfo(array $where)
    {
        $query = $this->getQuery();
        $query->join('shop s', 'sr.shop_id = s.id', 'inner')
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
                'sr.id' => $where['shopRecommendId']
            ]);
        return $query->find();
    }
}
