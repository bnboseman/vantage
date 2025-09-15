<?php 
namespace Vantage;

use GuzzleHttp\Client;

class Topic {
    private $name;
    private $url;
    private static $searchUrl = "http://www.cochranelibrary.com/home/topic-and-review-group-list.html?page=topic";

    public function __construct(string $name, string $url) {
        $this->name = $name;
        $this->url = $url;

    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url): void
    {
        $this->url = $url;
    }
    public static function getSearchUrl() {
        return self::$searchUrl;
    }

    public function fetchResults() {
        var_dump ($this->getUrl());
    }
}