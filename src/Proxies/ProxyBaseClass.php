<?php

namespace Mishkx\HttpClient\Proxies;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Mishkx\HttpClient\Interfaces\ProxyInterface;

abstract class ProxyBaseClass implements ProxyInterface
{
    protected $client;

    protected const PROTOCOL_REPLACES = [
        'socks' => 'socks5',
    ];

    abstract protected function getProxiesList();

    public function getList()
    {
        $list = Cache::remember(
            get_called_class() . '::list',
            Carbon::now()->addMinutes(Config::get('http-client.proxy_cache_time')),
            function () {
                return collect($this->getProxiesList())->shuffle()->toArray();
            }
        );

        $formatted = collect($list)
            ->filter(function ($item) {
                return (bool)$item['active'];
            })
            ->map(function ($item) {
                $type = $item['type'];
                $protocol = self::PROTOCOL_REPLACES[$type] ?? $type;

                $ip = $item['ip'];
                $port = $item['port'];

                $auth = '';
                $login = Arr::get($item, 'user');

                if ($login) {
                    $password = Arr::get($item, 'pass');
                    $auth = "{$login}:{$password}@";
                }

                return "{$protocol}://{$auth}{$ip}:{$port}";
            })
            ->values()
            ->toArray();

        return $formatted;
    }

    public function getRandom()
    {
        return collect($this->getList())->random();
    }
}
