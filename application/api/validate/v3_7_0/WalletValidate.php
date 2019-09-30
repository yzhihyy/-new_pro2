<?php

namespace app\api\validate\v3_7_0;

use think\Validate;

class WalletValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'copper_id' => ['require', 'integer'],
        'payment_type' => ['require', 'in' => '1,2,3'],
        'payment_money' => ['require', 'egt' => 0.01]
    ];

    // 错误信息
    protected $message = [
        'copper_id.require' => '铜板id不可为空',
        'copper_id.integer' => '铜板id格式错误',
        'payment_type.require' => '支付类型不可为空',
        'payment_type.in' => '支付类型错误',
        'payment_money.require' => '支付金额不可为空',
        'payment_money.egt' => '支付金额不能小于1分钱',
    ];

    // 验证场景
    protected $scene = [
        // 充值接口验证规则
        'recharge' => ['copper_id', 'payment_type', 'payment_money'],
    ];
}