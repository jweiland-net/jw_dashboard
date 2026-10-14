<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Domain\Model;

enum ContractRenewalState: string
{
    case Active = 'active';
    case Expiring = 'expiring';
    case Expired = 'expired';
    case Unlimited = 'unlimited';

    /**
     * Matching TYPO3 backend state (badge-*, callout-*)
     */
    public function getSeverity(): string
    {
        return match ($this) {
            self::Active, self::Unlimited => 'success',
            self::Expiring => 'warning',
            self::Expired => 'danger',
        };
    }
}
