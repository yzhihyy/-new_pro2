<?php

namespace app\api\model\v3_5_0;

use app\common\model\AbstractModel;

class FollowRelationModel extends AbstractModel
{
    protected $name = 'follow_relation';

   /**
     * 获取我关注的店铺列表
     *
     * @param array $where
     *
     * @return array
     */
    public function getFollowShopList($where)
    {
        $query = $this->alias('fr')
            ->join('shop s', 'fr.to_shop_id = s.id')
            ->field([
                's.id as shopId',
                's.shop_name',
                's.shop_thumb_image',
                's.shop_address',
            ])
            ->where([
                'fr.from_user_id' => $where['userId'],
                'fr.rel_type' => 1,
                's.account_status' => 1,
                's.online_status' => 1,
            ])
            ->order('fr.generate_time', 'desc')
            ->order('fr.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        if (!empty($where['keyword'])) {
            $query->where('s.shop_name', 'like', "%{$where['keyword']}%");
        }
        return $query->select()->toArray();
    }
}
