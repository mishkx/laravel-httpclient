<?php

namespace Mishkx\HttpClient;

use Illuminate\Support\ServiceProvider;
use Mishkx\HttpClient\Contracts\ProxyInterface;

class HttpClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/http-client.php', 'http-client'
        );

        $this->app->bind(ProxyInterface::class, $this->app['config']->get('http-client.' . HttpClientOptions::PROXY_CLASS));

        $this->app->bind(HttpClient::class, function ($app) {
            return new HttpClient($app['config']->get('http-client'));
        });
    }
}
