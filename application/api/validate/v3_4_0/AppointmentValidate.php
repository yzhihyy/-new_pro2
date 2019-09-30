<?php

namespace app\api\validate\v3_4_0;

use think\Validate;

class AppointmentValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'theme_activity_id' => ['require', 'integer'],
    ];

    // 错误信息
    protected $message = [
        'theme_activity_id.require' => '主题活动id不可为空',
        'theme_activity_id.integer' => '主题活动id格式错误',
    ];

    // 验证场景
    protected $scene = [
        // 预约接口验证规则
        'appointment' => ['theme_activity_id']
    ];
}