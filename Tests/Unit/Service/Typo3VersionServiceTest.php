<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Tests\Unit\Service;

use JWeiland\JwDashboard\Service\Typo3VersionService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Information\Typo3Version;

final class Typo3VersionServiceTest extends TestCase
{
    private const API_URL = 'https://get.typo3.org/api/v1/';

    private MockObject&RequestFactory $requestFactory;
    private MockObject&FrontendInterface $cache;
    private MockObject&Typo3Version $typo3Version;
    private Typo3VersionService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = $this->createMock(RequestFactory::class);
        $this->cache = $this->createMock(FrontendInterface::class);
        $this->typo3Version = $this->createMock(Typo3Version::class);
        $this->typo3Version->method('getVersion')->willReturn('14.3.4');
        $this->typo3Version->method('getMajorVersion')->willReturn(14);

        $cacheManager = $this->createMock(CacheManager::class);
        $cacheManager->method('getCache')->with('jw_dashboard_version')->willReturn($this->cache);

        $this->subject = new Typo3VersionService($this->requestFactory, $this->typo3Version, $cacheManager);
    }

    #[Test]
    public function getVersionDataReturnsCachedDataIfPresent(): void
    {
        $cachedData = [
            'installed' => '14.3.4',
            'latest' => '14.3.5',
            'checkFailed' => false,
            'isUpToDate' => false,
            'isSecurityRelease' => false,
            'releaseDate' => 0,
            'releaseUrl' => 'https://get.typo3.org/version/14',
            'lifecycle' => null,
        ];

        $this->cache->method('get')->willReturn($cachedData);
        $this->requestFactory->expects(self::never())->method('request');

        self::assertSame($cachedData, $this->subject->getVersionData(self::API_URL, 86400, 10));
    }

    #[Test]
    public function getVersionDataRequestsLatestReleaseAndLifecycleOfInstalledMajorVersion(): void
    {
        $this->cache->method('get')->willReturn(false);
        $requestedUrls = [];
        $this->requestFactory
            ->method('request')
            ->willReturnCallback(function (string $url) use (&$requestedUrls): ResponseInterface {
                $requestedUrls[] = $url;

                return str_ends_with($url, '/release/latest')
                    ? $this->createResponse(200, ['version' => '14.3.4', 'type' => 'regular'])
                    : $this->createResponse(200, [
                        'release_date' => '2026-04-21T00:00:00+02:00',
                        'maintained_until' => '2029-06-30T00:00:00+02:00',
                        'elts_until' => '2032-06-30T00:00:00+02:00',
                    ]);
            });

        $result = $this->subject->getVersionData(self::API_URL, 86400, 10);

        self::assertSame(
            [
                'https://get.typo3.org/api/v1/major/14/release/latest',
                'https://get.typo3.org/api/v1/major/14',
            ],
            $requestedUrls,
        );
        self::assertNotNull($result['lifecycle']);
        self::assertSame((int)strtotime('2029-06-30T00:00:00+02:00'), $result['lifecycle']->maintainedUntil);
    }

    #[Test]
    public function getVersionDataSkipsLifecycleRequestIfVersionCheckFailed(): void
    {
        $this->cache->method('get')->willReturn(false);
        $this->requestFactory
            ->expects(self::once())
            ->method('request')
            ->willReturn($this->createResponse(500, []));

        self::assertNull($this->subject->getVersionData(self::API_URL, 86400, 10)['lifecycle']);
    }

    #[Test]
    public function hasCachedDataChecksVersionSpecificCacheEntry(): void
    {
        $this->cache->expects(self::once())->method('has')->willReturn(true);

        self::assertTrue($this->subject->hasCachedData(self::API_URL));
    }

    #[Test]
    public function getVersionDataDetectsSecurityUpdate(): void
    {
        $this->cache->method('get')->willReturn(false);
        $this->requestFactory
            ->method('request')
            ->willReturn($this->createResponse(200, [
                'version' => '14.3.9',
                'type' => 'security',
                'date' => '2026-10-13T09:22:58+02:00',
            ]));

        $result = $this->subject->getVersionData(self::API_URL, 86400, 10);

        self::assertSame('14.3.9', $result['latest']);
        self::assertFalse($result['checkFailed']);
        self::assertFalse($result['isUpToDate']);
        self::assertTrue($result['isSecurityRelease']);
        self::assertGreaterThan(0, $result['releaseDate']);
    }

    #[Test]
    public function getVersionDataDetectsUpToDateInstallation(): void
    {
        $this->cache->method('get')->willReturn(false);
        $this->requestFactory
            ->method('request')
            ->willReturn($this->createResponse(200, ['version' => '14.3.4', 'type' => 'security']));

        $result = $this->subject->getVersionData(self::API_URL, 86400, 10);

        self::assertTrue($result['isUpToDate']);
    }

    #[Test]
    public function getVersionDataMarksCheckAsFailedOnHttpErrorAndDoesNotCacheIt(): void
    {
        $this->cache->method('get')->willReturn(false);
        $this->cache->expects(self::never())->method('set');
        $this->requestFactory
            ->method('request')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $result = $this->subject->getVersionData(self::API_URL, 86400, 10);

        self::assertSame('14.3.4', $result['installed']);
        self::assertSame('', $result['latest']);
        self::assertTrue($result['checkFailed']);
        self::assertFalse($result['isUpToDate']);
    }

    #[Test]
    public function getVersionDataMarksCheckAsFailedOnNotFoundResponse(): void
    {
        $this->cache->method('get')->willReturn(false);
        $this->requestFactory->method('request')->willReturn($this->createResponse(404, []));

        self::assertTrue($this->subject->getVersionData(self::API_URL, 86400, 10)['checkFailed']);
    }

    #[Test]
    public function getVersionDataCachesSuccessfulLookup(): void
    {
        $this->cache->method('get')->willReturn(false);
        $this->requestFactory->method('request')->willReturn($this->createResponse(200, ['version' => '14.3.5']));
        $this->cache->expects(self::once())->method('set')->with(self::anything(), self::anything(), [], 86400);

        $this->subject->getVersionData(self::API_URL, 86400, 10);
    }

    /**
     * @param array<string, string> $json
     */
    private function createResponse(int $statusCode, array $json): ResponseInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn((string)json_encode($json));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn($stream);

        return $response;
    }
}
