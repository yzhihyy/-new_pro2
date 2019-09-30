<?php

namespace app\api\validate;

use think\Validate;

class Shop extends Validate
{
    protected $rule = [
        'page' => 'number',
        'shop_category_id' => 'require|number',
        'shop_id' => 'require|number',
        'longitude' => 'require|float',
        'latitude' => 'require|float'
    ];

    protected $message = [
        'page.number' => '分页格式错误',
        'shop_category_id.require' => '店铺分类ID不可为空',
        'shop_category_id.number' => '店铺分类ID格式错误',
        'shop_id.require' => '店铺ID不可为空',
        'shop_id.number' => '店铺ID格式错误',
        'longitude.require' => '缺少经纬度',
        'longitude.float' => '经度格式有误',
        'latitude.require' => '缺少经纬度',
        'latitude.float' => '纬度格式有误',
    ];

    public function sceneGetShopList()
    {
        return $this->only(['page', 'shop_category_id', 'longitude', 'latitude']);
    }

    public function sceneGetShopDetail()
    {
        return $this->only(['shop_id', 'longitude', 'latitude']);
    }
}