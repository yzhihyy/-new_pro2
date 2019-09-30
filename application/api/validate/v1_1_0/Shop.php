<?php

namespace app\api\validate\v1_1_0;

use think\Validate;

class Shop extends Validate
{
    // 验证规则
    protected $rule = [
        'activity_id' => 'require|integer|gt:0',
        'latitude' => 'require|float|between:-180,180',
        'longitude' => 'require|float|between:-180,180',
        'sort' => 'require|in:1,2,3',
        'shop_category_id' => 'require|integer|gt:0'
    ];

    // 错误信息
    protected $message = [
        'activity_id.require' => '活动不存在',
        'activity_id.integer' => '活动不存在',
        'activity_id.gt' => '活动不存在',

        'latitude.require' => '经纬度格式错误',
        'latitude.float' => '经纬度格式错误',
        'latitude.between' => '经纬度格式错误',

        'longitude.require' => '经纬度格式错误',
        'longitude.float' => '经纬度格式错误',
        'longitude.between' => '经纬度格式错误',

        'sort.require' => '排序方式不可为空',
        'sort.in' => '排序方式错误',

        'shop_category_id.require' => '店铺分类不存在',
        'shop_category_id.integer' => '店铺分类不存在',
        'shop_category_id.gt' => '店铺分类不存在',
    ];

    public function sceneGetShopActivityDetail()
    {
        return $this->only(['activity_id', 'latitude', 'longitude']);
    }

    public function sceneGetShopList()
    {
        return $this->only(['shop_category_id', 'longitude', 'latitude', 'sort']);
    }

    public function sceneGetShopDetail()
    {
        return $this->only(['shop_id', 'longitude', 'latitude']);
    }
}
