<?php

namespace app\api\validate\v2_0_0;

use think\Validate;

class UserValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'free_rule_id' => 'require|number|gt:0'
    ];

    // 错误信息
    protected $message = [
        'free_rule_id.require' => '免单卡id不可为空',
        'free_rule_id.number' => '免单卡id格式错误',
        'free_rule_id.gt' => '免单卡id必须大于:rule',
    ];

    // 验证场景
    protected $scene = [
        'freeCardDetail' => ['free_rule_id'],
    ];
}
