<?php

namespace app\api\model\v2_0_0;

use app\common\model\AbstractModel;

class OrderModel extends AbstractModel
{
    protected $name = 'order';

    /**
     * 用户在指定店铺的消费记录
     *
     * @param array $where
     *
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userConsumeDataByShop($where = [])
    {
        return $this->field([
            'sum(payment_amount) as totalMoney', // 在该店的总交易额
            'count(id) as totalCount', // 消费次数
            'SUM(payment_amount) / COUNT(id) as avgMoney', // 客均价
        ])
            ->where([
                'user_id' => $where['userId'],
                'shop_id' => $where['shopId'],
                'order_status' => 1
            ])->find();
    }

    /**
     * 根据免单规则查找用户订单
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrdersByFreeRule($where = [])
    {
        $query = $this->alias('o')
            ->field([
                'o.id as orderId',
                'o.order_num as orderNum',
                'o.payment_amount as payMoney',
                'o.order_amount as orderMoney',
                "DATE_FORMAT(o.payment_time, '%Y-%m-%d %H:%i') as payTime",
                'o.free_flag as freeFlag',
                'o.consume_amount as consumeAmount',
                'o.free_rule_id as freeRuleId',
            ])
            ->leftJoin('shop s', 's.id = o.shop_id')
            ->where(['o.user_id' => $where['userId'], 'o.order_status' => 1])
            ->whereIn('o.free_rule_id', $where['freeRuleId'])
            ->order('o.generate_time', 'desc');

        if (isset($where['shopId'])) {
            $query->where('o.shop_id', $where['shopId']);
        }

        return $query->select()->toArray();
    }

    /**
     * 获取最近购买过的店铺
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMyRecentlyConsumedShop($where = [])
    {
        // 从订单随机筛选出最近购买过的店铺
        $subQuery = $this->alias('o')
            ->join('shop s', 'o.shop_id = s.id and s.online_status = 1 and s.account_status = 1')
            ->field([
                's.id',
                's.id as shopId',
                's.shop_name as shopName',
                's.shop_image as shopImage',
                's.shop_thumb_image as shopThumbImage',
                's.recommend_image as recommendImage',
                'o.id as orderId',
                'FLOOR(RAND() * 999999) as randNumber', // 生存随机数
            ])
            ->where([
                'o.order_status' => 1,
                'o.user_id' => $where['userId']
            ])
            ->order([
                'o.id' => 'desc'
            ])
            ->buildSql();

        $query = $this->table($subQuery)
            ->alias('sq')
            ->field('sq.*')
            ->order([
                'sq.randNumber' => 'desc',
                'sq.orderId' => 'desc'
            ])
            ->group('sq.shopId')
            ->limit(0, 4);
        return $query->select();
    }

    /**
     * 查询订单统计信息
     *
     * @param array $where
     *
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderStatistics($where = [])
    {
        return $this->alias('o')
            ->field([
                'COUNT(o.id) as id_count',
                'SUM(o.free_money) as free_money_sum',
                'SUM(o.payment_amount) as payment_amount_sum'
            ])
            ->where($where)
            ->find();
    }

    /**
     * 我的足迹
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMyFootprintList($where = [])
    {
        // 从订单表统计出我消费过的店铺
        $subQuery = $this->alias('o')
            ->join('shop s', 'o.shop_id = s.id and s.account_status = 1 and s.online_status = 1')
            ->leftJoin('free_rule fr', "fr.shop_id = s.id and fr.user_id = {$where['userId']} and fr.status = 1")
            ->field([
                'o.shop_id as shopId', // 店铺ID
                "IF(fr.shop_free_order_frequency,
                    LEAST(fr.shop_free_order_frequency, s.free_order_frequency),
                    s.free_order_frequency
                ) as shopFreeOrderFrequency", // 该轮店铺设置的免单次数
                'o.current_number as currentNumber', // 当前次数
                'o.consume_amount as consumeAmount', // 当前累计金额
                'o.free_flag as freeFlag', // 是否免单
                'o.free_money as freeMoney', // 免单金额
                's.shop_name as shopName', // 店铺名称
                's.shop_image as shopImage', // 店铺logo
                's.shop_thumb_image as shopThumbImage', // 店铺logo
            ])
            ->where([
                'o.user_id' => $where['userId'],
                'o.order_status' => 1
            ])
            ->order([
                'o.id' => 'desc'
            ])
            ->buildSql();

        // 查询我的足迹
        $mainQuery = $this->table($subQuery)
            ->alias('subQuery')
            ->field([
                'subQuery.shopId', // 店铺ID
                'subQuery.shopName', // 店铺名称
                'subQuery.shopImage', // 店铺logo
                'subQuery.shopThumbImage', // 店铺logo
                'subQuery.currentNumber as sumCurrentNumber', // 已消费次数
                'subQuery.consumeAmount as sumConsumeAmount', // 累计金额
                'subQuery.shopFreeOrderFrequency', // 免单次数
                'subQuery.freeFlag',
                'subQuery.freeMoney',
            ])
            ->group('subQuery.shopId')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $mainQuery->select();
    }

    /**
     * 获取订单列表
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderList($where = [])
    {
        $condition = [
            'o.order_type' => 1,
            'o.user_id' => $where['userId'],
            'o.order_status' => 1
        ];
        if (isset($where['freeFlag'])) {
            $condition['o.free_flag'] = $where['freeFlag'];
        }
        $query = $this->alias('o')
            ->join('shop s', 'o.shop_id = s.id')
            ->leftJoin('free_rule fr', "fr.shop_id = s.id and fr.user_id = {$where['userId']} and fr.status = 1")
            ->field([
                'o.id as orderId',
                'o.shop_id as shopId',
                's.shop_name as shopName',
                's.shop_thumb_image as shopThumbImage',
                'o.order_amount as orderAmount',
                'o.payment_amount as paymentAmount',
                'o.payment_time as paymentTime',
                'o.free_flag as freeFlag', // 是否免单
                'o.free_money as freeMoney', // 免单金额
                'o.current_number as currentOrderCount', // 当前次数
                "IF(fr.shop_free_order_frequency,
                    LEAST(fr.shop_free_order_frequency, s.free_order_frequency, o.shop_free_order_frequency),
                    LEAST(s.free_order_frequency, o.shop_free_order_frequency)
                ) as shopFreeOrderFrequency", // 该轮店铺设置的免单次数
            ])
            ->where($condition)
            ->order(['o.id' => 'desc'])
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select();
    }

    /**
     * 获取商家客户列表
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMerchantCustomerList($where = [])
    {
        // 店铺所有交易成功的订单
        $subQuery = $this->alias('o')
            ->field([
                'o.id',
                'o.user_id',
                'o.shop_id',
                'o.payment_amount',
                'o.payment_time',
            ])
            ->where([
                'o.shop_id' => $where['shopId'],
                'o.order_status' => 1,
            ])
            ->order('o.id', 'desc')
            ->buildSql();
        // 统计商家用户的订单总额和订单总数
        $query = $this->table($subQuery)
            ->alias('o')
            ->join('user buyer', 'buyer.id = o.user_id', 'INNER')
            ->field([
                'buyer.id as buyerId',
                'buyer.nickname as buyerNickname',
                'buyer.avatar as buyerAvatar',
                'buyer.thumb_avatar as buyerThumbAvatar',
                'SUM(o.payment_amount) as countMoney',
                'COUNT(o.user_id) as countOrder',
                'SUM(o.payment_amount) / COUNT(o.user_id) as avgMoney', // 客均价
                'o.id as lastOrderId',
                'o.payment_amount as lastPayMoney',
                'o.payment_time as lastPayTime',
            ])
            ->group('o.user_id');

        // 排序
        $sort = $where['sort'] ?? 1;
        switch ($sort) {
            // 总额排序
            case 2:
                $query->order('countMoney', 'desc');
                break;
            // 订单量排序
            case 3:
                $query->order('countOrder', 'desc');
                break;
            // 客均价
            case 4:
                $query->order('avgMoney', 'desc');
                break;
            // 按时间排序
            case 1:
            default:
                $query->order('o.payment_time', 'desc');
                break;

        }

        $query->limit($where['page'] * $where['limit'], $where['limit']);

        return $query->select()->toArray();
    }

    /**
     * 获取商家订单列表
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMerchantOrderList($where = [])
    {
        $query = $this->alias('o')
            ->join('user buyer', 'buyer.id = o.user_id')
            ->field([
                'o.id as orderId',
                'o.order_num as orderNum',
                'buyer.id as buyerId',
                'buyer.nickname as buyerNickname',
                'buyer.avatar as buyerAvatar',
                'buyer.thumb_avatar as buyerThumbAvatar',
                'o.payment_time as payTime',
                'o.order_amount as orderMoney',
                'o.payment_amount as payMoney',
                'o.free_flag as freeFlag',
            ])
            ->where('o.order_type', 1)
            ->where('o.shop_id', $where['shopId'])
            ->where('o.order_status', 1);

            if (isset($where['userId']) && $where['userId']) {
                $query->where('o.user_id', $where['userId']);
            }

            $query->order('o.generate_time', 'DESC')
            ->order('o.id', 'DESC')
            ->limit($where['page'] * $where['limit'], $where['limit']);

            return $query->select()->toArray();
    }

    /**
     * 免单卡详情（订单列表）
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getFreeCardOrderList($where = [])
    {
        $query = $this->alias('o')
            ->leftJoin('shop s', 'o.shop_id = s.id')
            ->field([
                'o.id AS orderId', // 订单ID
                's.id AS shopId', // 店铺ID
                's.shop_name AS shopName', // 店铺名称
                's.shop_image AS shopImage', // 店铺图片
                's.shop_thumb_image AS shopThumbImage', // 店铺图片缩略图
                'o.payment_amount as paymentAmount',
                'o.payment_time as paymentTime'
            ])
            ->where([
                'o.user_id' => $where['userId'],
                'o.order_status' => 1,
                'o.free_rule_id' => $where['freeRuleId']
            ]);
        return $query->select();
    }

    /**
     * 获取订单信息
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderInfo($where = [])
    {
        $condition = [
            'o.user_id' => $where['userId'],
            'o.order_num' => $where['orderNum']
        ];
        $query = $this->alias('o')
            ->leftJoin('shop s', 'o.shop_id = s.id')
            ->field([
                'o.shop_id',
                's.shop_name',
                's.shop_thumb_image',
                'o.order_status',
                'o.free_flag',
                'o.free_money',
                'o.payment_amount',
                'o.shop_free_order_frequency',
                'o.current_number',
                'o.consume_amount',
                'o.free_rule_id'
            ])
            ->where($condition)
            ->find();
        return $query;
    }
}
