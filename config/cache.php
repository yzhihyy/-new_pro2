<?php

return [
    'type' => 'complex',
    'default' => [
        'type' => 'File',
        'path' => '',
        'prefix' => '',
        'expire' => 0,
    ],
    'redis' => [
        'type' => 'Redis',
        'host' => Env::get('redis.host', '127.0.0.1'),
        'port' => Env::get('redis.port', 6379),
        'password' => Env::get('redis.auth', ''),
        'prefix' => '',
        'expire' => 0,
    ],
];