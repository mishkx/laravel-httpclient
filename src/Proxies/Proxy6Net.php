<?php

namespace Mishkx\HttpClient\Proxies;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Mishkx\HttpClient\Facades\HttpClient;

class Proxy6Net extends ProxyBaseClass
{
    protected const API_URL = 'https://proxy6.net/api/';

    protected function buildMethodUrl($name)
    {
        $apiKey = Config::get('http-client.proxy_credentials.proxy6');
        return self::API_URL . $apiKey . '/'. $name;
    }

    protected function callMethod($name)
    {
        $result = HttpClient::get($this->buildMethodUrl($name));
        return json_decode($result, true);
    }

    protected function getProxiesList()
    {
        return Arr::get($this->callMethod('getproxy'), 'list', []);
    }
}
