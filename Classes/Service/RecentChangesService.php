<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * Returns the latest created/updated pages and content elements of the record history,
 * limited to records the given backend user is allowed to see.
 */
class RecentChangesService
{
    private const TABLES = ['pages', 'tt_content'];

    /**
     * History rows scanned to find enough distinct, accessible records
     */
    private const SCAN_LIMIT = 300;

    /**
     * @var array<int, string>
     */
    private array $userNames = [];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * @return list<array{
     *     table: string,
     *     uid: int,
     *     pageId: int,
     *     title: string,
     *     row: array<string, mixed>,
     *     tstamp: int,
     *     action: string,
     *     userName: string,
     * }>
     */
    public function getRecentChanges(int $limit, BackendUserAuthentication $backendUser): array
    {
        $pagePermsClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $changes = [];
        $seen = [];

        foreach ($this->fetchHistory() as $history) {
            $table = (string)$history['tablename'];
            $uid = (int)$history['recuid'];
            $key = $table . ':' . $uid;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            if (!$backendUser->check('tables_select', $table)) {
                continue;
            }

            $row = BackendUtility::getRecord($table, $uid);
            if ($row === null) {
                // Deleted in the meantime
                continue;
            }

            $pageId = $table === 'pages' ? $uid : (int)($row['pid'] ?? 0);
            if (!$backendUser->isAdmin() && BackendUtility::readPageAccess($pageId, $pagePermsClause) === false) {
                continue;
            }

            $changes[] = [
                'table' => $table,
                'uid' => $uid,
                'pageId' => $pageId,
                'title' => BackendUtility::getRecordTitle($table, $row),
                'row' => $row,
                'tstamp' => (int)$history['tstamp'],
                'action' => (int)$history['actiontype'] === 1 ? 'created' : 'updated',
                'userName' => $this->getUserName((int)$history['userid']),
            ];

            if (count($changes) >= $limit) {
                break;
            }
        }

        return $changes;
    }

    /**
     * @return iterable<array{tablename: string, recuid: int|string, tstamp: int|string, actiontype: int|string, userid: int|string|null}>
     */
    private function fetchHistory(): iterable
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_history');
        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->select('tablename', 'recuid', 'tstamp', 'actiontype', 'userid')
            ->from('sys_history')
            ->where(
                $queryBuilder->expr()->in(
                    'tablename',
                    $queryBuilder->createNamedParameter(self::TABLES, Connection::PARAM_STR_ARRAY),
                ),
                $queryBuilder->expr()->in(
                    'actiontype',
                    $queryBuilder->createNamedParameter([1, 2], Connection::PARAM_INT_ARRAY),
                ),
                $queryBuilder->expr()->eq('workspace', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('tstamp', 'DESC')
            ->addOrderBy('uid', 'DESC')
            ->setMaxResults(self::SCAN_LIMIT)
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            /** @var array{tablename: string, recuid: int|string, tstamp: int|string, actiontype: int|string, userid: int|string|null} $row */
            yield $row;
        }
    }

    private function getUserName(int $userId): string
    {
        if ($userId === 0) {
            return '';
        }

        if (!isset($this->userNames[$userId])) {
            $user = BackendUtility::getRecord('be_users', $userId, ['username', 'realName']) ?? [];
            $realName = trim((string)($user['realName'] ?? ''));
            $this->userNames[$userId] = $realName !== '' ? $realName : (string)($user['username'] ?? '');
        }

        return $this->userNames[$userId];
    }
}
