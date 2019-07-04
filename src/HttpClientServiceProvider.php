<?php

namespace Mishkx\HttpClient;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Mishkx\HttpClient\Interfaces\HttpClientInterface;
use Mishkx\HttpClient\Interfaces\ProxyInterface;

class HttpClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/http-client.php', 'http-client'
        );

        $this->app->bind(ProxyInterface::class, Config::get('http-client.' . HttpClientOptions::PROXY_CLASS));

        $this->app->bind(HttpClientInterface::class, function ($app) {
            return new HttpClient(Config::get('http-client'));
        });
    }
}
