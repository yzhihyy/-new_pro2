<?php

namespace app\api\validate\v3_5_0;

use think\Validate;

class ShopValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'show_send_sms' => ['in' => '0,1'],
        'show_phone' => ['in' => '0,1'],
        'show_enter_shop' => ['in' => '0,1'],
        'show_address' => ['in' => '0,1'],
        'show_wechat' => ['in' => '0,1'],
        'show_qq' => ['in' => '0,1'],
        'qq' => ['max' => 18],
        'wechat' => ['max' => 30],
        'show_payment' => ['in' => '0,1'],
    ];

    // 错误信息
    protected $message = [
        'show_send_sms.in' => '显示发送短信配置错误',
        'show_phone.in' => '显示拨打电话配置错误',
        'show_enter_shop.in' => '显示进入店铺配置错误',
        'show_address.in' => '显示店铺地址配置错误',
        'show_wechat.in' => '显示微信号配置错误',
        'show_qq.in' => '显示QQ号配置错误',
        'qq.max' => 'QQ号长度不可超过18个字符',
        'wechat.max' => '微信号长度不可超过30个字符',
        'show_payment.in' => '显示买单模块配置错误',
    ];

    // 验证场景
    protected $scene = [
        // 店铺设置接口验证规则
        'shopSetting' => ['show_send_sms', 'show_phone', 'show_enter_shop', 'show_address', 'show_wechat', 'show_qq', 'qq', 'wechat', 'show_payment']
    ];
}