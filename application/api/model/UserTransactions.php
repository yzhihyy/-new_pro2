<?php

namespace app\api\model;

use app\common\model\AbstractModel;

class UserTransactions extends AbstractModel
{
    /**
     * 获取店铺余额明细记录
     *
     * @param $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopTransactions($where)
    {
        $query = $this->alias('ut')
            ->field('ut.id as record_id, ut.amount, ut.type, ut.status, ut.bank_card_type, ut.generate_time,
                u.nickname, u.avatar')
            ->leftJoin('order o', 'o.id = ut.order_id')
            ->leftJoin('user u', 'u.id = o.user_id')
            ->where('ut.user_id', '=', $where['shop_user_id'])
            ->whereIn('ut.type', [2, 3])
            ->order('ut.generate_time', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);

        return $query->select()->toArray();
    }
}
