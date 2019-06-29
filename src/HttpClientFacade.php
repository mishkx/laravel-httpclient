<?php

namespace Mishkx\HttpClient;

use Illuminate\Support\Facades\Facade;

class HttpClientFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return HttpClient::class;
    }
}
