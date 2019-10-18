<?php

namespace Mishkx\HttpClient\Proxies;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Mishkx\HttpClient\Facades\HttpClient;

class ProxyMarket extends ProxyBaseClass
{
    protected const API_URL = 'https://proxy.market/dev-api';

    protected function buildMethodUrl($name)
    {
        $apiKey = Config::get('http-client.proxy_credentials.proxy_market');
        return self::API_URL . '/' . $name . '/' . $apiKey;
    }

    protected function callMethod($name)
    {
        $result = HttpClient::post($this->buildMethodUrl($name));
        return json_decode($result, true);
    }

    protected function getProxiesList()
    {
        return collect(Arr::get($this->callMethod('list'), 'list.data', []))
            ->map(function ($item) {
                return [
                    'ip' => $item['ip'],
                    'port' => $item['http_port'],
                    'type' => 'http',
                    'user' => $item['login'],
                    'pass' => $item['password'],
                    'active' => $item['active'],
                ];
            })
            ->toArray();
    }
}
