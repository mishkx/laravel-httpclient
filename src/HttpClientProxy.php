<?php

namespace Mishkx\HttpClient;

use Illuminate\Support\Collection;
use Mishkx\HttpClient\Contracts\ProxyInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\ConnectException;
use Exception;

class HttpClientProxy
{
    public const TIMEOUT = 2;

    protected $client;

    public function __construct($client = null)
    {
        $this->client = $client ?: resolve(ProxyInterface::class);
    }

    public function getAvailable($url)
    {
        return (new Collection($this->client->getList()))
            ->shuffle()
            ->first(function ($proxy) use ($url) {
                return self::isAvailable($url, $proxy);
            });
    }

    public static function isAvailable($url, $proxy)
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
