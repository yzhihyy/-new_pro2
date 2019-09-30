<?php

namespace app\api\validate;

use think\Validate;

class Merchant extends Validate
{
    // 错误信息
    protected $message = [
        'shop_name.require' => '店铺名称不可为空',
        'phone.require' => '电话号码不可为空',
        'phone.mobile' => '请填写正确的手机号码',
        'address.require' => '店铺地址不可为空',
        'order_frequency.require' => '订单次数不可为空',
        'order_frequency.integer' => '订单次数格式错误',
        'order_frequency.egt' => '免单次数不能小于2次',
        'order_frequency.elt' => '免单次数不能大于9次',
        'holder_name.require' => '持卡人姓名不可为空',
        'bankcard_num.require' => '银行卡号不可为空',
        'identity_card_num.require' => '身份证号不可为空',
        'code.require' => '手机验证码不可为空',
        'withdraw_amount.require' => '提现金额不可为空',
        'withdraw_amount.float' => '请填写正确的金额',
    ];

    // 申请成为商家接口验证规则
    public function sceneApplyMerchant()
    {
        return $this->append('shop_name', 'require')
            ->append('phone', 'require|mobile')
            ->append('address', 'require');
    }

    // 免单设置接口验证规则
    public function sceneFreeSetting()
    {
        return $this->append('order_frequency', 'require|integer|egt:2|elt:9');
    }

    // 银行卡四元素校验
    public function sceneBankcard4Verify()
    {
        return $this->append('holder_name', 'require')
            ->append('identity_card_num', 'require')
            ->append('bankcard_num', 'require')
            ->append('phone', 'require|mobile');
    }

    // 提现银行卡校验验证码确认
    public function sceneBankcardCodeVerify()
    {
        return $this->append('phone', 'require|mobile')
            ->append('code', 'require')
            ->append('bankcard_num', 'require');
    }

    // 提现银行卡更换持卡人
    public function sceneChangeCardholder()
    {
        return $this->append('identity_card_num', 'require')
            ->append('code', 'require');
    }

    // 商家提现
    public function sceneWithdraw()
    {
        return $this->append('withdraw_amount', 'require|float')
            ->append('phone', 'require|mobile')
            ->append('code', 'require');
    }
}
