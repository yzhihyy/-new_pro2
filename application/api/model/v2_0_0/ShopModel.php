<?php

namespace app\api\model\v2_0_0;

use app\common\model\AbstractModel;

class ShopModel extends AbstractModel
{
    protected $name = 'shop';

    /**
     * Get Query.
     *
     * @return $this
     */
    public function getQuery()
    {
        $query = $this->alias('s')
            ->field([
                's.id',
                's.id as shopId',
                's.shop_type as shopType',
                's.shop_category_id as shopCategoryId',
                's.user_id as userId',
                's.shop_name as shopName',
                's.shop_image as shopImage',
                's.shop_thumb_image as shopThumbImage',
                's.shop_address as shopAddress',
                's.shop_address_poi as shopAddressPoi',
                's.shop_province AS shopProvince',
                's.shop_city AS shopCity',
                's.shop_area AS shopArea',
                's.shop_detail_address AS shopDetailAddress',
                's.phone as shopPhone',
                's.introduce as shopIntroduce',
                's.balance',
                's.receipt_qr_code as receiptQrCode',
                's.receipt_qr_code_poster as receiptQrCodePoster',
                's.longitude',
                's.latitude',
                's.account_status as accountStatus',
                's.online_status as onlineStatus',
                's.operation_time as operationTime',
                's.tally_time as tallyTime',
                's.free_order_frequency as freeOrderFrequency',
                's.real_name as realName',
                's.id_number as idNumber',
                's.identity_card_front_face_img as identityCardFrontFaceImg',
                's.identity_card_back_face_img as identityCardBackFaceImg',
                's.identity_card_holder_half_img as identityCardHolderHalfImg',
                's.withdraw_rate as withdrawRate',
                's.withdraw_holder_phone as withdrawHolderPhone',
                's.withdraw_bankcard_num as withdrawBankcardNum',
                's.withdraw_holder_name as withdrawHolderName',
                's.withdraw_id_card as withdrawIdCard',
                's.withdraw_bank_type as withdrawBankType',
                's.inviter',
                's.announcement',
                's.is_recommend as isRecommend',
                's.recommend_sort as recommendSort',
                's.recommend_image as recommendImage',
                's.remark',
                's.views',
                's.generate_time as generateTime',
            ]);
        return $query;
    }

