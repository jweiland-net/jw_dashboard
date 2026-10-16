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
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Gathers basic site health metrics from TYPO3 system tables (read-only, live workspace).
 * Metrics of not installed system extensions are returned as null.
 */
class SiteHealthService
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly PackageManager $packageManager,
    ) {}

    /**
     * @return array{
     *     pageCount: int,
     *     hiddenPageCount: int,
     *     fileCount: int,
     *     brokenLinksCount: int|null,
     *     schedulerTaskCount: int|null,
     * }
     */
    public function collectMetrics(): array
    {
        return [
            'pageCount' => $this->countPages(false),
            'hiddenPageCount' => $this->countPages(true),
            'fileCount' => $this->countFiles(),
            'brokenLinksCount' => $this->packageManager->isPackageActive('linkvalidator')
                ? $this->countBrokenLinks()
                : null,
            'schedulerTaskCount' => $this->packageManager->isPackageActive('scheduler')
                ? $this->countSchedulerTasks()
                : null,
        ];
    }

    private function countPages(bool $hidden): int
    {
        $queryBuilder = $this->getQueryBuilder('pages');
        $queryBuilder->getRestrictions()->add(new WorkspaceRestriction(0));

        $queryBuilder
            ->count('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter((int)$hidden, Connection::PARAM_INT)),
            );

        return $this->fetchCount($queryBuilder);
    }

    private function countFiles(): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file');
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->count('uid')
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq('missing', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            );

        return $this->fetchCount($queryBuilder);
    }

    private function countBrokenLinks(): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_linkvalidator_link');
        $queryBuilder->getRestrictions()->removeAll();

        $queryBuilder
            ->count('uid')
            ->from('tx_linkvalidator_link');

        return $this->fetchCount($queryBuilder);
    }

    private function countSchedulerTasks(): int
    {
        $queryBuilder = $this->getQueryBuilder('tx_scheduler_task');

        $queryBuilder
            ->count('uid')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->eq('disable', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            );

        return $this->fetchCount($queryBuilder);
    }

    private function fetchCount(QueryBuilder $queryBuilder): int
    {
        $count = $queryBuilder->executeQuery()->fetchOne();

        return is_numeric($count) ? (int)$count : 0;
    }

    private function getQueryBuilder(string $table): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(new DeletedRestriction());

        return $queryBuilder;
    }
}
