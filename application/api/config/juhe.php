<?php

return [
    // 聚合数据接口
    'juhe_platform_api' => [
        // log生成路径
        'log_path' => '/juhe/',
        // 银行卡类别查询AppKey
        'bankcardsilk_key' => 'f2cf992396e6a2254a66da957ad41bb5',
        // 银行卡类型的logo图片路径
        'bankcard_logo_url' => 'http://images.juheapi.com/banklogo/',
        // 银行卡类别查询
        'bankcardsilk_url' => 'https://bankcardsilk.api.juhe.cn/bankcardsilk/query.php',
        // 银行卡四元素校验AppKey
        'verifybankcard4_key' => '157af5fda35d709c7ca618c48eae0cee',
        // 银行卡四元素校验
        'verifybankcard4_url' => 'https://v.juhe.cn/verifybankcard4/query',
    ],

    // 聚合数据接口单天校验限制次数
    'bankcard_verify_count' => 50,
    // 商家银行卡校验次数缓存名
    'shop_bankcard_verify_cache_name' => 'shop_bankcard_verify_%d_%s',
    // 商家银行卡信息缓存名
    'shop_bankcard_info_cache_name' => 'shop_bankcard_num_%d_%s',

];
