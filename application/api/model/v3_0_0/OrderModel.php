<?php

namespace app\api\model\v3_0_0;

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
        $query = $this->field([
            'sum(payment_amount) as totalMoney', // 在该店的总交易额
            'count(id) as totalCount', // 消费次数
            '(SUM(IF(order_type = 1, payment_amount, 0)) + SUM(prestore_amount)) / 
            (COUNT(IF(order_type = 1, true, null)) + COUNT(IF(order_type = 2, true, null))) as avgMoney', // 客均价
        ])
            ->where([
                'user_id' => $where['userId'],
                'shop_id' => $where['shopId'],
                'order_status' => 1
            ]);

        if (isset($where['orderType']) && $where['orderType']) {
            $query->where('order_type', $where['orderType']);
        }

        return $query->find();
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
            ->leftJoin('theme_activity ta', 'ta.id = o.theme_activity_id')
            ->field([
                'o.id as orderId',
                'o.order_type as orderType',
                'o.order_num as orderNum',
                'buyer.id as buyerId',
                'buyer.nickname as buyerNickname',
                'buyer.avatar as buyerAvatar',
                'buyer.thumb_avatar as buyerThumbAvatar',
                'o.payment_time as payTime',
                'o.order_amount as orderMoney',
                'o.payment_amount as payMoney',
                'o.prestore_amount as prestoreMoney',
                'o.verification_status as verificationStatus',
                'o.free_flag as freeFlag',
                'ta.id AS themeActivityId',
                'ta.theme_title AS themeActivityTitle'
            ])
            ->where('o.shop_id', $where['shopId'])
            ->where('o.order_status', 1);

        if (isset($where['userId']) && $where['userId']) {
            $query->where('o.user_id', $where['userId']);
        }

        if (isset($where['orderType']) && $where['orderType']) {
            $query->where('o.order_type', $where['orderType']);
        }

        $query->order('o.generate_time', 'DESC')
            ->order('o.id', 'DESC')
            ->limit($where['page'] * $where['limit'], $where['limit']);

        return $query->select()->toArray();
    }

    /**
     * 获取用户订单列表
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserOrderList(array $where)
    {
        $query = $this->alias('o')
            ->field([
                'o.id AS orderId',
                'o.order_type AS orderType',
                'o.order_num AS orderNum',
                'o.verification_status AS verificationStatus',
                'o.payment_amount AS paymentAmount',
                'o.prestore_amount AS prestoreAmount',
                'o.payment_time AS payTime',
                's.id AS shopId',
                's.shop_name AS shopName',
                's.shop_image AS shopImage',
                's.shop_thumb_image AS shopThumbImage',
                'ta.theme_title as themeTitle'
            ])
            ->join('shop s', 's.id = o.shop_id')
            ->leftJoin('theme_activity ta', 'ta.id = o.theme_activity_id')
            ->where('o.user_id', $where['userId'])
            ->where('o.order_status', 1)
            ->limit($where['page'] * $where['limit'], $where['limit']);

        switch ($where['status']) {
            // 全部订单
            case 0:
                break;
            // 待使用
            case 1:
                $query->where('o.verification_status', 0)
                    ->whereIn('o.order_type', [2, 3, 4]);
                break;
            // 核销中
            case 2:
                $query->where('o.verification_status', 1)
                    ->whereIn('o.order_type', [2, 3, 4]);
                break;
            // 已完成
            case 3:
                $query->where(function ($q) {
                    $q->where('o.order_type', 1)
                        ->whereOr(function ($sq) {
                            $sq->where('o.verification_status', 2)
                                ->whereIn('o.order_type', [2, 3, 4]);
                        });
                });
                break;
            default:
                return [];
        }

        return $query->order('o.generate_time', 'DESC')
            ->order('o.id', 'DESC')
            ->limit($where['page'] * $where['limit'], $where['limit'])
            ->select()
            ->toArray();
    }

    /**
     * 获取核销订单
     *
     * @param array $where
     *
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getVerificationOrder(array $where)
    {
        return $this->alias('o')
            ->where('o.id', $where['orderId'])
            ->where('o.user_id', $where['userId'])
            ->where('o.order_status', 1)
            ->whereIn('o.order_type', [2, 3, 4])
            ->find();
    }

   /**
     * 获取订单列表
     *
     * @param array $where
     *
     * @return array
     */
    public function getOrderList($where)
    {
        $sql = '';
        switch ($where['status']) {
            case 1;
                $sql = ' AND o.order_type IN (2, 3, 4) AND o.verification_status = 0';
                break;
            case 2;
                $sql = ' AND o.order_type IN (2, 3, 4) AND o.verification_status = 1';
                break;
            case 3;
                $sql = ' AND (o.order_type = 1 OR o.verification_status = 2)';
                break;
        }
        $query = $this->alias('o')
            ->field([
                'o.id AS orderId',
                'o.order_type AS orderType',
                'o.order_num AS orderNum',
                'o.verification_status AS verificationStatus',
                'o.payment_amount AS paymentAmount',
                'o.prestore_amount AS prestoreAmount',
                'o.payment_time AS payTime',
                'u.id AS userId',
                'u.nickname AS nickname',
                'u.avatar AS avatar',
                'u.thumb_avatar AS userThumbAvatar',
                'u.phone',
                'ta.id AS themeActivityId',
                'ta.theme_title AS themeActivityTitle'
            ])
            ->join('user u', 'u.id = o.user_id')
            ->leftJoin('theme_activity ta', 'ta.id = o.theme_activity_id')
            ->where('o.shop_id = '.$where['shopId'].' AND o.order_status = 1'.$sql)
            ->order('o.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }
}
