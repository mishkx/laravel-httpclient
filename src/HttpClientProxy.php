<?php

namespace Mishkx\HttpClient;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use Mishkx\HttpClient\Interfaces\ProxyInterface;

class HttpClientProxy
{
    public const TIMEOUT = 2;

    protected $proxyClient;

    public function __construct($client = null)
    {
        $this->proxyClient = $client ?: resolve(ProxyInterface::class);
    }

    public function getAvailable($url)
    {
        return (new Collection($this->proxyClient->getList()))
            ->shuffle()
            ->first(function ($proxy) use ($url) {
                return self::isAvailable($url, $proxy);
            });
    }

    private static function isAvailable($url, $proxy)
    {
        try {
            $client = new Client();

            $client->head($url, [
                RequestOptions::PROXY => $proxy,
                RequestOptions::TIMEOUT => self::TIMEOUT,
                RequestOptions::VERIFY => false,
            ]);

            return true;
        } catch (ClientException $exception) {
            return true;
        } catch (ConnectException $exception) {
            return false;
        } catch (Exception $exception) {
            return false;
        }
    }
}
