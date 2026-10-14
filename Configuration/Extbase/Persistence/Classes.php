<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use JWeiland\JwDashboard\Domain\Model\ContactPerson;
use JWeiland\JwDashboard\Domain\Model\MaintenanceContract;

return [
    ContactPerson::class => [
        'tableName' => 'tx_jwdashboard_contact_person',
    ],
    MaintenanceContract::class => [
        'tableName' => 'tx_jwdashboard_contract',
    ],
];
