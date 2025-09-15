<?php
namespace Vantage;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Carbon\Carbon;
use PHPHtmlParser\Dom\HtmlNode;
use PHPHtmlParser\Dom\Collection;
use PHPHtmlParser\Dom;

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
        'Upgrade-Insecure-Requests' => 1,
        'Sec-Fetch-Dest'            => 'document',
        'Sec-Fetch-Mode'            => 'navigate',
        'Sec-Fetch-Site'            => 'none',
        'Sec-Fetch-User'            => '?1',
        'Priority'                  => 'u=1',
        'TE'                        => 'trailers'
    ];

    private $baseUrl = 'https://www.cochranelibrary.com';


    private function __construct()
    {
        $jar = new CookieJar();
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'cookies'  => $jar,
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

        $dom = new Dom();
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

    public function getTopicReviews(Topic $topic): array
    {
        $reviews     = [];
        $seen        = [];
        $currentUrl  = $topic->getUrl();
        $totalPages  = null;
        $pageVisited = 0;

        while ($currentUrl) {
            $pageVisited++;
            $response = $this->client->get($currentUrl);
            $html     = (string) $response->getBody();

            $dom = new Dom();
            $dom->loadStr($html);

            if ($totalPages === null) {
                $totalPages = $this->detectTotalPages($dom);
                if ($totalPages < 1) $totalPages = 1;
            }
            echo "  -> Processing page {$pageVisited} of {$totalPages}\n";


            // --- Extract items on the page ---
            $containers = $dom->find('.search-results-item, .search-result, article.result, [data-test=result-item], li');
            if (is_iterable($containers)) {
                foreach ($containers as $c) {
                    // anchor with DOI
                    $a = $this->firstNode($c, 'a[href*="/doi/"]');
                    if (!$a) { continue; }

                    $href = $a->getAttribute('href') ?? '';
                    if ($href === '') { continue; }
                    if (!preg_match('~^https?://~i', $href)) {
                        $href = $this->absolutize($href, $currentUrl);
                    }

                    // Title
                    $title = trim($a->text ?? '');
                    if ($title === '') {
                        $h = $this->firstNode($c, 'h2, h3');
                        $title = $h ? trim($h->text ?? '') : '';
                    }

                    // Authors
                    $authors = '';
                    $authorNode = $this->firstNode($c, '.search-result-authors div');

                    if ($authorNode) {
                        $authors = explode(', ', $this->cleanText($authorNode->text));
                    }

                    // Date
                    $date = '';
                    $dateNode = $this->firstNode($c, '.search-result-date div');
                    if ($dateNode) {

                        $date = Carbon::createFromFormat('d F Y', $this->cleanText($dateNode->text));
                    } else {
                        $date = Carbon::createFromFormat('d F Y', $this->cleanText($c->text));
                    }

                    if (!isset($seen[$href])) {
                        $seen[$href] = true;
                        $reviews[] = new Review(
                            $href,
                            $topic->getName(),
                            $title,
                            $authors,
                            $date);
                    }
                }
            }

            // --- Next page URL ---
            $nextUrl = null;
            $nextA = $this->firstNode($dom, 'a[rel=next]');
            if ($nextA) {
                $nHref = $nextA->getAttribute('href') ?? '';
                if ($nHref !== '') {
                    $nextUrl = preg_match('~^https?://~i', $nHref) ? $nHref : $this->absolutize($nHref, $currentUrl);
                }
            }
            if (!$nextUrl) {
                // Fallback: link with text "Next"
                $links = $dom->find('a');
                if (is_iterable($links)) {
                    foreach ($links as $a) {
                        $txt = strtolower(trim($a->text ?? ''));
                        if ($txt === 'next' || strpos($txt, 'next') !== false) {
                            $nHref = $a->getAttribute('href') ?? '';
                            if ($nHref !== '') {
                                $nextUrl = preg_match('~^https?://~i', $nHref) ? $nHref : $this->absolutize($nHref, $currentUrl);
                                break;
                            }
                        }
                    }
                }
            }

            $currentUrl = $nextUrl;
        }

        return $reviews;
    }

    /** Safe: return first matched node or null (no ->first()) */
    private function firstNode($scope, string $selector): ? HtmlNode
    {
        $nodes = $scope->find($selector);
        if (is_array($nodes)) {
            return isset($nodes[0]) ? $nodes[0] : null;
        }

        if ($nodes instanceof Collection) {
            return $nodes->count() > 0 ? $nodes[0] : null;
        }
        if ($nodes instanceof HtmlNode) {
            return $nodes; // single match returned as node
        }
        return null;
    }

    /** Look for max numeric page in common pagination wrappers */
    private function detectTotalPages(\PHPHtmlParser\Dom $dom): int
    {
        $maxPage = 1;
        $navs = $dom->find('nav, .pagination, .search-pagination, .p-pagination, .c-pagination');
        if (is_iterable($navs)) {
            foreach ($navs as $nav) {
                $kids = $nav->find('a, span, li');
                if (!is_iterable($kids)) continue;
                foreach ($kids as $k) {
                    $txt = trim(preg_replace('~\s+~u', ' ', $k->text ?? ''));
                    if ($txt !== '' && ctype_digit($txt)) {
                        $maxPage = max($maxPage, (int)$txt);
                    }
                }
            }
        }
        return $maxPage;
    }

    private function cleanText(string $s): string
    {
        return trim(preg_replace('~\s+~u', ' ', html_entity_decode($s)));
    }

    private function absolutize(string $url, string $base): string
    {
        if (preg_match('~^https?://~i', $url)) return $url;
        $bp = parse_url($base);
        if (!$bp || empty($bp['scheme']) || empty($bp['host'])) return $url;
        $origin = $bp['scheme'].'://'.$bp['host'].(isset($bp['port'])?':'.$bp['port']:'');
        if (str_starts_with($url, '/')) return $origin.$url;
        $path = preg_replace('~/[^/]*$~', '/', $bp['path'] ?? '/');
        return $origin.$path.$url;
    }
}
