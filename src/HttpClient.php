<?php

namespace Mishkx\HttpClient;

use Exception;
use GuzzleHttp;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Mishkx\HttpClient\Interfaces\HttpClientInterface;

class HttpClient implements HttpClientInterface
{
    protected $clientConfig = [];
    protected $cookiesJar = [];
    protected $referer = null;
    protected $requestOptions = [];

    public function __construct($config = [])
    {
        $this->setClientConfig($config);
    }

    public function addHeaders($headers)
    {
        $this->requestOptions[HttpClientOptions::DEFAULT_HEADERS] = array_merge(
            $this->requestOptions[HttpClientOptions::DEFAULT_HEADERS], $headers
        );
        return $this;
    }

    public function getClientConfigValue($key)
    {
        return Arr::get($this->clientConfig, $key, Config::get("http-client.{$key}"));
    }

    public function getCookieByName($name)
    {
        $cookie = $this->getCookiesJar()->getCookieByName($name);

        if ($cookie) {
            return $cookie->getValue();
        }

        return null;
    }

    public function getProxyAddress($url)
    {
        if (!$this->getClientConfigValue(HttpClientOptions::USE_PROXY)) {
            return null;
        }
        if (!$this->getClientConfigValue(HttpClientOptions::PROXY_ADDRESS)) {
            $this->setProxyAddress((new HttpClientProxy())->getAvailable($url));
        }
        return $this->getClientConfigValue(HttpClientOptions::PROXY_ADDRESS);
    }

    public function getReferer()
    {
        return $this->referer;
    }

    public function getRequestOptions()
    {
        return $this->requestOptions;
    }

    public function setClientConfig($config = [])
    {
        $availableKeys = [
            HttpClientOptions::COOKIES_PATH,
            HttpClientOptions::DEFAULT_HEADERS,
            HttpClientOptions::DEFAULT_USER_AGENT,
            HttpClientOptions::IS_REPEAT_ON_ERROR,
            HttpClientOptions::MAX_ATTEMPTS,
            HttpClientOptions::PROXY_ADDRESS,
            HttpClientOptions::SLEEP,
            HttpClientOptions::SLEEP_RANGE,
            HttpClientOptions::USE_PROXY,
        ];

        $this->clientConfig = (new Collection($this->clientConfig))
            ->merge($config)
            ->filter(function ($value, $key) use ($availableKeys) {
                return (new Collection($availableKeys))->contains($key);
            })
            ->toArray();

        return $this;
    }

    public function setClientConfigValue($key, $value)
    {
        return $this->setClientConfig([
            $key => $value,
        ]);
    }

    public function setCookie($data = [])
    {
        $cookieJar = $this->getCookiesJar();
        $cookieJar->setCookie(new GuzzleHttp\Cookie\SetCookie($data));
        return $this;
    }

    public function setHeader($name, $value)
    {
        $this->requestOptions[RequestOptions::HEADERS][$name] = $value;
        return $this;
    }

    public function setHeaders($headers)
    {
        $this->requestOptions[RequestOptions::HEADERS] = $headers;
        return $this;
    }

    public function setHeaderXRequestedWith()
    {
        return $this->setHeader('X-Requested-With', 'XMLHttpRequest');
    }

    public function setIsRepeatOnError($state = true)
    {
        return $this->setClientConfigValue(HttpClientOptions::IS_REPEAT_ON_ERROR, $state);
    }

    public function setProxyAddress($value)
    {
        return $this->setClientConfigValue(HttpClientOptions::PROXY_ADDRESS, $value ?: null);
    }

    public function setReferer($referer)
    {
        $this->referer = $referer;
        return $this;
    }

    public function setRequestOptions($options = [])
    {
        $this->requestOptions = $options;
        return $this;
    }

    public function removeCookiesFile()
    {
        $cookiesPath = $this->getClientConfigValue(HttpClientOptions::COOKIES_PATH);
        if (File::exists($cookiesPath)) {
            File::delete($cookiesPath);
        }
    }

    public function resetRequestOptions()
    {
        $this->setRequestOptions([]);
        return $this;
    }

    public function download($url, $path, $method = HttpClientOptions::GET)
    {
        return $this->request($method, $url, [
            RequestOptions::SINK => $path
        ]);
    }

