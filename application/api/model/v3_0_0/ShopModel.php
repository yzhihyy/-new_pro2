<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class ShopModel extends AbstractModel
{

    protected $name = 'shop';

    /**
     * 查询店铺信息
     * @param array $where
     * @return array
     */
    public function getShopInfo($where = [])
    {
        if (!isset($where['account_status'])) {
            $where['account_status'] = 1;
        }
        if (!isset($where['online_status'])) {
            $where['online_status'] = 1;
        }
        return $this->where($where)->find();
    }
}
