<?php

namespace app\api\model;

use app\common\model\AbstractModel;

class Order extends AbstractModel
{
    /**
     * 获取店铺最近免单记录
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopLastFreeOrderList($where = [])
    {
        $query = $this->alias('o')
            ->join('user u', 'u.id = o.user_id', 'INNER')
            ->field([
                'o.id',
                'o.id as orderId', // 订单ID
                'o.user_id',
                'u.avatar', // 用户头像
                'u.nickname', // 用户昵称
                'o.payment_time as paymentTime', // 支付时间
                'o.order_amount as orderAmount', // 订单金额
                'o.payment_amount as paymentAmount', // 支付金额
                'o.free_flag as freeFlag'
            ])
            ->where([
                ['o.shop_id', '=', $where['shop_id']],
                //['o.free_flag', '=', 1],
                ['o.order_status', '=', 1]
            ])
            ->order('o.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }

    /**
     * 查询订单统计信息
     * @param array $where
     * @return array
     */
    public function getOrderStatistics($where = [])
    {
        return $this->where($where)->fieldRaw('COUNT(id) as id_count, SUM(free_money) as free_money_sum, SUM(payment_amount) as payment_amount_sum')->find();
    }

    /**
     * 查询我的足迹列表
     * @param array $where
     * @return array
     */
    public function getMyFootprintList($where = [])
    {
        $limit = $where['limit'];
        $rows = $where['page'] * $limit;
        $sql = 'SELECT fo.*, s.shop_name, s.shop_thumb_image  FROM (
                SELECT * FROM (SELECT id as order_id, shop_id, shop_free_order_frequency, current_number, consume_amount, free_flag, free_money FROM fo_order WHERE user_id = '.$where['userId'].' AND order_status = 1 ORDER BY id DESC) o GROUP BY o.shop_id
            ) fo 
            INNER JOIN fo_shop s ON fo.shop_id = s.id
            ORDER BY order_id DESC
            LIMIT '.$rows.', '.$limit;
        return $this->query($sql);
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
            ->join('user seller', 'seller.id = o.shop_user_id', 'INNER')
            ->join('user buyer', 'buyer.id = o.user_id', 'INNER')
            ->field([
                'o.id',
                'o.id as orderId', // 订单ID
                'o.order_num as orderNum', // 订单编号
                'buyer.id as buyerId', // 买家ID
                'buyer.nickname as buyerNickname', // 买家昵称
                'buyer.avatar as buyerAvatar', // 买家头像
                'buyer.thumb_avatar as buyerThumbAvatar', // 买家头像缩略图
                'o.consume_amount as consumeAmount', // 本轮消费总额
                'o.current_number as countAlreadyBuyTimes', // 本轮消费次数
                'o.shop_free_order_frequency as shopFreeOrderFrequency', // 本轮免单需要次数
                'o.shop_free_order_frequency - o.current_number as countAlsoNeedBuyTimes', // 免单还需次数
                'o.consume_amount / o.shop_free_order_frequency as avgFreeMoney', // 免单平均价
                'o.free_flag as freeFlag', // 是否免单
                'o.payment_amount as payMoney', // 本次消费金额
                'o.payment_time as payTime', // 支付时间
            ])
            ->where([
                'o.shop_user_id' => $where['userId'],
                'o.order_status' => 1
            ]);
        if (isset($where['customerId']) && $where['customerId']) {
            $query->where('o.user_id', $where['customerId']);
        }
        $query->order([
            'o.id' => 'desc'
        ])
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }

    /**
     * 查询订单列表
     * @param array $where
     * @return array
     */
    public function getOrderList($where = [])
    {
        $condition = [
            'o.user_id' => $where['userId'],
            'o.order_status' => 1
        ];
        if (isset($where['freeFlag'])) {
            $condition['o.free_flag'] = $where['freeFlag'];
        }
        if (isset($where['shop_id'])) {
            $condition['o.shop_id'] = $where['shop_id'];
        }
        $query = $this->alias('o')->join('shop s', 'o.shop_id = s.id')
            ->field([
                'o.shop_id',
                's.shop_name',
                's.shop_thumb_image',
                'o.payment_amount',
                'o.payment_time',
                'o.free_money',
                'o.free_flag',
                'o.shop_free_order_frequency',
                'o.current_number'
            ])
            ->where($condition)
            ->order(['o.id'=>'desc'])
            ->limit($where['page'] * $where['limit'], $where['limit'])
            ->select()->toArray();
        return $query;
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
        /**
         * 商家的所有订单
         */
        $subQuery = $this->alias('o')
            ->field([
                'o.id',
                'o.user_id',
                'o.shop_id',
                'o.shop_user_id',
                'o.payment_amount',
                'o.payment_time',
            ])
            ->where([
                'o.shop_user_id' => $where['userId'],
                'o.order_status' => 1,
            ])
            ->order('o.id', 'desc')
            ->buildSql();
        /**
         * 获取商家的用户，统计订单量，订单总额
         */
        $query = $this->table($subQuery)
            ->alias('o')
            ->join('user seller', 'seller.id = o.shop_user_id', 'INNER')
            ->join('user buyer', 'buyer.id = o.user_id', 'INNER')
            ->field([
                'buyer.id as buyerId', // 买家ID
                'buyer.nickname as buyerNickname', // 买家昵称
                'buyer.avatar as buyerAvatar', // 买家头像
                'buyer.thumb_avatar as buyerThumbAvatar', // 买家头像缩略图
                'SUM(o.payment_amount) as countMoney', // 总消费
                'COUNT(o.user_id) as countOrder', // 总次数
                'SUM(o.payment_amount) / COUNT(o.user_id) as avgMoney', // 客均价
                'o.id as lastOrderId', // 最后订单
                'o.payment_amount as lastPayMoney', // 最近消费金额
                'o.payment_time as lastPayTime', // 最近支付时间
            ]);
        // 排序
        if (isset($where['sort'])) {
            switch ($where['sort']) {
                case '1':
                    // 按时间排序
                    $query->order('o.payment_time', 'desc');
                    break;
                case '2':
                    // 总额排序
                    $query->order('countMoney', 'desc');
                    break;
                case '3':
                    // 订单量排序
                    $query->order('countOrder', 'desc');
                    break;
                case '4':
                    // 客户均价
                    $query->order('avgMoney', 'desc');
                    break;
                default:
                    // 默认时间排序
                    $query->order('o.payment_time', 'desc');
                    break;
            }
        }
        $query->group('o.user_id')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }

    /**
     * 查询店铺回头客数量（下单次数大于1）
     * @param array $where
     * @return array
     */
    public function getShopCustomer($where = [])
    {
        $sql = 'SELECT COUNT(o.id) as id_count FROM (
                SELECT id FROM fo_order WHERE shop_id = '.$where['shop_id'].' AND order_status = 1 GROUP BY user_id HAVING COUNT(id) > 1
            ) o';
        return $this->query($sql);
    }

    /**
     * 查询店铺回头客数量（即下单次数大于1的用户）
     *
     * @param array $where
     *
     * @return array|null|\PDOStatement|string|\think\Model
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopReturnGuest($where = [])
    {
        $subQuery = $this->alias('o')
            ->field([
                'o.user_id as userId',
                'count(o.id) as orderCount'
            ])
            ->where([
                'o.shop_id' => $where['shop_id'],
                'o.order_status' => 1
            ])
            ->group('o.user_id')
            ->having('count(o.id)>1')
            ->buildSql();

        $mainQuery = $this->table($subQuery)
            ->alias('st')
            ->field([
                'count(st.userId) as countReturnGuest'
            ]);
        return $mainQuery->find();
    }

    /**
     * 商家的近期订单列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recentOrderList(array $where = [] ): array
    {
        $query = $this->alias('o')
            ->join('user u', 'u.id = o.user_id', 'LEFT')
            ->field([
                'o.user_id',
                'u.avatar',
                'u.nickname',
                'o.payment_time',
                'o.order_amount',
                'o.payment_amount',
                'o.free_flag'
            ]);

        if(isset($where['order_status'])){
            $query->where('o.order_status', 1);
        }
        if(isset($where['shop_id'])){
            $query->where('o.shop_id', $where['shop_id']);
        }
        if(isset($where['start_time'])){
            $query->where('payment_time', '>=', $where['start_time']);
        }
        if(isset($where['end_time'])){
            $query->where('payment_time', '<=', $where['end_time']);
        }

        $query->order('o.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);

        return $query->select()->toArray();
    }



}