<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class AreaModel extends AbstractModel
{
    protected $name = 'area';

    /**
     * 根据adcode获取省市区id
     *
     * @param array $where
     * @return array
     */
    public function getCityIdByAdcode($where)
    {
        $query = $this->alias('a')
            ->rightJoin('city c', 'c.id = a.city_id')
            ->field([
                'c.province_id as provinceId',
                'c.id as cityId',
                'c.name as cityName',
                'a.id as areaId',
                'a.name as areaName',
            ])
            ->where([
                'a.code' => $where['adcode'],
            ]);
        return $query->find();
    }
}
