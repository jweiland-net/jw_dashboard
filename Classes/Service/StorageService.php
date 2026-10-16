<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\FileType;

/**
 * Summarises the files indexed in FAL (sys_file) by file type.
 */
class StorageService
{
    /**
     * Fixed category order. The order defines the chart color slot, so it must never be sorted by size.
     */
    public const CATEGORIES = ['images', 'documents', 'videos', 'audio', 'other'];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * @return array{
     *     totalSize: int,
     *     totalCount: int,
     *     categories: list<array{key: string, count: int, size: int, share: float}>,
     * }
     */
    public function getStatistics(): array
    {
        $counts = array_fill_keys(self::CATEGORIES, 0);
        $sizes = array_fill_keys(self::CATEGORIES, 0);

        foreach ($this->fetchTotalsByType() as $row) {
            $category = $this->mapType((int)$row['type']);
            $counts[$category] += (int)$row['files'];
            $sizes[$category] += (int)$row['bytes'];
        }

        $totalSize = array_sum($sizes);
        $totalCount = array_sum($counts);

        $result = [];
        foreach (self::CATEGORIES as $key) {
            $result[] = [
                'key' => $key,
                'count' => $counts[$key],
                'size' => $sizes[$key],
                'share' => $totalSize > 0 ? round($sizes[$key] / $totalSize * 100, 2) : 0.0,
            ];
        }

        return [
            'totalSize' => $totalSize,
            'totalCount' => $totalCount,
            'categories' => $result,
        ];
    }

    private function mapType(int $type): string
    {
        return match (FileType::tryFrom($type)) {
            FileType::IMAGE => 'images',
            FileType::TEXT, FileType::APPLICATION => 'documents',
            FileType::VIDEO => 'videos',
            FileType::AUDIO => 'audio',
            default => 'other',
        };
    }

    /**
     * @return iterable<array{type: int|string, files: int|string, bytes: int|string|null}>
     */
    private function fetchTotalsByType(): iterable
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file');
        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->select('type')
            ->addSelectLiteral(
                $queryBuilder->expr()->count('uid', 'files'),
                $queryBuilder->expr()->sum('size', 'bytes'),
            )
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq('missing', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->groupBy('type')
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            /** @var array{type: int|string, files: int|string, bytes: int|string|null} $row */
            yield $row;
        }
    }
}
