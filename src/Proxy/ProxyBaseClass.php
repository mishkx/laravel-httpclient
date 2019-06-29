<?php

namespace Mishkx\HttpClient\Proxy;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Mishkx\HttpClient\Contracts\ProxyInterface;

abstract class ProxyBaseClass implements ProxyInterface
{
    protected const CACHE_MINUTES = 60;

    protected const PROTOCOL_REPLACES = [
        'socks' => 'socks5',
    ];

    protected $client;

    abstract protected function getProxiesList();

    public function getList()
    {
        $list = Cache::remember(get_called_class() . '::list', now()->addMinutes(self::CACHE_MINUTES), function () {
            return collect($this->getProxiesList())->shuffle()->toArray();
        });

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
