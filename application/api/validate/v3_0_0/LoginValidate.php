<?php

namespace app\api\validate\v3_0_0;

use app\api\model\v3_7_0\UserModel;
use think\Validate;

class LoginValidate extends Validate
{

    protected $rule = [
        'unionid' => 'require|max:64',
        'nickname' => 'require',
        'phone' => 'require|mobile',
        'code' => 'require|number',
        'longitude' => 'float|between:-180,180',
        'latitude' => 'float|between:-180,180',
        'login_type' => 'require|in:1,2,3,4', // 1手机登录，2微信登录，3QQ登录，4H5邀请登录
        'third_party_type' => 'require|in:2,3', // 绑定&&解绑，2微信，3QQ
        'invite_code' => 'length:6|inviteCodeCheck',
    ];

    protected $message = [
        'unionid.require' => 'unionid不可为空',
        'unionid.max' => 'unionid最长不可超过:rule',

        'nickname.require' => '昵称不可为空',

        'phone.require' => '手机号不可为空',
        'phone.mobile' => '手机号格式错误',

        'code.require' => '验证码不可为空',
        'code.number' => '验证码错误',

        'longitude.float' => '经纬度格式有误',
        'longitude.between' => '经纬度范围有误',

        'latitude.float' => '经纬度格式有误',
        'latitude.between' => '经纬度范围有误',

        'login_type.require' => '登录类型不可为空',
        'login_type.in' => '登录类型错误',
        'login_type.eq' => '登录类型错误',

        'third_party_type.require' => '登录类型不可为空',
        'third_party_type.in' => '登录类型错误',
        'third_party_type.eq' => '登录类型错误',

        'invite_code.length' => '邀请码必须为6位',
        'invite_code.inviteCodeCheck' => '邀请码不存在',
    ];

    /**
     * 校验邀请码
     * @param $code
     * @return bool
     */
    protected function inviteCodeCheck($code)
    {
        $find = model(UserModel::class)->where('invite_code', $code)->value('id');
        if(!empty($find)){
            return true;
        }
        return false;
    }

    /**
     * 微信登录验证.
     *
     * @return $this
     */
    public function sceneWechatLogin()
    {
        return $this->only([
            'unionid',
            'login_type',
            'invite_code',
        ])
            ->remove('login_type', 'in')
            ->append('login_type', 'eq:2');
    }

    /**
     * QQ登录验证.
     *
     * @return $this
     */
    public function sceneQQLogin()
    {
        return $this->only([
            'unionid',
            'login_type',
            'invite_code',
        ])
            ->remove('login_type', 'in')
            ->append('login_type', 'eq:3');
    }

    /**
     * APP手机号登录验证.
     *
     * @return $this
     */
    public function scenePhoneLogin()
    {
        return $this->only([
            'phone',
            'code',
            'longitude',
            'latitude',
            'login_type',
            'invite_code',
        ])
            ->remove('login_type', 'in')
            ->append('login_type', 'eq:1');
    }

    /**
     * H5邀请登录验证.
     *
     * @return $this
     */
    public function sceneH5InviteLogin()
    {
        return $this->only([
            'phone',
            'code',
            'login_type',
            'invite_code'
        ])
            ->remove('login_type', 'in')
            ->append('login_type', 'eq:4');
    }

    /**
     * 第三方绑定手机验证
     *
     * @return $this
     */
    public function sceneThirdPartyBindPhone()
    {
        return $this->only([
            'third_party_type',
            'unionid',
            'nickname',
            'phone',
            'code',
            'avatar',
            'invite_code',
        ]);
    }

    /**
     * 手机绑定第三方验证
     *
     * @return $this
     */
    public function scenePhoneBindThirdParty()
    {
        return $this->only([
            'third_party_type',
            'unionid',
            'nickname',
        ]);
    }

    /**
     * 解绑第三方验证.
     *
     * @return $this
     */
    public function sceneUnBindThirdParty()
    {
        return $this->only([
            'third_party_type',
        ]);
    }
}
