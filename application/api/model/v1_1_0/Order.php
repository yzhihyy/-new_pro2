<?php

namespace app\api\model\v1_1_0;

use app\api\model\Order as CommonOrder;

class Order extends CommonOrder
{
    /**
     * 获取订单信息
     * @param array $where
     * @return array
     */
    public function getOrderInfo($where = [])
    {
        $condition = [
            'o.user_id' => $where['userId'],
            'o.order_num' => $where['orderNum']
        ];
        $query = $this->alias('o')->leftJoin('shop s', 'o.shop_id = s.id')
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

    /**
     * 获取一轮免单的订单记录
     * @param array $where
     * @return array
     */
    public function getFreeRuleOrderList($where = [])
    {
        $condition = [
            'o.user_id' => $where['userId'],
            'o.free_rule_id' => $where['freeRuleId'],
            'o.order_status' => 1
        ];
        $query = $this->alias('o')
            ->field([
                'o.payment_amount',
                'o.payment_time'
            ])
            ->where($condition)
            ->select()->toArray();
        return $query;
    }
}