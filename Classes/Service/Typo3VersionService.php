<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Service;

use JWeiland\JwDashboard\Domain\Model\SupportLifecycle;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * Compares the installed TYPO3 version against the latest release of the same major version
 * published at get.typo3.org (endpoint: <apiUrl>major/<major>/release/latest) and fetches
 * the support lifecycle of the major version (endpoint: <apiUrl>major/<major>).
 */
class Typo3VersionService
{
    /**
     * Increase when the structure of the cached data changes
     */
    private const CACHE_VERSION = 2;

    private readonly FrontendInterface $cache;

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly Typo3Version $typo3Version,
        CacheManager $cacheManager,
    ) {
        $this->cache = $cacheManager->getCache('jw_dashboard_version');
    }

    /**
     * @return array{
     *     installed: string,
     *     latest: string,
     *     checkFailed: bool,
     *     isUpToDate: bool,
     *     isSecurityRelease: bool,
     *     releaseDate: int,
     *     releaseUrl: string,
     *     lifecycle: SupportLifecycle|null,
     * }
     */
    public function getVersionData(string $apiUrl, int $cacheTtl, int $timeout): array
    {
        $installedVersion = $this->typo3Version->getVersion();
        $cacheKey = $this->getCacheKey($apiUrl);

        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            /** @var array{installed: string, latest: string, checkFailed: bool, isUpToDate: bool, isSecurityRelease: bool, releaseDate: int, releaseUrl: string, lifecycle: SupportLifecycle|null} $cached */
            return $cached;
        }

        $release = $this->fetchLatestRelease($apiUrl, $timeout);
        $latestVersion = $release['version'] ?? '';

        $data = [
            'installed' => $installedVersion,
            'latest' => $latestVersion,
            'checkFailed' => $latestVersion === '',
            'isUpToDate' => $latestVersion !== '' && version_compare($installedVersion, $latestVersion, '>='),
            'isSecurityRelease' => ($release['type'] ?? '') === 'security',
            'releaseDate' => (int)strtotime($release['date'] ?? ''),
            'releaseUrl' => 'https://get.typo3.org/version/' . $this->typo3Version->getMajorVersion(),
            'lifecycle' => $latestVersion === '' ? null : $this->fetchLifecycle($apiUrl, $timeout),
        ];

        // Do not keep failed lookups for the whole TTL, try again on next request
        if (!$data['checkFailed']) {
            $this->cache->set($cacheKey, $data, [], $cacheTtl);
        }

        return $data;
    }

    public function hasCachedData(string $apiUrl): bool
    {
        return $this->cache->has($this->getCacheKey($apiUrl));
    }

    public function flushCache(): void
    {
        $this->cache->flush();
    }

    private function getCacheKey(string $apiUrl): string
    {
        return sha1(self::CACHE_VERSION . $apiUrl . $this->typo3Version->getVersion());
    }

    private function fetchLifecycle(string $apiUrl, int $timeout): ?SupportLifecycle
    {
        $json = $this->fetchJson(rtrim($apiUrl, '/') . '/major/' . $this->typo3Version->getMajorVersion(), $timeout);

        return $json === null ? null : SupportLifecycle::fromArray($json);
    }

    /**
     * @return array<mixed>|null
     */
    private function fetchJson(string $url, int $timeout): ?array
    {
        try {
            $response = $this->requestFactory->request($url, 'GET', ['timeout' => $timeout]);
            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $json = json_decode((string)$response->getBody(), true);

            return is_array($json) ? $json : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{version?: string, type?: string, date?: string}
     */
    private function fetchLatestRelease(string $apiUrl, int $timeout): array
    {
        $json = $this->fetchJson(rtrim($apiUrl, '/') . '/major/' . $this->typo3Version->getMajorVersion() . '/release/latest', $timeout);
        if ($json === null || !is_string($json['version'] ?? null)) {
            return [];
        }

        return [
            'version' => $json['version'],
            'type' => is_string($json['type'] ?? null) ? $json['type'] : '',
            'date' => is_string($json['date'] ?? null) ? $json['date'] : '',
        ];
    }
}
