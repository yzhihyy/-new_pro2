<?php

namespace app\api\validate\v2_0_0;

use think\Validate;

class SubAccountValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'phone' => ['require', 'mobile'],
        'code' => ['require', 'number'],

        'user_id' => ['require', 'number', 'gt' => 0],
        'remark' => ['require', 'max' => 10],
    ];

    // 错误信息
    protected $message = [
        'phone.require' => '手机号码不可为空',
        'phone.mobile' => '请填写正确的手机号码',
        'code.require' => '验证码不可为空',
        'code.number' => '验证码格式有误',

        'user_id.require' => '子账号用户ID不可为空',
        'user_id.number' => '子账号用户ID格式错误',
        'user_id.gt' => '子账号用户ID格式错误',
        'remark.require' => '备注不可为空',
        'remark.max' => '备注不可超过:rule个字符',
    ];

    // 添加子账号 - 检测
    public function sceneDetect()
    {
        return $this->only(['phone']);
    }

    // 添加子账号 - 验证码校验
    public function sceneCodeVerify()
    {
        return $this->only(['phone', 'code']);
    }

    // 设置子账号备注
    public function sceneSetRemark()
    {
        return $this->only(['user_id', 'remark']);
    }

    // 删除子账号
    public function sceneDelete()
    {
        return $this->only(['user_id']);
    }
}
