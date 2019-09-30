<?php
return [
    'app_key' => '1156180327146358#kkjy-tcyx', # 应用标识
    'client_id' => 'YXA6zeFowG7SEema2o2KXCn4cg', # Client ID
    'client_secret' => 'YXA6Jq7nenPkCDHBSBGGn4iv2KbxrPE', # Client Secret
    'cache_path' => '/easemob', # 缓存路径
    'log_path' => '/easemob', # log 路径
    'user_prefix' => Env::get('easemob.user_prefix', 'prod_'), // 环信用户前缀
];