    /**
     * 获取首页推荐店铺列表
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getHomeRecommendList($where = [])
    {
        $query = $this->getQuery();
        if ($where) {
            $query->where($where);
        }
        $query->where([
            's.is_recommend' => 1,
            's.account_status' => 1,
            's.online_status' => 1
        ])->order([
            'recommend_sort' => 'asc'
        ])->limit(0, 4);
        return $query->select();
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
            ->join('shop_category sc', 's.shop_category_id = sc.id')
            ->leftJoin('order o', 's.id = o.shop_id')
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
                "COUNT(distinct o.user_id, o.order_status = 1 and o.user_id > 0 or null) as howManyPeopleBought", // 多少人买过
                "COUNT(o.order_status = 1 or null) as countOrder", // 订单成交量
                "COUNT(o.order_status = 1 and o.free_flag = 1 or null) as countFreeOrder", // 订单成交量（免单订单）
                "CASE WHEN (s.latitude is null or s.longitude is null) 
                THEN 0 
                ELSE (GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667)
                END as distance", // 距离
                's.views', // 浏览量
            ])
            ->where([
                's.account_status' => 1,
                's.online_status' => 1
            ]);
        if (isset($where['userId']) && $where['userId']) {
            $query->leftJoin('free_rule fr', "fr.user_id = {$where['userId']} and fr.shop_id = s.id and fr.status = 1")
                ->field([
                    "CASE WHEN (fr.user_id is null)
                     THEN s.free_order_frequency
                     ELSE (LEAST(fr.shop_free_order_frequency, s.free_order_frequency) - fr.order_count)
                     END as countAlsoNeedBuyTimes", // 距离免单还需多少次
                    "IF(COUNT(o.order_status = 1 and o.user_id = {$where['userId']} or null), 1, 0) as haveBought", // 是否买过
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
                // 筛选距离50km以内
                $query->whereRaw("CASE WHEN (s.latitude is null or s.longitude is null) 
                THEN 0 
                ELSE (GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667)
                END <= '50000'")
                    ->order([
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
        return $query->select();
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
    public function getShopListByCategoryId($where = [])
    {
        $query = $this->alias('s')
            ->join('shop_category sc', 's.shop_category_id = sc.id')
            ->leftJoin('order o', 's.id = o.shop_id')
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
                "COUNT(distinct o.user_id, o.order_status = 1 and o.user_id > 0 or null) as howManyPeopleBought", // 多少人买过
                "COUNT(o.order_status = 1 or null) as countOrder", // 订单成交量
                "COUNT(o.order_status = 1 and o.free_flag = 1 or null) as countFreeOrder", // 订单成交量（免单订单）
                "CASE WHEN (s.latitude is null or s.longitude is null) 
                THEN 0 
                ELSE (GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667)
                END as distance", // 距离
                's.views', // 浏览量
            ])
            ->where([
                's.shop_category_id' => $where['shopCategoryId'],
                's.account_status' => 1,
                's.online_status' => 1,
            ]);
        if (isset($where['userId']) && $where['userId']) {
            $query->leftJoin('free_rule fr', "fr.user_id = {$where['userId']} and fr.shop_id = s.id and fr.status = 1")
                ->field([
                    "CASE WHEN (fr.user_id is null)
                     THEN s.free_order_frequency
                     ELSE (LEAST(fr.shop_free_order_frequency, s.free_order_frequency) - fr.order_count)
                     END as countAlsoNeedBuyTimes", // 距离免单还需多少次
                    "IF(COUNT(o.order_status = 1 and o.user_id = {$where['userId']} or null), 1, 0) as haveBought", // 是否买过
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
                // 筛选距离50km以内
                $query->whereRaw("CASE WHEN (s.latitude is null or s.longitude is null) 
                THEN 0 
                ELSE (GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667)
                END <= '50000'")
                    ->order([
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
        return $query->select();
    }

    /**
     * 搜索店铺列表
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function searchShopList($where = [])
    {
        $query = $this->alias('s')
            ->join('shop_category sc', 's.shop_category_id = sc.id')
            ->leftJoin('order o', 's.id = o.shop_id')
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
                "COUNT(distinct o.user_id, o.order_status = 1 and o.user_id > 0 or null) as howManyPeopleBought", // 多少人买过
                "COUNT(o.order_status = 1 or null) as countOrder", // 订单成交量
                "COUNT(o.order_status = 1 and o.free_flag = 1 or null) as countFreeOrder", // 订单成交量（免单订单）
                's.views', // 浏览量
            ])
            ->where([
                ['s.account_status', '=', 1],
                ['s.online_status', '=', 1],
                ['s.shop_name', 'like', '%' . $where['keyword'] . '%']
            ]);
        // 距离
        if (isset($where['latitude']) and isset($where['longitude'])) {
            $query->fieldRaw("CASE WHEN (s.latitude is null or s.longitude is null) THEN -1                
                ELSE (GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667)
                END as distance");
        } else {
            $query->fieldRaw("-1 as distance");
        }

        if (isset($where['userId']) && $where['userId']) {
            $query->leftJoin('free_rule fr', "fr.user_id = {$where['userId']} and fr.shop_id = s.id and fr.status = 1")
                ->field([
                    "CASE WHEN (fr.user_id is null)
                     THEN s.free_order_frequency
                     ELSE (LEAST(fr.shop_free_order_frequency, s.free_order_frequency) - fr.order_count)
                     END as countAlsoNeedBuyTimes", // 距离免单还需多少次
                    "IF(COUNT(o.order_status = 1 and o.user_id = {$where['userId']} or null), 1, 0) as haveBought", // 是否买过
                ]);
        }
        $query->group('s.id')
            ->order([
                'distance' => 'asc',
                'countOrder' => 'desc',
                's.id' => 'desc',
            ])
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select();
    }

    /**
     * 获取店铺信息
     *
     * @param array $where
     *
     * @return array|null|\PDOStatement|string|\think\Model
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopInfo(array $where = [])
    {
        $query = $this->getQuery();

        if (isset($where['shopType']) && $where['shopType']) {
            $query->where('s.shop_type', $where['shopType']);
        }

        if (isset($where['userId']) && $where['userId']) {
            $query->where('s.user_id', $where['userId']);
        }

        if (isset($where['shopId']) && $where['shopId']) {
            $query->where('s.id', $where['shopId']);
        }

        if (isset($where['onlineStatus'])) {
            $query->where('s.online_status', $where['onlineStatus']);
        }

        if (isset($where['shopName']) && $where['shopName']) {
            $query->where('s.shop_name', $where['shopName']);
        }

        if (isset($where['shopType']) && $where['shopType']) {
            $query->where('s.shop_type', $where['shopType']);
        }

        return $query->find();
    }

    /**
     * 我的分店
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMyBranchShop(array $where = [])
    {
        $query = $this->alias('s')
            ->field([
                's.id AS shopId',
                's.shop_name as shopName',
                's.shop_image AS shopImage',
                's.shop_thumb_image AS shopThumbImage',
                's.online_status AS onlineStatus'
            ])
            ->where('s.shop_type', 2)
            ->where('s.user_id', $where['userId'])
            ->order('s.generate_time', 'DESC')
            ->order('s.id', 'DESC');
        return $query->select()->toArray();
    }

    /**
     * 获取关联店铺
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAssociatedShop(array $where = [])
    {
        $query = $this->alias('s')
            ->field([
                's.id AS shopId',
                's.shop_group_id AS shopGroupId',
                's.shop_name as shopName',
            ])
            ->where('s.user_id', $where['userId'])
            ->where('s.id', '<>', $where['shopId']);
        $type = $where['type'] ?? 1;
        switch ($type) {
            // 已关联店铺
            case 1:
                $query->where('s.user_id', $where['userId'])
                    ->where($where['shopGroupId'] ? "s.shop_group_id = {$where['shopGroupId']}" : '1 = 0');
                break;
            // 关联店铺
            case 2:
                $query->where("IF(s.shop_group_id = 0, s.online_status = 1, s.shop_group_id = {$where['shopGroupId']})")
                    ->order('s.shop_group_id', 'DESC');
                break;
            default:
                break;
        }

        return $query->order('s.associated_time', 'DESC')
            ->select()
            ->toArray();
    }

    /**
     * 获取店铺详情
     *
     * @param $where
     *
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopDetail($where)
    {
        $query = $this->alias('s')
            ->join('shop_category sc', 's.shop_category_id = sc.id')
            ->join('user u', 's.user_id = u.id')
            ->leftJoin('order o', 's.id = o.shop_id')
            ->field([
                's.id',
                's.id as shopId', // 店铺ID
                's.user_id as shopUserId', // 店铺用户ID
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
                's.free_order_frequency as freeOrderFrequency', // 消费满多少次免单
                "COUNT(distinct o.user_id, o.order_status = 1 and o.user_id > 0 or null) as howManyPeopleBought", // 多少人买过
                "COUNT(o.order_status = 1 or null) as countOrder", // 订单成交量
                'COUNT(o.order_status = 1 and o.free_flag = 0 or null) as countNormalOrder', // 订单成交量（普通订单）
                "COUNT(o.order_status = 1 and o.free_flag = 1 or null) as countFreeOrder", // 订单成交量（免单订单）
                "CASE WHEN (s.latitude is null or s.longitude is null) 
                THEN 0 
                ELSE (GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667)
                END as distance", // 距离
                's.views', // 店铺浏览量
                's.pay_setting_type as paySettingType',
                // 店铺的用户信息
                'u.avatar as shop_user_avatar',
                'u.thumb_avatar as shop_user_thumb_avatar',
                'u.nickname as shop_user_nickname'
            ])
            ->where('s.id', $where['shopId']);
        if (isset($where['userId']) && $where['userId']) {
            $query->leftJoin('free_rule fr', "fr.user_id = {$where['userId']} and fr.shop_id = s.id and fr.status = 1")
                ->leftJoin('follow_relation fr_', "fr_.from_user_id = {$where['userId']} and fr_.to_shop_id = s.id and fr_.rel_type = 1")
                ->field([
                    "CASE WHEN (fr.user_id is null)
                     THEN 0
                     ELSE fr.order_count
                     END as countAlreadyBuyTimes", // 本轮已消费次数

                    "CASE WHEN (fr.user_id is null)
                     THEN s.free_order_frequency
                     ELSE (LEAST(s.free_order_frequency, fr.shop_free_order_frequency) - fr.order_count)
                     END as countAlsoNeedBuyTimes", // 距离免单还需多少次

                    "IF(COUNT(o.order_status = 1 and o.user_id = {$where['userId']} or null), 1, 0) as haveBought", // 是否买过
                    "COUNT(o.user_id = {$where['userId']} and o.order_status = 1 or null) as countMyTotalOrder", // 消费次数(全部订单)
                    "COUNT(o.user_id = {$where['userId']} and o.order_status = 1 and o.free_flag = 0 or null) as countMyNormalOrder", // 消费次数(普通订单)
                    "COUNT(o.user_id = {$where['userId']} and o.order_status = 1 and o.free_flag = 1 or null) as countMyFreeOrder", // 消费次数(免单订单)

                    'IF(fr_.id, 1, 0) as isFollow', // 是否已关注该店铺
                ]);
        }
        return $query->find();
    }
}
