<?php

namespace app\api\model\v2_0_0;

use app\common\model\AbstractModel;

class UserTransactionsModel extends AbstractModel
{
    protected $name = 'user_transactions';

    /**
     * 获取店铺余额明细记录
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopTransactions(array $where = [])
    {
        return $this->alias('ut')
            ->field([
                'ut.id as recordId',
                'ut.amount',
                'ut.type',
                'ut.status',
                'ut.bank_card_type as bankCardType',
                'ut.generate_time as generateTime',
                'u.nickname',
                'u.avatar',
                'ta.id as themeActivityId',
                'ta.theme_title as themeActivityTitle'
            ])
            ->leftJoin('order o', 'o.id = ut.order_id')
            ->leftJoin('theme_activity ta', 'o.theme_activity_id = ta.id')
            ->leftJoin('user u', 'u.id = o.user_id')
            ->where('ut.shop_id', $where['shopId'])
            ->whereIn('ut.type', [2, 3, 6, 7])
            ->order('ut.generate_time', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit'])
            ->select()
            ->toArray();
    }
}
