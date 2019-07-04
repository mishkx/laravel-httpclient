<?php

namespace Mishkx\HttpClient\Interfaces;

use GuzzleHttp\RequestOptions;
use Mishkx\HttpClient\HttpClientOptions;

interface HttpClientInterface
{
    public function addHeaders($headers);

    public function getClientConfigValue($key);

    public function getCookieByName($name);

    public function getProxyAddress($url);

    public function getReferer();

    public function getRequestOptions();

    public function setClientConfig($config = []);

    public function setClientConfigValue($key, $value);

    public function setCookie($data = []);

    public function setHeader($name, $value);

    public function setHeaders($headers);

    public function setHeaderXRequestedWith();

    public function setIsRepeatOnError($state = true);

    public function setProxyAddress($value);

    public function setReferer($referer);

    public function setRequestOptions($options = []);

    public function removeCookiesFile();

    public function resetRequestOptions();

    public function download($url, $path, $method = HttpClientOptions::GET);

    public function get($url, $params = []);

    public function post($url, $params = [], $key = RequestOptions::FORM_PARAMS);

    public static function jsonDecode($json);
}
