<?php

namespace app\api\model\v2_0_0;

use app\common\model\AbstractModel;
use think\exception\DbException;
use think\db\exception\{
    DataNotFoundException, ModelNotFoundException
};

class ShopNodeModel extends AbstractModel
{
    protected $name = 'shop_node';

    /**
     * 获取Query
     *
     * @return $this
     */
    public function getQuery()
    {
        return $this->alias('sn')
            ->field([
                'sn.id',
                'sn.id AS nodeId',
                'sn.node_name AS nodeName',
                'sn.parent_id AS parentId',
                'sn.action',
                'sn.action_alias AS actionAlias',
                'sn.sort',
                'sn.status',
                'sn.is_menu AS isMenu',
                'sn.generate_time AS generateTime',
            ]);
    }

    /**
     * 获取店铺节点
     *
     * @param array $where
     *
     * @return array
     *
     * @throws DbException
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function getShopNodes(array $where = [])
    {
        $query = $this->getQuery()->where('sn.status', 1);
        if (isset($where['isMenu']) && is_numeric($where['isMenu'])) {
            $query->where('sn.is_menu', $where['isMenu']);
        }

        return $query->select()->toArray();
    }
}
