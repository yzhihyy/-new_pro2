<?php

namespace app\api\validate;

use think\Validate;

class Captcha extends Validate
{
    protected $rule = [
        'phone' => 'require|mobile',
        'code_content' => 'require',
        'code_token' => 'require'
    ];

    protected $message = [
        'phone.require' => '手机号不可为空',
        'phone.mobile' => '手机号格式有误',
        'code_content.require' => '验证码前置算法，结果不可为空',
        'code_token.require' => '验证码前置算法，结果不可为空',
    ];

    public function sceneGetLoginCaptcha()
    {
        return $this;
    }
}