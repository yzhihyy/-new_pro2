<?php

namespace app\api\model\v2_0_0;

use app\common\model\AbstractModel;

class ShopCategoryModel extends AbstractModel
{
    protected $name = 'shop_category';

    public function getQuery($where)
    {
        $query = $this->alias('sc')
            ->field([
                'sc.id',
                'sc.id as shopCategoryId',
                'sc.name',
                'sc.image',
                'sc.thumb_image as thumbImage',
                'sc.sort',
                'sc.status',
                'sc.generate_time as generateTime'
            ]);
        if (!empty($where) && is_array($where)) {
            foreach ($where as $field => $condition) {
                list($exp, $value) = $condition;
                switch ($field) {
                    case 'name':
                        switch ($exp) {
                            case '=':
                                $query->where('sc.name', '=', $value);
                                break;
                            case 'like':
                                $query->where('sc.name', 'like', $value);
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'status':
                        switch ($exp) {
                            case '=':
                                $query->where('sc.status', '=', $value);
                                break;
                            default:
                                break;
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        return $query;
    }

    /**
     * 获取分类列表
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getShopCategoryList($where = [])
    {
        $query = $this->getQuery($where);
        $query->order([
            'sc.sort' => 'asc'
        ]);
        return $query->select();
    }
}
