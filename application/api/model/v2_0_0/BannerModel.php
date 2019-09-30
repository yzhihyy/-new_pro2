<?php

namespace app\api\model\v2_0_0;

use app\common\model\AbstractModel;

class BannerModel extends AbstractModel
{
    protected $name = 'banner';

    public function getQuery($where)
    {
        $query = $this->alias('b')
            ->field([
                'b.id',
                'b.id as bannerId',
                'b.position',
                'b.title',
                'b.image',
                'b.thumb_image as thumbImage',
                'b.ad_link_type as adLinkType',
                'b.ad_link as adLink',
                'b.sort',
                'b.status',
                'b.generate_time as generateTime'
            ]);
        if (!empty($where) && is_array($where)) {
            foreach ($where as $field => $condition) {
                list($exp, $value) = $condition;
                switch ($field) {
                    case 'position':
                        switch ($exp) {
                            case '=':
                                $query->where('b.position', '=', $value);
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'status':
                        switch ($exp) {
                            case '=':
                                $query->where('b.status', '=', $value);
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
     * 获取banner列表
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBannerList($where = [])
    {
        $query = $this->getQuery($where);
        $query->order('sort', 'desc');
        return $query->select();
    }
}
