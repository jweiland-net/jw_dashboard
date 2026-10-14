<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Domain\Repository;

use JWeiland\JwDashboard\Domain\Model\MaintenanceContract;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Contracts are global records (usually stored on root level), so the storage page is ignored.
 *
 * @extends Repository<MaintenanceContract>
 */
class MaintenanceContractRepository extends Repository
{
    /**
     * Returns all not cancelled contracts ordered by end date (contracts without end date have end_date 0).
     *
     * @return QueryResultInterface<int, MaintenanceContract>
     */
    public function findCurrentOrderedByExpiry(): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->logicalNot($query->equals('status', 'cancelled')),
        );
        $query->setOrderings([
            'endDate' => QueryInterface::ORDER_ASCENDING,
            'title' => QueryInterface::ORDER_ASCENDING,
        ]);

        return $query->execute();
    }
}
