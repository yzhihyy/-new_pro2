<?php
return [
    // 主机
    'host' => Env::get('redis.host'),
    // 端口
    'port' => Env::get('redis.port'),
    // 密码
    'auth' => Env::get('redis.auth'),
];