    public function get($url, $params = [])
    {
        $options = $params ? [
            RequestOptions::QUERY => $params,
        ] : [];

        return $this->request(HttpClientOptions::GET, $url, $options);
    }

    public function post($url, $params = [], $key = RequestOptions::FORM_PARAMS)
    {
        return $this->request(HttpClientOptions::POST, $url, [
            $key => $params
        ]);
    }

    protected function requestRaw($method, $url, $options = [])
    {
        $client = new GuzzleHttp\Client();

        $options = new Collection($options);

        $headers = (new Collection($this->getClientConfigValue(HttpClientOptions::DEFAULT_HEADERS)))
            ->merge($options->get(RequestOptions::HEADERS));

        $headers->put('Referer', $this->getReferer());

        if (!$headers->has('User-Agent')) {
            $headers->put('User-Agent', $this->getClientConfigValue(HttpClientOptions::DEFAULT_USER_AGENT));
        }

        if (!$options->has(RequestOptions::PROXY) && $proxyAddress = $this->getProxyAddress($url)) {
            $options->put(RequestOptions::PROXY, $proxyAddress);
        }

        $options = $options
            ->merge([
                RequestOptions::HEADERS => $headers,
                RequestOptions::COOKIES => $this->getCookiesJar(),
                RequestOptions::ON_STATS => function (GuzzleHttp\TransferStats $stats) {
                    $uri = $stats->getEffectiveUri();
                    $query = $uri->getQuery() ? '?' . $uri->getQuery() : '';
                    $referer = "{$uri->getScheme()}://{$uri->getHost()}{$uri->getPath()}{$query}";
                    $this->setReferer($referer);
                },
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::VERIFY => false,
            ])
            ->toArray();

        $result = $client->request($method, $url, $options);

        return $result->getBody()->getContents();
    }

    protected function request($method, $url, $options)
    {
        $this->sleep();

        $options = (new Collection($this->getRequestOptions()))->merge($options);

        if ($this->getClientConfigValue(HttpClientOptions::IS_REPEAT_ON_ERROR)) {
            $message = "{$method}: {$url} ";
            $result = $this->repeatRequest([$this, 'requestRaw'], [$method, $url, $options], $message);
            $this->setIsRepeatOnError(false);
        } else {
            $result = $this->requestRaw($method, $url, $options);
        }

        $this->resetRequestOptions();

        return $result;
    }

    private function getCookiesJar()
    {
        if ($this->cookiesJar) {
            return $this->cookiesJar;
        }

        $cookiesPath = $this->getClientConfigValue(HttpClientOptions::COOKIES_PATH);

        if ($cookiesPath) {
            $directory = pathinfo($cookiesPath, PATHINFO_DIRNAME);
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0777, true);
            }
            try {
                $this->cookiesJar = new GuzzleHttp\Cookie\FileCookieJar($cookiesPath, true);
            } catch (Exception $exception) {
                $this->removeCookiesFile();
                return $this->getCookiesJar();
            }
        }

        if (!$this->cookiesJar) {
            $this->cookiesJar = new GuzzleHttp\Cookie\CookieJar();
        }

        return $this->cookiesJar;
    }

    private function repeatRequest($callback, $params = [], $message = '', $attempt = 0)
    {
        $maxAttempts = $this->getClientConfigValue(HttpClientOptions::MAX_ATTEMPTS);

        $attempt++;

        try {
            return call_user_func_array($callback, $params);
        } catch (Exception $exception) {
            if ($attempt > $maxAttempts) {
                return call_user_func_array($callback, $params);
            } else {
                $this->sleep();
                return $this->repeatRequest($callback, $params, $message, $attempt);
            }
        }
    }

    private function sleep()
    {
        $sleepValue = $this->getClientConfigValue(HttpClientOptions::SLEEP);
        $sleepRangeValue = $this->getClientConfigValue(HttpClientOptions::SLEEP_RANGE);

        if ($sleepValue) {
            usleep(random_int($sleepValue * 1000000, ($sleepValue + $sleepRangeValue) * 1000000));
        }
    }

    public static function jsonDecode($json)
    {
        return GuzzleHttp\json_decode($json, true);
    }
}
