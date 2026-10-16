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

/**
 * Aggregates backend record changes of the record history (sys_history) per calendar week.
 */
class ContentActivityService
{
    private const ACTION_CREATED = 1;
    private const ACTION_UPDATED = 2;
    private const ACTION_DELETED = 3;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * Weeks are ordered from oldest to current week (Monday as first day of week).
     *
     * @return array{
     *     weeks: list<array{start: int, end: int, count: int, isCurrent: bool}>,
     *     total: int,
     *     created: int,
     *     updated: int,
     *     deleted: int,
     *     editors: int,
     *     maxCount: int,
     * }
     */
    public function getWeeklyActivity(int $numberOfWeeks, \DateTimeImmutable $today): array
    {
        $currentWeekStart = $today->modify('monday this week')->setTime(0, 0);
        $firstWeekStart = $currentWeekStart->modify('-' . ($numberOfWeeks - 1) . ' weeks');

        $weeks = [];
        for ($i = 0; $i < $numberOfWeeks; $i++) {
            $start = $firstWeekStart->modify('+' . $i . ' weeks');
            $weeks[] = [
                'start' => $start->getTimestamp(),
                // Last second of Sunday, DST safe
                'end' => $start->modify('+1 week')->getTimestamp() - 1,
                'isCurrent' => $i === $numberOfWeeks - 1,
            ];
        }

        $counts = array_fill(0, $numberOfWeeks, 0);
        $actionCounts = [self::ACTION_CREATED => 0, self::ACTION_UPDATED => 0, self::ACTION_DELETED => 0];
        $editors = [];

        foreach ($this->fetchHistory($firstWeekStart->getTimestamp()) as $row) {
            $weekIndex = $this->findWeekIndex($weeks, (int)$row['tstamp']);
            if ($weekIndex === null) {
                continue;
            }

            $counts[$weekIndex]++;
            $actionType = (int)$row['actiontype'];
            if (isset($actionCounts[$actionType])) {
                $actionCounts[$actionType]++;
            }
            if ((int)$row['userid'] > 0) {
                $editors[(int)$row['userid']] = true;
            }
        }

        $result = [];
        foreach ($weeks as $index => $week) {
            $result[] = [
                'start' => $week['start'],
                'end' => $week['end'],
                'count' => $counts[$index],
                'isCurrent' => $week['isCurrent'],
            ];
        }

        return [
            'weeks' => $result,
            'total' => array_sum($counts),
            'created' => $actionCounts[self::ACTION_CREATED],
            'updated' => $actionCounts[self::ACTION_UPDATED],
            'deleted' => $actionCounts[self::ACTION_DELETED],
            'editors' => count($editors),
            'maxCount' => $counts === [] ? 0 : max($counts),
        ];
    }

    /**
     * @param list<array{start: int, end: int, isCurrent: bool}> $weeks
     */
    private function findWeekIndex(array $weeks, int $timestamp): ?int
    {
        foreach ($weeks as $index => $week) {
            if ($timestamp >= $week['start'] && $timestamp <= $week['end']) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return iterable<array{tstamp: int|string, actiontype: int|string, userid: int|string|null}>
     */
    private function fetchHistory(int $since): iterable
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_history');
        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder
            ->select('tstamp', 'actiontype', 'userid')
            ->from('sys_history')
            ->where(
                $queryBuilder->expr()->gte('tstamp', $queryBuilder->createNamedParameter($since, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('usertype', $queryBuilder->createNamedParameter('BE')),
                $queryBuilder->expr()->in(
                    'actiontype',
                    $queryBuilder->createNamedParameter(
                        [self::ACTION_CREATED, self::ACTION_UPDATED, self::ACTION_DELETED],
                        Connection::PARAM_INT_ARRAY,
                    ),
                ),
            )
            ->executeQuery();

        while ($row = $result->fetchAssociative()) {
            /** @var array{tstamp: int|string, actiontype: int|string, userid: int|string|null} $row */
            yield $row;
        }
    }
}
