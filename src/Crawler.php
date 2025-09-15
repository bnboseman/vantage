<?php
namespace Vantage;

use GuzzleHttp\Client;


class Crawler
{
    private static $instance = null;

    private $client = null;

    private $topics = null;

    private static $headers = [
            'Host'                      => 'www.cochranelibrary.com',
            'User-Agent'                => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv =>126.0) Gecko/20100101 Firefox/126.0',
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language'           => 'en-US,en;q=0.5',
            'Accept-Encoding'           => 'gzip, deflate',
            'Connection'                => 'keep-alive',
    ];

    private $baseUrl = 'https://www.cochranelibrary.com';


    private function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers'  => self::$headers,
            'timeout'  => 10.0,
        ]);
    }

    public static function getInstance(): Crawler {
        if (is_null(self::$instance)) {
            self::$instance = new Crawler();
        }
        return self::$instance;
    }

    public function getClient(): Client {
        return $this->client;
    }

    public function getTopics() {
        if ($this->topics) {
            return $this->topics;
        }
        $response = $this->client->get(Topic::getSearchUrl());
        $topicHtml = (string) $response->getBody();

        $dom = new \PHPHtmlParser\Dom();
        $dom->loadStr($topicHtml);

        foreach ($dom->find('.browse-by-list-item') as $node) {
            $link           = $node->find('a');
            $href           = $link->getAttribute('href');
            $button         = $node->find('button');
            $topics[] = new Topic($button->innerHtml, $href);
        }

        $this->topics = $topics;
        return $this->topics;
    }

}