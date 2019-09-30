<?php

namespace app\api\validate\v1_1_0;

use think\Validate;

class Payment extends Validate
{
    // 验证规则
    protected $rule = [
        'order_number' => ['require'],
    ];

    // 错误信息
    protected $message = [
        'order_number.require' => '订单编号不可为空',
    ];

    // 验证场景
    protected $scene = [
        // 获取订单状态接口验证规则
        'paymentCompleted' => ['order_number'],
    ];
}