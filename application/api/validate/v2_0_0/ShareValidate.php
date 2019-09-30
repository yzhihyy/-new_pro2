<?php

namespace app\api\validate\v2_0_0;

use think\Validate;

class ShareValidate extends Validate
{
    protected $rule = [
        'type' => 'require|in:1,2,3,4,5',
    ];

    protected $message = [
        'type.require' => '分享类型不可为空',
        'type.in' => '分享类型错误',
    ];
}