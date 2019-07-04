<?php

namespace Mishkx\HttpClient\Proxies;

use Illuminate\Support\Facades\Config;
use Mishkx\HttpClient\Facades\HttpClient;

class SuperProxyNet extends ProxyBaseClass
{
    protected const API_URL = 'http://super-proxy.net/ruip.html';

    protected $cacheKey = 'SuperProxyNetProxiesList';

    protected function getProxiesList()
    {
        $content = HttpClient::get(self::API_URL);

        $digitRegExp = '[\d]{1,3}';

        preg_match_all("/({$digitRegExp}\.){3}({$digitRegExp})/", $content, $matches);

        $credentials = Config::get('http-client.proxy_credentials.super_proxy');

        return collect($matches[0])
            ->map(function ($ip) use ($credentials) {
                return [
                    'ip' => $ip,
                    'port' => $credentials['port'],
                    'type' => $credentials['type'],
                    'user' => $credentials['login'],
                    'pass' => $credentials['password'],
                    'active' => true,
                ];
            });
    }
}
