<?php

namespace app\api\validate\v3_0_0;

use think\Validate;

class ShopValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'pay_setting_type' => ['require', 'in' => '1,2,3'],

        'order_number' => ['require'],

        'shop_id' => ['require', 'integer'],
    ];

    // 错误信息
    protected $message = [
        'pay_setting_type.require' => '设置类型不能为空',
        'pay_setting_type.in' => '设置类型错误',

        'order_number.require' => '订单编号不可为空',

        'shop_id.require' => '店铺id不可为空',
        'shop_id.integer' => '店铺id格式错误',
    ];

    // 验证场景
    protected $scene = [
        // 买单设置接口验证规则
        'paySetting' => ['pay_setting_type'],
        // 订单核销接口验证规则
        'orderWriteOff' => ['order_number'],
        // 获取商家信息接口验证规则
        'getShopInfo' => ['shop_id'],
    ];
}