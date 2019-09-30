<?php

namespace app\api\validate\v1_1_0;

use think\Validate;

class User extends Validate
{
    // 验证规则
    protected $rule = [
        'free_rule_id' => ['require', 'integer'],
    ];

    // 错误信息
    protected $message = [
        'free_rule_id.require' => '免单卡id不可为空',
        'free_rule_id.integer' => '免单卡id格式错误',
    ];

    // 验证场景
    protected $scene = [
        // 免单卡详情接口验证规则
        'freeCardDetail' => ['free_rule_id'],
    ];
}