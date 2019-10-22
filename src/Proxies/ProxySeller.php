<?php

namespace Mishkx\HttpClient\Proxies;

use Illuminate\Support\Facades\Config;
use Mishkx\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;
use Exception;
use Illuminate\Support\Arr;

class ProxySeller extends ProxyBaseClass
{
    protected const API_URL = 'https://proxy-seller.ru/control_panel/download';

    protected const CONTROL_PANEL_URL = 'https://proxy-seller.ru/control-panel';

    private const AUTH_URL = 'https://proxy-seller.ru/authorization';

    private const CONTROL_PANEL_ITEM_SELECTOR = '.personal_account_ipv4_table [data-order_type="ipv4"][data-id]';

    protected $cacheKey = 'ProxySellerRuProxiesList';

    private function client()
    {
        if (!$this->client) {
            $this->client = new HttpClient();
        }
        return $this->client;
    }

    private function auth()
    {
        $content = $this->client()->get(self::AUTH_URL);
        $crawler = new Crawler($content);
        $csrfToken = $crawler->filter('[name="csrf-token"]')->first()->attr('content');
        $this->client()->post(self::AUTH_URL, [
            '_csrf-frontend' => $csrfToken,
            'LoginForm[username]' => Config::get('http-client.proxy_seller.login'),
            'LoginForm[password]' => Config::get('http-client.proxy_seller.password'),
        ]);
    }

    private function getProxiesIds()
    {
        $content = $this->client()->get(self::CONTROL_PANEL_URL);
        $crawler = new Crawler($content);
        return $crawler->filter(self::CONTROL_PANEL_ITEM_SELECTOR)
            ->each(function (Crawler $crawler) {
                return $crawler->attr('data-id');
            });
    }

    protected function getProxiesList()
    {
        try {
            $this->auth();
            $ids = $this->getProxiesIds();
        } catch (Exception $exception) {
            $ids = [];
        }

        if (!$ids) {
            return [];
        }

        $content = $this->client()->post(self::API_URL, [
            'type' => 'TXT',
            'list' => implode(',', $ids),
        ]);

        preg_match_all('/^[\d\.]+\s+([^\s]+).+$/m', $content, $matches);

        if (!Arr::has($matches, 1)) {
            return [];
        }

        return collect($matches[1])
            ->map(function ($item) {
                $item = preg_replace('/^(https\:\/\/)?/', 'https://', $item);
                $item = parse_url($item);
                return [
                    'ip' => $item['host'],
                    'port' => $item['port'],
                    'type' => 'https',
                    'user' => $item['user'],
                    'pass' => $item['pass'],
                    'active' => true,
                ];
            })
            ->toArray();
    }
}
