<?php

namespace app\api\validate\v2_0_0;

use think\Validate;

class BankcardValidate extends Validate
{
    // 错误信息
    protected $message = [
        'phone.require' => '电话号码不可为空',
        'phone.mobile' => '请填写正确的手机号码',
        'code.require' => '手机验证码不可为空',
        'holder_name.require' => '持卡人姓名不可为空',
        'bankcard_num.require' => '银行卡号不可为空',
        'identity_card_num.require' => '身份证号不可为空',
        'withdraw_amount.require' => '提现金额不可为空',
        'withdraw_amount.float' => '请填写正确的金额',
    ];

    // 银行卡四元素校验
    public function sceneBankcard4Verify()
    {
        return $this->append([
            'holder_name' => 'require',
            'identity_card_num' => 'require',
            'bankcard_num' => 'require',
            'phone' => 'require|mobile',
        ]);
    }

    // 提现银行卡校验验证码确认
    public function sceneBankcardCodeVerify()
    {
        return $this->append([
            'phone' => 'require|mobile',
            'code' => 'require',
            'bankcard_num' => 'require'
        ]);
    }

    // 提现银行卡更换持卡人
    public function sceneChangeCardholder()
    {
        return $this->append([
            'identity_card_num' => 'require',
            'code' => 'require'
        ]);
    }

    // 提现
    public function sceneWithdraw()
    {
        return $this->append([
            'withdraw_amount' => 'require|float',
            'code' => 'require'
        ]);
    }
}
