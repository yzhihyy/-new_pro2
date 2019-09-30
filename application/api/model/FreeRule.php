<?php

namespace app\api\model;

use app\common\model\AbstractModel;

class FreeRule extends AbstractModel
{
    /**
     * 查询用户在店铺的免单规则
     * @param array $where
     * @return array
     */
    public function getFreeRule($where = [])
    {
        return $this->where($where)->find();
    }

    public function getFreeRuleInfo($where)
    {
        $query = $this->alias('fr')
            ->field([
                'fr.id',
                'fr.user_id as userId',
                'fr.shop_id as shopId',
                'fr.shop_free_order_frequency as shopFreeOrderFrequency',
                'fr.order_count as orderCount',
                'fr.consume_amount as consumeAmount'
            ])
            ->where([
                ['fr.user_id', '=', $where['user_id']],
                ['fr.shop_id', '=', $where['shop_id']],
                ['fr.status', '=', 1]
            ]);
        return $query->find();
    }

    /**
     * 查询用户最近可免单的信息
     * @param array $where
     * @return array
     */
    public function getNearestFreeInfo($where = [])
    {
        $query = $this->alias('fr')
            ->field([
                'fr.shop_id',
                'fr.shop_free_order_frequency - fr.order_count' => 'surplus_count',
                'fr.shop_free_order_frequency',
                'fr.order_count',
                'fr.consume_amount',
                's.shop_name',
                's.shop_image',
                's.shop_thumb_image'
            ])
            ->leftJoin('shop s','s.id = fr.shop_id')
            ->where([
                'fr.user_id' => $where['user_id'],
                'fr.status' => 1,
                's.online_status' => 1,
                's.account_status' => 1
            ])
            ->order([
                'surplus_count' => 'ASC',
                'fr.id' => 'DESC',
            ])
            ->find();
        return $query;
    }
}