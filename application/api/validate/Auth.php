<?php

namespace app\api\validate;

use think\Validate;

class Auth extends Validate
{
    protected $rule = [
        'phone' => 'require|mobile',
        'code' => 'require|number',
        'type' => 'require|in:1,2',
        'longitude' => 'float',
        'latitude' => 'float'
    ];

    protected $message = [
        'phone.require' => '手机号不可为空',
        'phone.mobile' => '手机号格式有误',
        'code.require' => '验证码不可为空',
        'code.number' => '验证码格式有误',
        'type.require' => '登录类型不可为空',
        'type.in' => '登录类型有误',
        'longitude.float' => '经纬度格式有误',
        'latitude.float' => '经纬度格式有误',
    ];

    public function sceneLogin()
    {
        return $this;
    }
}