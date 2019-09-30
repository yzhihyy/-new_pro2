<?php

namespace app\api\model\v2_0_0;

use app\common\model\AbstractModel;

class FreeRuleModel extends AbstractModel
{
    protected $name = 'free_rule';

    /**
     * Get Query.
     *
     * @return $this
     */
    public function getQuery()
    {
        return $this->alias('fr')
            ->field([
                'fr.id AS freeRuleId',
                'fr.user_id AS userId',
                'fr.shop_id AS shopId',
                'fr.shop_free_order_frequency AS shopFreeOrderFrequency',
                'fr.order_count AS orderCount',
                'fr.consume_amount AS consumeAmount',
                'fr.status AS freeRuleStatus',
                'fr.free_money AS freeMoney',
                'fr.generate_time AS generateTime',
                'fr.update_time AS updateTime',
            ]);
    }

    /**
     * 查询用户在店铺的免单规则
     *
     * @param array $where
     * @return array|null|\PDOStatement|string|\think\Model
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getFreeRule($where = [])
    {
        return $this->where($where)->find();
    }

    /**
     * 获取免单规则指定列分页数据
     *
     * @param array $where
     *
     * @return array
     */
    public function getRuleColumnPagination($where = [])
    {
        $query = $this->where('user_id', $where['userId'])
            ->where('shop_id', $where['shopId'])
            ->limit($where['page'] * $where['limit'], $where['limit'])
            ->order('generate_time', 'desc');

        return $query->column(['consume_amount'], 'id');
    }

    /**
     * 获取免单卡列表
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getFreeCardList($where)
    {
        $query = $this->alias('fr')
            ->leftJoin('shop s', 's.id = fr.shop_id')
            ->field([
                'fr.id as freeRuleId',
                'fr.shop_id as shopId',
                'LEAST(fr.shop_free_order_frequency, s.free_order_frequency) as freeOrderFrequency',
                'fr.order_count as orderCount',
                'fr.consume_amount as consumeAmount',
                'fr.status',
                'fr.free_money as freeMoney',
                's.shop_name as shopName',
                's.shop_image as shopImage',
                's.shop_thumb_image as shopThumbImage',
            ])
            ->where([
                'fr.user_id' => $where['userId']
            ])
            ->order([
                'fr.update_time' => 'DESC',
                'fr.id' => 'DESC',
            ])
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select();
    }

    /**
     * 免单卡详情
     *
     * @param $where
     *
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getFreeCardDetail($where)
    {
        $query = $this->alias('fr')
            ->leftJoin('shop s', 's.id = fr.shop_id')
            ->field([
                'fr.id as freeRuleId',
                'fr.shop_id as shopId',
                'LEAST(fr.shop_free_order_frequency, s.free_order_frequency) as freeOrderFrequency',
                'fr.order_count as orderCount',
                'fr.consume_amount as consumeAmount',
                'fr.status',
                'fr.free_money as freeMoney',
                's.shop_name as shopName',
                's.shop_image as shopImage',
                's.shop_thumb_image as shopThumbImage',
            ])
            ->where([
                'fr.user_id' => $where['userId'],
                'fr.id' => $where['freeRuleId']
            ]);
        return $query->find();
    }

    /**
     * 获取用户在指定店铺里有效的免单规则
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserValidRuleAtShop($where = [])
    {
        return $this->field(true)
            ->where('user_id', $where['userId'])
            ->where('shop_id', $where['shopId'])
            ->where('status', 1)
            ->order('generate_time', 'desc')
            ->find();
    }
}
