<?php

return [
    'default-user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.100 Safari/537.36',

    'is-repeat-on-error' => false,

    'max-attempts' => 5,

    'proxy-class' => \Mishkx\HttpClient\Proxy\Proxy6Net::class,

    'sleep' => 0.1,

    'sleep-range' => 0.2,

    'use-proxy' => false,

    'proxy-credentials' => [
        'proxy6' => env('PROXY6_API_KEY'),
    ],
];
