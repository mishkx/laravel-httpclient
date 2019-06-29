<?php

namespace Mishkx\HttpClient\Proxy;

use HttpClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

class Proxy6Net extends ProxyBaseClass
{
    protected const API_URL = 'https://proxy6.net/api/';

    protected const PROTOCOL_REPLACES = [
        'socks' => 'socks5',
    ];

    protected $cacheKey = 'Proxy6NetProxiesList';

    protected function buildMethodUrl($name)
    {
        $apiKey = Config::get('http-client.proxy-credentials.proxy6');
        return self::API_URL . $apiKey . '/'. $name;
    }

    protected function callMethod($name)
    {
        $result = HttpClient::get($this->buildMethodUrl($name));
        return json_decode($result, true);
    }

    protected function getProxiesList()
    {
        return Arr::get($this->callMethod('getproxy'), 'list');
    }
}
