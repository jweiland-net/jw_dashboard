<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Domain\Repository;

use JWeiland\JwDashboard\Domain\Model\ContactPerson;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Contact persons are global records (usually stored on root level), so the storage page is ignored.
 *
 * @extends Repository<ContactPerson>
 */
class ContactPersonRepository extends Repository
{
    /**
     * Returns the contact person designated as the primary contact, if any.
     */
    public function findMainContact(): ?ContactPerson
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->matching(
            $query->equals('isMainContact', true),
        );
        $query->setLimit(1);

        $result = $query->execute()->getFirst();

        return $result instanceof ContactPerson ? $result : null;
    }
}
