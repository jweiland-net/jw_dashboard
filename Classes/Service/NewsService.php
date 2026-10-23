<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Service;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * Fetches and parses the TYPO3 RSS news feed, caching results per configured TTL.
 */
class NewsService
{
    private readonly FrontendInterface $cache;

    public function __construct(
        private readonly RequestFactory $requestFactory,
        CacheManager $cacheManager,
    ) {
        $this->cache = $cacheManager->getCache('jw_dashboard_news');
    }

    /**
     * @return list<array{title: string, link: string, description: string, pubDate: int}>
     */
    public function getLatestNews(string $feedUrl, int $limit, int $cacheTtl, int $timeout): array
    {
        $cacheKey = $this->getCacheKey($feedUrl, $limit);
        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            /** @var list<array{title: string, link: string, description: string, pubDate: int}> $cached */
            return $cached;
        }

        $items = $this->parseFeed($feedUrl, $limit, $timeout);

        // Do not keep a failed request for the whole TTL, try again on next request
        if ($items !== []) {
            $this->cache->set($cacheKey, $items, [], $cacheTtl);
        }

        return $items;
    }

    public function hasCachedData(string $feedUrl, int $limit): bool
    {
        return $this->cache->has($this->getCacheKey($feedUrl, $limit));
    }

    public function flushCache(): void
    {
        $this->cache->flush();
    }

    private function getCacheKey(string $feedUrl, int $limit): string
    {
        return sha1($feedUrl . $limit);
    }

    /**
     * @return list<array{title: string, link: string, description: string, pubDate: int}>
     */
    private function parseFeed(string $feedUrl, int $limit, int $timeout): array
    {
        $previousUseErrors = libxml_use_internal_errors(true);
        try {
            $response = $this->requestFactory->request($feedUrl, 'GET', ['timeout' => $timeout]);
            $xml = simplexml_load_string((string)$response->getBody(), options: LIBXML_NOCDATA | LIBXML_NONET);
        } catch (\Throwable) {
            return [];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseErrors);
        }

        if ($xml === false || !isset($xml->channel->item)) {
            return [];
        }

        $items = [];
        foreach ($xml->channel->item as $item) {
            if (count($items) >= $limit) {
                break;
            }

            $items[] = [
                'title' => trim((string)$item->title),
                'link' => trim((string)$item->link),
                'description' => trim(strip_tags((string)$item->description)),
                'pubDate' => (int)strtotime((string)$item->pubDate),
            ];
        }

        return $items;
    }
}
