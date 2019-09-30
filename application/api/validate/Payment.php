<?php

namespace app\api\validate;

use think\Validate;

class Payment extends Validate
{
    // 验证规则
    protected $rule = [
        'shop_id' => ['require', 'integer'],
        'order_money' => ['require', 'egt' => 1],
        'payment_type' => ['in' => '1,2'],
        'payment_money' => ['egt' => 0.01],
        'order_number' => ['require'],
    ];

    // 错误信息
    protected $message = [
        'shop_id.require' => '店铺id不可为空',
        'shop_id.integer' => '店铺id格式错误',

        'order_money.require' => '订单金额不可为空',
        'order_money.egt' => '订单金额不能小于1元钱',

        'payment_type.in' => '支付类型错误',
        'payment_money.egt' => '支付金额不能小于1分钱',

        'order_number.require' => '订单编号不可为空',
    ];

    // 验证场景
    protected $scene = [
        // 准备支付接口验证规则
        'preparePayment' => ['shop_id'],
        // 支付接口验证规则
        'payment' => ['shop_id', 'order_money', 'payment_type', 'payment_money'],
        // 获取订单状态接口验证规则
        'getOrderStatus' => ['order_number'],
    ];
}