<?php

namespace app\api\model\v1_1_0;

use app\api\model\Shop as CommonShop;

class Shop extends CommonShop
{
    /**
     * 获取Query
     *
     * @return $this
     */
    public function getQuery()
    {
        $query = $this->alias('s')
            ->field([
                's.id',
                's.id as shopId',
                's.shop_category_id as shopCategoryId',
                's.user_id as userId',
                's.shop_name as shopName',
                's.shop_image as shopImage',
                's.shop_thumb_image as shopThumbImage',
                's.shop_address as shopAddress',
                's.shop_address_poi as shopAddressPoi',
                's.phone as shopPhone',
                's.introduce as shopIntroduce',
                's.receipt_qr_code as receiptQrCode',
                's.receipt_qr_code_poster as receiptQrCodePoster',
                's.longitude',
                's.latitude',
                's.account_status as accountStatus',
                's.online_status as onlineStatus',
                's.operation_time as operationTime',
                's.free_order_frequency as freeOrderFrequency',
                's.real_name as realName',
                's.id_number as idNumber',
                's.identity_card_front_face_img as identityCardFrontFaceImg',
                's.identity_card_back_face_img as identityCardBackFaceImg',
                's.identity_card_holder_half_img as identityCardHolderHalfImg',
                's.withdraw_holder_phone as withdrawHolderPhone',
                's.withdraw_bankcard_num as withdrawBankcardNum',
                's.withdraw_holder_name as withdrawHolderName',
                's.withdraw_id_card as withdrawIdCard',
                's.withdraw_bank_type as withdrawBankType',
                's.inviter',
                's.announcement',
                's.generate_time as generateTime'
            ]);
        return $query;
    }

