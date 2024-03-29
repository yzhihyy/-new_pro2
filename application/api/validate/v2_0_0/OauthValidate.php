<?php

namespace app\api\validate\v2_0_0;

use think\Validate;

class OauthValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'wx_code' => ['require']
    ];

    // 错误信息
    protected $message = [
        'wx_code.require' => '微信授权code不可为空'
    ];

    // 验证场景
    protected $scene = [
        // 准备支付接口验证规则
        'getWxAuth' => ['wx_code']
    ];
}