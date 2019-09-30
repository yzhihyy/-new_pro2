<?php

namespace app\api\validate\v2_0_0;

use think\Validate;

class HomeValidate extends Validate
{
    protected $rule = [
        'longitude' => 'require|float|between:-180,180',
        'latitude' => 'require|float|between:-180,180',
        'sort' => 'require|in:1,2,3',
    ];

    protected $message = [
        'longitude.require' => '经纬度不可为空',
        'longitude.float' => '经纬度格式有误',
        'longitude.between' => '经纬度范围有误',

        'latitude.require' => '经纬度不可为空',
        'latitude.float' => '经纬度格式有误',
        'latitude.between' => '经纬度范围有误',

        'sort.require' => '排序方式不可为空',
        'sort.in' => '排序方式错误',
    ];

    protected $scene = [
        'joyList' => ['longitude', 'latitude', 'sort']
    ];
}