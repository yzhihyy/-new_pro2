<?php

namespace app\api\validate\v3_0_0;

use think\Validate;

class PaymentValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'shop_id' => ['require', 'integer'],
        'payment_type' => ['in' => '1,2'],
        'payment_money' => ['egt' => 0.01],
        'remark' => ['max' => 50],
        'theme_activity_id' => ['integer'],

        'order_number' => ['require'],
    ];

    // 错误信息
    protected $message = [
        'shop_id.require' => '店铺id不可为空',
        'shop_id.integer' => '店铺id格式错误',
        'theme_activity_id.integer' => '主题活动id格式错误',

        'payment_type.in' => '支付类型错误',
        'payment_money.egt' => '支付金额不能小于1分钱',
        'remark.max' => '备注长度不可超过50个字符',
        'order_number.require' => '订单编号不可为空',
    ];

    // 验证场景
    protected $scene = [
        // 准备支付接口验证规则
        'prePayment' => ['shop_id'],
        // 支付接口验证规则
        'paying' => ['shop_id', 'payment_type', 'payment_money', 'remark', 'theme_activity_id'],
        // 支付完成接口验证规则
        'finishPayment' => ['order_number']
    ];
}