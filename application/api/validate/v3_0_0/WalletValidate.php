<?php

namespace app\api\validate\v3_0_0;

use think\Validate;

class WalletValidate extends Validate
{
    // 错误信息
    protected $message = [
        'third_party_type.require' => '参数缺失',
        'third_party_type.in' => '参数错误',
        'nickname.require' => '昵称不可为空',
        'unionid.require' => '参数缺失',
        'unionid.max' => '参数错误',
        'openid.require' => '参数缺失',
        'openid.max' => '参数错误',

        'withdraw_amount.require' => '提现金额不可为空',
        'withdraw_amount.float' => '请填写正确的金额',
    ];

    /**
     * 提现绑定第三方
     *
     * @return $this
     */
    public function sceneWithdrawBindThirdParty()
    {
        return $this->append([
            'third_party_type' => 'require|in:1',
            'nickname' => 'require|max:50',
            'unionid' => 'require|max:64',
            'openid' => 'require|max:64',
        ]);
    }

    /**
     * 用户提现
     *
     * @return $this
     */
    public function sceneWithdraw()
    {
        return $this->append([
            'withdraw_amount' => 'require|float',
            'code' => 'require'
        ]);
    }
}