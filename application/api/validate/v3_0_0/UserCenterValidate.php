<?php

namespace app\api\validate\v3_0_0;

use think\Validate;

class UserCenterValidate extends Validate
{

    protected $rule = [
        'user_id' => 'require|integer|gt:0',
        'longitude' => 'require|float|between:-180,180',
        'latitude' => 'require|float|between:-180,180',
    ];
    protected $message = [
        'user_id.require' => '用户ID不可为空',
        'user_id.integer' => '用户ID格式错误',
        'user_id.gt' => '用户ID必须大于:rule',
        'longitude.require' => '经纬度不可为空',
        'longitude.float' => '经纬度格式错误',
        'longitude.between' => '经纬度格式错误',
        'latitude.require' => '经纬度不可为空',
        'latitude.float' => '经纬度格式错误',
        'latitude.between' => '经纬度格式错误',
    ];

    public function sceneInfo()
    {
        return $this->only(['user_id', 'longitude', 'latitude']);
    }
}
