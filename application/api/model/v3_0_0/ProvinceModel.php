<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class ProvinceModel extends AbstractModel
{
    protected $name = 'province';

    /**
     * 获取省市区id
     *
     * @param array $where
     * @return array
     */
    public function getCityId($where)
    {
        $query = $this->alias('p')
            ->leftJoin('fo_city c', 'p.id = c.province_id')
            ->leftJoin('fo_area a', 'c.id = a.city_id')
            ->field([
                'p.id as provinceId',
                'p.name as provinceName',
                'c.id as cityId',
                'c.name as cityName',
                'a.id as areaId',
                'a.name as areaName',
            ])
            ->where([
                ['p.name', 'like', '%'.$where['province'].'%'],
                ['c.name', 'like', '%'.$where['city'].'%'],
                ['a.name', 'like', '%'.$where['area'].'%']
            ]);
        return $query->find();
    }
}
