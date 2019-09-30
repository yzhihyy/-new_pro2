<?php

namespace app\api\validate\v1_1_0;

use think\Validate;

class Auth extends Validate
{
    protected $rule = [
        'unionid' => 'require|max:64',
        'phone' => 'require|mobile',
        'code' => 'require|number',
        'user_type' => 'require|in:1,2',
        'longitude' => 'float|between:-180,180',
        'latitude' => 'float|between:-180,180',
        'login_type' => 'require|in:1,2',
    ];

    protected $message = [
        'unionid.require' => 'unionid不可为空',
        'unionid.max' => 'unionid最长不可超过64',

        'phone.require' => '手机号不可为空',
        'phone.mobile' => '手机号格式错误',

        'code.require' => '验证码不可为空',
        'code.number' => '验证码错误',

        'user_type.require' => '用户类型不可为空',
        'user_type.in' => '用户类型错误',

        'longitude.float' => '经纬度格式有误',
        'longitude.between' => '经纬度格式有误',

        'latitude.float' => '经纬度格式有误',
        'latitude.between' => '经纬度格式有误',

        'login_type.require' => '登录类型不可为空',
        'login_type.in' => '登录类型错误',
        'login_type.eq' => '登录类型错误'
    ];

    /**
     * 微信登录验证
     *
     * @return $this
     */
    public function sceneLogin1()
    {
        return $this
            ->only(['unionid', 'login_type'])
            ->remove('login_type', 'in')
            ->append('login_type', 'eq:1');
    }

    /**
     * 手机号登录验证
     *
     * @return $this
     */
    public function sceneLogin2()
    {
        return $this
            ->only(['phone', 'code', 'user_type', 'longitude', 'latitude', 'login_type'])
            ->remove('login_type', 'in')
            ->append('login_type', 'eq:2');
    }

    /**
     * 微信绑定手机验证
     *
     * @return $this
     */
    public function sceneWechatBindPhone()
    {
        return $this->only(['unionid', 'phone', 'code']);
    }

    /**
     * 手机绑定微信验证
     *
     * @return $this
     */
    public function scenePhoneBindWechat()
    {
        return $this->only(['unionid']);
    }
}