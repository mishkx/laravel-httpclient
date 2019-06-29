<?php

namespace Mishkx\HttpClient\Proxy;

use HttpClient;

class SuperProxyNet extends ProxyBaseClass
{
    protected const API_URL = 'http://super-proxy.net/ruip.html';

    protected $cacheKey = 'SuperProxyNetProxiesList';

    protected function getProxiesList()
    {
        $content = HttpClient::get(self::API_URL);

        $digitRegExp = '[\d]{1,3}';

        preg_match_all("/({$digitRegExp}\.){3}({$digitRegExp})/", $content, $matches);

        return collect($matches[0])
            ->map(function ($ip) {
                return [
                    'ip' => $ip,
                    'port' => config('api.super_proxy.port'),
                    'type' => config('api.super_proxy.type'),
                    'user' => config('api.super_proxy.login'),
                    'pass' => config('api.super_proxy.password'),
                    'active' => true,
                ];
            });
    }
}
