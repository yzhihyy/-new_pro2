<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class UserHasShopModel extends AbstractModel
{
    protected $name = 'user_has_shop';

   /**
     * 获取我的店铺列表
     *
     * @param array $where
     *
     * @return array
     */
    public function getMyShopList($where)
    {
        $query = $this->alias('uhs')
            ->join('shop s', 'uhs.shop_id = s.id')
            ->field([
                's.id as shopId',
                's.shop_name as shopName'
            ])
            ->where([
                'uhs.user_id' => $where['userId'],
                's.account_status' => 1,
                's.online_status' => 1,
            ])
            ->order('uhs.selected_shop_flag', 'desc')
            ->order('uhs.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }
}
