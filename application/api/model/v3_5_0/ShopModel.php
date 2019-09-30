<?php

namespace app\api\model\v3_5_0;

use app\common\model\AbstractModel;

class ShopModel extends AbstractModel
{

    protected $name = 'shop';

   /**
     * 搜索店铺
     *
     * @param array $where
     *
     * @return array
     */
    public function searchShopList($where)
    {
        $query = $this->alias('s')
            ->field([
                's.id as shopId',
                's.shop_name',
                's.shop_thumb_image',
                's.shop_address',
            ])
            ->where([
                's.account_status' => 1,
                's.online_status' => 1,
            ])
            ->order('s.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        if (!empty($where['keyword'])) {
            $query->where('s.shop_name', 'like', "%{$where['keyword']}%");
        }
        return $query->select()->toArray();
    }
}
