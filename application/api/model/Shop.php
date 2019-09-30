<?php

namespace app\api\model;

use app\common\model\AbstractModel;

class Shop extends AbstractModel
{
    /**
     * 查询店铺信息
     * @param array $where
     * @return array
     */
    public function getShopInfo($where = [])
    {
        if (!isset($where['account_status'])) {
            $where['account_status'] = 1;
        }
        return $this->where($where)->find();
    }

    /**
     * 获取首页猜你喜欢列表
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getJoyList($where = [])
    {
        $query = $this->alias('s')
            ->join('shop_category sc', 's.shop_category_id = sc.id', 'INNER')
            ->join('order o', 's.id = o.shop_id', 'LEFT')
            ->field([
                's.id',
                's.id as shopId', // 店铺ID
                's.shop_name as shopName', // 店铺名称
                's.shop_image as shopImage', // 店铺图像
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                's.shop_address as shopAddress', // 店铺地址
                's.shop_address_poi as shopAddressPoi', // 店铺地址周边
                'sc.name as shopCategoryName', // 店铺所属分类名称
                's.free_order_frequency as freeOrderFrequency', // 免单次数
                "COUNT(distinct o.user_id, o.user_id > 0 or null) as howManyPeopleBought", // 多少人买过
                "COUNT(o.order_status = 1 or null) as countOrder", // 订单成交量
                "CASE WHEN (s.latitude is null or s.longitude is null) 
                THEN 0 
                ELSE (GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667)
                END as distance", // 距离
            ])
            ->where([
                ['s.account_status', '=', 1],
                ['s.online_status', '=', 1]
            ]);
        if (isset($where['userId']) && $where['userId']) {
            $query->field([
                "IF(COUNT(o.user_id = {$where['userId']} or null), 1, 0) as haveBought", // 是否买过
            ]);
        }
        $query->group('s.id')
            ->order('distance', 'asc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }

    /**
     * 首页根据分类ID获取店铺列表
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopList($where = [])
    {
        $query = $this->alias('s')
            ->join('shop_category sc', 's.shop_category_id = sc.id', 'INNER')
            ->join('order o', 's.id = o.shop_id', 'LEFT')
            ->field([
                's.id',
                's.id as shopId', // 店铺ID
                's.shop_name as shopName', // 店铺名称
                's.shop_image as shopImage', // 店铺图像
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                's.shop_address as shopAddress', // 店铺地址
                's.shop_address_poi as shopAddressPoi', // 店铺地址周边
                'sc.name as shopCategoryName', // 店铺所属分类名称
                's.free_order_frequency as freeOrderFrequency', // 免单次数
                "COUNT(distinct o.user_id, o.user_id > 0 or null) as howManyPeopleBought", // 多少人买过
                "COUNT(o.order_status = 1 or null) as countOrder", // 订单成交量
                "CASE WHEN (s.latitude is null or s.longitude is null) 
                THEN 0 
                ELSE (GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667)
                END as distance", // 距离
            ])
            ->where([
                ['s.shop_category_id', '=', $where['shop_category_id']],
                ['s.account_status', '=', 1],
                ['s.online_status', '=', 1]
            ]);
        if (isset($where['userId']) && $where['userId']) {
            $query->field([
                "IF(COUNT(o.user_id = {$where['userId']} or null), 1, 0) as haveBought", // 是否买过
            ]);
        }
        $query->group('s.id')
            ->order('distance', 'asc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }

    /**
     * 获取店铺详情
     *
     * @param $where
     *
     * @return array|null|\PDOStatement|string|\think\Model
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopDetail($where)
    {
        $query = $this->alias('s')
            ->join('shop_category sc', 's.shop_category_id = sc.id', 'INNER')
            ->join('order o', 's.id = o.shop_id', 'LEFT')
            ->field([
                's.id',
                's.id as shopId', // 店铺ID
                's.shop_name as shopName', // 店铺名称
                's.shop_image as shopImage', // 店铺图像
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                's.shop_address as shopAddress', // 店铺地址
                's.shop_address_poi as shopAddressPoi', // 店铺地址周边
                's.longitude', // 经纬度
                's.latitude', // 经纬度
                's.phone as shopPhone', // 店铺电话
                's.operation_time as operationTime', // 营业时间
                'sc.name as shopCategoryName', // 店铺所属分类名称
                's.free_order_frequency as freeOrderFrequency', // 免单次数
                "COUNT(distinct o.user_id, o.user_id > 0 or null) as howManyPeopleBought", // 多少人买过
                "COUNT(o.order_status = 1 or null) as countOrder", // 订单成交量
                "CASE WHEN (s.latitude is null or s.longitude is null) 
                THEN 0 
                ELSE (GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667)
                END as distance", // 距离
            ])
            ->where('s.id', '=', $where['shop_id']);
        if (isset($where['userId']) && $where['userId']) {
            $query->field([
                "IF(COUNT(o.user_id = {$where['userId']} or null), 1, 0) as haveBought", // 是否买过
                "COUNT(o.user_id = {$where['userId']} and o.free_flag = 0 or null) as countNormalOrder", // 消费次数(普通订单)
                "COUNT(o.user_id = {$where['userId']} and o.free_flag = 1 or null) as countFreeOrder", // 消费次数(免单订单)
            ]);
        }
        return $query->find();
    }

    /**
     * 搜索店铺列表
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function searchShopList($where = [])
    {
        $query = $this->alias('s')
            ->join('shop_category sc', 's.shop_category_id = sc.id', 'INNER')
            ->join('order o', 's.id = o.shop_id', 'LEFT')
            ->field([
                's.id',
                's.id as shopId', // 店铺ID
                's.shop_name as shopName', // 店铺名称
                's.shop_image as shopImage', // 店铺图像
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                's.shop_address as shopAddress', // 店铺地址
                's.shop_address_poi as shopAddressPoi', // 店铺地址周边
                'sc.name as shopCategoryName', // 店铺所属分类名称
                's.free_order_frequency as freeOrderFrequency', // 免单次数
                "COUNT(distinct o.user_id, o.user_id > 0 or null) as howManyPeopleBought", // 多少人买过
                "COUNT(o.order_status = 1 or null) as countOrder", // 订单成交量
                "CASE WHEN (s.latitude is null or s.longitude is null) 
                THEN 0 
                ELSE (GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667)
                END as distance", // 距离
            ])
            ->where([
                ['s.account_status', '=', 1],
                ['s.online_status', '=', 1],
                ['s.shop_name', 'like', '%' . $where['keyword'] . '%']
            ]);
        if (isset($where['userId']) && $where['userId']) {
            $query->field([
                "IF(COUNT(o.user_id = {$where['userId']} or null), 1, 0) as haveBought", // 是否买过
            ]);
        }
        $query->group('s.id')
            ->order([
                's.id' => 'desc',
                'distance' => 'asc'
            ])
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }
}