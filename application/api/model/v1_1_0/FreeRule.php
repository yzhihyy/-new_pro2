<?php

namespace app\api\model\v1_1_0;

use app\common\model\AbstractModel;

class FreeRule extends AbstractModel
{
    /**
     * 查询免单规则列表
     * @param array $where
     * @return array
     */
    public function getFreeRuleList($where = [])
    {
        $query = $this->alias('fr')
            ->field([
                'fr.id as freeRuleId',
                'fr.shop_id',
                'fr.shop_free_order_frequency',
                'fr.order_count',
                'fr.consume_amount',
                'fr.status',
                's.shop_name',
                's.shop_image',
                's.shop_thumb_image'
            ])
            ->leftJoin('shop s','s.id = fr.shop_id')
            ->where([
                'fr.user_id' => $where['userId']
            ])
            ->order([
                'fr.update_time' => 'DESC',
                'fr.id' => 'DESC',
            ])
            ->limit($where['page'] * $where['limit'], $where['limit'])
            ->select()->toArray();
        return $query;
    }

    /**
     * 查询免单规则详情
     * @param array $where
     * @return array
     */
    public function getFreeRuleDetail($where = [])
    {
        $query = $this->alias('fr')
            ->field([
                'fr.shop_id',
                'fr.shop_free_order_frequency',
                'fr.order_count',
                'fr.consume_amount',
                'fr.status',
                's.shop_name',
                's.shop_image',
                's.shop_thumb_image'
            ])
            ->leftJoin('shop s','s.id = fr.shop_id')
            ->where([
                'fr.user_id' => $where['userId'],
                'fr.id' => $where['freeRuleId']
            ])
            ->find();
        return $query;
    }
}