    /**
     * 猜你喜欢
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
                's.announcement', // 店铺公告
                's.shop_image as shopImage', // 店铺图像
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                's.shop_address as shopAddress', // 店铺地址
                's.shop_address_poi as shopAddressPoi', // 店铺地址周边
                'sc.name as shopCategoryName', // 店铺所属分类名称
                's.free_order_frequency as freeOrderFrequency', // 消费满多少次免单
                "COUNT(distinct o.user_id, o.user_id > 0 or null) as howManyPeopleBought", // 多少人买过
                "COUNT(o.order_status = 1 or null) as countOrder", // 订单成交量
                "COUNT(o.order_status = 1 and o.free_flag = 1 or null) as countFreeOrder", // 免单订单量
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
            $query->join('free_rule fr', "fr.user_id = {$where['userId']} and fr.shop_id = s.id and fr.status = 1", 'left')
                ->field([
                    "CASE WHEN (fr.user_id is null)
                     THEN s.free_order_frequency
                     ELSE (fr.shop_free_order_frequency - fr.order_count)
                     END as countAlsoNeedBuyTimes", // 距离免单还需多少次
                    "IF(COUNT(o.user_id = {$where['userId']} or null), 1, 0) as haveBought", // 是否买过
                ]);
        }
        // 排序
        switch ($where['sort']) {
            case 1:
                // 按销量排序
                $query->order([
                    'countOrder' => 'desc',
                    'distance' => 'asc',
                    's.id' => 'desc'
                ]);
                break;
            case 2:
                // 按距离排序
                $query->order([
                    'distance' => 'asc',
                    's.id' => 'desc'
                ]);
                break;
            case 3:
                // 按免单次数排序
                $query->order([
                    'freeOrderFrequency' => 'asc',
                    'distance' => 'asc',
                    's.id' => 'desc'
                ]);
                break;
            default:
                $query->order([
                    'distance' => 'asc',
                    's.id' => 'desc'
                ]);
                break;
        }
        // 分页
        $query->group('s.id')
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
                's.announcement', // 店铺公告
                's.shop_image as shopImage', // 店铺图像
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                's.shop_address as shopAddress', // 店铺地址
                's.shop_address_poi as shopAddressPoi', // 店铺地址周边
                'sc.name as shopCategoryName', // 店铺所属分类名称
                's.free_order_frequency as freeOrderFrequency', // 消费满多少次免单
                "COUNT(distinct o.user_id, o.user_id > 0 or null) as howManyPeopleBought", // 多少人买过
                "COUNT(o.order_status = 1 or null) as countOrder", // 订单成交量
                "COUNT(o.order_status = 1 and o.free_flag = 1 or null) as countFreeOrder", // 免单订单量
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
            $query->join('free_rule fr', "fr.user_id = {$where['userId']} and fr.shop_id = s.id and fr.status = 1", 'left')
                ->field([
                    "CASE WHEN (fr.user_id is null)
                     THEN s.free_order_frequency
                     ELSE (fr.shop_free_order_frequency - fr.order_count)
                     END as countAlsoNeedBuyTimes", // 距离免单还需多少次
                    "IF(COUNT(o.user_id = {$where['userId']} or null), 1, 0) as haveBought", // 是否买过
                ]);
        }
        // 排序
        switch ($where['sort']) {
            case 1:
                // 按销量排序
                $query->order([
                    'countOrder' => 'desc',
                    'distance' => 'asc',
                    's.id' => 'desc'
                ]);
                break;
            case 2:
                // 按距离排序
                $query->order([
                    'distance' => 'asc',
                    's.id' => 'desc'
                ]);
                break;
            case 3:
                // 按免单次数排序
                $query->order([
                    'freeOrderFrequency' => 'asc',
                    'distance' => 'asc',
                    's.id' => 'desc'
                ]);
                break;
            default:
                $query->order([
                    'distance' => 'asc',
                    's.id' => 'desc'
                ]);
                break;
        }
        $query->group('s.id')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
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
                's.announcement', // 店铺公告
                's.shop_image as shopImage', // 店铺图像
                's.shop_thumb_image as shopThumbImage', // 店铺缩略图
                's.shop_address as shopAddress', // 店铺地址
                's.shop_address_poi as shopAddressPoi', // 店铺地址周边
                'sc.name as shopCategoryName', // 店铺所属分类名称
                's.free_order_frequency as freeOrderFrequency', // 消费满多少次免单
                "COUNT(distinct o.user_id, o.user_id > 0 or null) as howManyPeopleBought", // 多少人买过
                "COUNT(o.order_status = 1 or null) as countOrder", // 订单成交量
                "COUNT(o.order_status = 1 and o.free_flag = 1 or null) as countFreeOrder", // 免单订单量
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
            $query->join('free_rule fr', "fr.user_id = {$where['userId']} and fr.shop_id = s.id and fr.status = 1", 'left')
                ->field([
                    "CASE WHEN (fr.user_id is null)
                     THEN s.free_order_frequency
                     ELSE (fr.shop_free_order_frequency - fr.order_count)
                     END as countAlsoNeedBuyTimes", // 距离免单还需多少次
                    "IF(COUNT(o.user_id = {$where['userId']} or null), 1, 0) as haveBought", // 是否买过
                ]);
        }
        $query->group('s.id')
            ->order([
                'distance' => 'asc',
                'countOrder' => 'desc',
                's.id' => 'desc',
            ])
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
                's.announcement', // 店铺公告
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
            $query->join('free_rule fr', "fr.user_id = {$where['userId']} and fr.shop_id = s.id and fr.status = 1", 'left')
                ->field([
                    "CASE WHEN (fr.user_id is null)
                     THEN 0
                     ELSE fr.order_count
                     END as countAlreadyBuyTimes", // 本轮已消费次数

                    "CASE WHEN (fr.user_id is null)
                     THEN s.free_order_frequency
                     ELSE (fr.shop_free_order_frequency - fr.order_count)
                     END as countAlsoNeedBuyTimes",// 本轮还需消费次数

                    "IF(COUNT(o.user_id = {$where['userId']} or null), 1, 0) as haveBought", // 是否买过
                    "COUNT(o.user_id = {$where['userId']} and o.order_status = 1 or null) as countTotalOrder", // 消费次数(全部订单)
                    "COUNT(o.user_id = {$where['userId']} and o.order_status = 1 and o.free_flag = 0 or null) as countNormalOrder", // 消费次数(普通订单)
                    "COUNT(o.user_id = {$where['userId']} and o.order_status = 1 and o.free_flag = 1 or null) as countFreeOrder", // 消费次数(免单订单)
            ]);
        }
        return $query->find();
    }
}