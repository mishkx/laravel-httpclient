<?php

namespace Mishkx\HttpClient\Facades;

use Illuminate\Support\Facades\Facade;
use Mishkx\HttpClient\Interfaces\HttpClientInterface;

class HttpClient extends Facade
{
    protected static function getFacadeAccessor()
    {
        return HttpClientInterface::class;
    }
}
