<?php
namespace Vantage;

use Carbon\Carbon;

class Review
{
    private $url;
    private $title;
    private $topic;
    private $authors;
    private Carbon $date;

    public function __construct($url, $title, $topic, $authors, Carbon $date)
    {
        $this->url    = $url;
        $this->title  = $title;
        $this->topic  = $topic;
        $this->authors = $authors;
        $this->date   = $date;
    }

    public function __toString(): string
    {
        return $this->url . "|" .
            $this->topic . "|" .
            $this->title . "|" .
            implode(', ', $this->authors) . "|" .
            $this->date->format('Y-m-d');
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

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @param mixed $topic
     */
    public function setTopic($topic): void
    {
        $this->topic = $topic;
    }

    public function getAuthors(): array
    {
        return $this->authors;
    }

    public function setAuthors(mixed $authors): void
    {
        $this->authors = $authors;
    }

    public function getDate(): Carbon
    {
        return $this->date;
    }

    public function setDate(Carbon $date): void
    {
        $this->date = $date;
    }



}