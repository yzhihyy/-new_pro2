<?php

return [
    // 苹果支付配置信息
    'apple_pay_config' => [
        // 日志路径
        'log_path' => '/applepay/app',
        // 3.7.0版本app通知URL
        'notify_url_v3_7_0' => config('app.app_host').'/user/v3_7_0/applePayNotify',
    ],
];
