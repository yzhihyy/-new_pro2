<?php

namespace app\api\model\v3_7_0;

use app\common\model\AbstractModel;

class UserTransactionsModel extends AbstractModel
{
    protected $name = 'user_transactions';

    /**
     * 获取用户余额明细记录
     *
     * @param array $where
     *
     * @return array
     */
    public function getUserTransactions(array $where = [])
    {
        return $this->alias('ut')
            ->field([
                'ut.id as transactionId',
                'ut.amount',
                'ut.type',
                'ut.status',
                'ut.generate_time AS generateTime',
                's.id AS shopId',
                's.shop_name AS shopName',
                's.shop_thumb_image AS shopThumbImage',
                'u.id AS sendUserId',
                'u.thumb_avatar AS sendUserThumbAvatar',
                'u.nickname AS sendUserNickname',
                'ls.start_time',
                'ls.end_time'
            ])
            ->leftJoin('theme_vote_record tvr', 'tvr.id = ut.order_id')
            ->leftJoin('live_show ls', 'ls.id = ut.order_id')
            ->leftJoin('user u', 'u.id = ls.send_user_id')
            ->leftJoin('shop s', 's.id = tvr.shop_id')
            ->where('ut.user_id', $where['userId'])
            ->whereIn('ut.type', [4, 5, 8])
            ->order('ut.generate_time', 'DESC')
            ->order('ut.id', 'DESC')
            ->limit($where['page'] * $where['limit'], $where['limit'])
            ->select()
            ->toArray();
    }
}
