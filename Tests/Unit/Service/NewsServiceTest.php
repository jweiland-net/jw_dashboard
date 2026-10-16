<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Tests\Unit\Service;

use JWeiland\JwDashboard\Service\NewsService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\RequestFactory;

final class NewsServiceTest extends TestCase
{
    private MockObject&RequestFactory $requestFactory;
    private MockObject&FrontendInterface $cache;
    private NewsService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = $this->createMock(RequestFactory::class);
        $this->cache = $this->createMock(FrontendInterface::class);

        $cacheManager = $this->createMock(CacheManager::class);
        $cacheManager->method('getCache')->with('jw_dashboard_news')->willReturn($this->cache);

        $this->subject = new NewsService($this->requestFactory, $cacheManager);
    }

    #[Test]
    public function getLatestNewsReturnsCachedItemsIfPresent(): void
    {
        $cachedItems = [
            ['title' => 'Cached News', 'link' => 'https://news.typo3.com/x', 'description' => '', 'pubDate' => 0],
        ];

        $this->cache->method('get')->willReturn($cachedItems);
        $this->requestFactory->expects(self::never())->method('request');

        $result = $this->subject->getLatestNews('https://news.typo3.com/rss', 5, 3600, 10);

        self::assertSame($cachedItems, $result);
    }

    #[Test]
    public function getLatestNewsReturnsEmptyArrayOnHttpErrorAndDoesNotCacheIt(): void
    {
        $this->cache->method('get')->willReturn(false);
        $this->cache->expects(self::never())->method('set');

        $this->requestFactory->method('request')->willThrowException(new \RuntimeException('Timeout'));

        $result = $this->subject->getLatestNews('https://news.typo3.com/rss', 5, 3600, 10);

        self::assertSame([], $result);
    }

    #[Test]
    public function getLatestNewsReturnsEmptyArrayOnInvalidXml(): void
    {
        $this->cache->method('get')->willReturn(false);
        $this->cache->method('set');

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('not-xml-at-all!!!');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->requestFactory->method('request')->willReturn($response);

        $result = $this->subject->getLatestNews('https://news.typo3.com/rss', 5, 3600, 10);

        self::assertSame([], $result);
    }

    #[Test]
    public function getLatestNewsRespectsItemLimit(): void
    {
        $this->cache->method('get')->willReturn(false);
        $this->cache->method('set');

        $rss = '<?xml version="1.0"?><rss version="2.0"><channel>' .
            '<item><title>A</title><link>http://a</link><description>a</description><pubDate>Mon, 01 Jan 2024 00:00:00 GMT</pubDate></item>' .
            '<item><title>B</title><link>http://b</link><description>b</description><pubDate>Tue, 02 Jan 2024 00:00:00 GMT</pubDate></item>' .
            '<item><title>C</title><link>http://c</link><description>c</description><pubDate>Wed, 03 Jan 2024 00:00:00 GMT</pubDate></item>' .
            '</channel></rss>';

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($rss);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->requestFactory->method('request')->willReturn($response);

        $result = $this->subject->getLatestNews('https://news.typo3.com/rss', 2, 3600, 10);

        self::assertCount(2, $result);
        self::assertSame('A', $result[0]['title']);
        self::assertSame('B', $result[1]['title']);
    }

    #[Test]
    public function getLatestNewsConvertsPubDateToTimestampAndStripsHtml(): void
    {
        $this->cache->method('get')->willReturn(false);
        $this->cache->expects(self::once())->method('set');

        $rss = '<?xml version="1.0"?><rss version="2.0"><channel>' .
            '<item><title> A </title><link>http://a</link><description><![CDATA[<p>Teaser</p>]]></description>' .
            '<pubDate>Thu, 15 Oct 2026 07:00:00 +0200</pubDate></item>' .
            '</channel></rss>';

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($rss);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->requestFactory->method('request')->willReturn($response);

        $result = $this->subject->getLatestNews('https://news.typo3.com/rss', 5, 3600, 10);

        self::assertSame('A', $result[0]['title']);
        self::assertSame('Teaser', $result[0]['description']);
        self::assertSame(1792040400, $result[0]['pubDate']);
    }
}
