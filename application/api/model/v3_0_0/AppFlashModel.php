<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;
use app\common\utils\date\DateHelper;

class AppFlashModel extends AbstractModel
{
    protected $name = 'app_flash';

    /**
     * 获取闪屏页，顺序，城市/省份/全国
     * @param $params
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function flashShow($params)
    {
        $nowTime = DateHelper::getNowDateTime()->format('Y-m-d H:i:s');
        $query = $this->field(['image_url','second','title','link_type','link_url','shop_id'])
            ->where('status', '=', 0)
            ->where('forbidden', '=', 0)
            ->where('start_time', '<=', $nowTime)
            ->where('end_time', '>=', $nowTime);

        $provinceQuery = clone $query;
        $countryQuery = clone $query;

        if(!empty($params['city_id'])){
            $find = $query->where('city_id', '=', $params['city_id'])->order('id', 'desc')->find();
        }

        if(empty($find) && !empty($params['province_id'])){
            $find = $provinceQuery->where('province_id', '=', $params['province_id'])->where('city_id', '=', 0)->order('id', 'desc')->find();
        }
        if(empty($find)){
            $find = $countryQuery
                ->where('show_area', '=', 1)
                ->order('id', 'desc')
                ->find();
        }

        return $find ?? [];
    }
}
