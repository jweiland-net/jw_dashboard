<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */


namespace JWeiland\JwDashboard\CoreDashboard\Status;

/**
 * Severity of a status item, values match the TYPO3 backend states (callout-*, badge-*)
 */
enum StatusSeverity: string
{
    case Success = 'success';
    case Info = 'info';
    case Warning = 'warning';
    case Danger = 'danger';

    public function getWeight(): int
    {
        return match ($this) {
            self::Success => 0,
            self::Info => 1,
            self::Warning => 2,
            self::Danger => 3,
        };
    }

    public function needsAttention(): bool
    {
        return $this === self::Warning || $this === self::Danger;
    }
}
