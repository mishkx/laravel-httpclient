<?php

return [
    'default_user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36',

    'is_repeat_on_error' => false,

    'max_attempts' => 5,

    'proxy_cache_time' => 60,

    'proxy_class' => \Mishkx\HttpClient\Proxies\Proxy6Net::class,

    'proxy_credentials' => [
        'proxy6' => env('PROXY6_API_KEY'),
        'proxy_market' => env('PROXY_MARKET_API_KEY'),
        'super_proxy' => [
            'login' => env('SUPER_PROXY_LOGIN'),
            'password' => env('SUPER_PROXY_PASSWORD'),
            'type' => env('SUPER_PROXY_TYPE', 'http'),
            'port' => env('SUPER_PROXY_PORT', '7165'),
        ],
    ],

    'sleep' => 0.1,

    'sleep_range' => 0.2,

    'use_proxy' => false,
];
