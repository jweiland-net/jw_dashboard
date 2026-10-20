<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use JWeiland\JwDashboard\Controller\DashboardController;

/**
 * Definitions for modules provided by EXT:jw_dashboard
 *
 * Registered as standalone main module (no parent, no navigation component), so the module is
 * reachable directly from the module menu without a page tree. Do not switch to "controllerActions":
 * Extbase modules are always attached to the "content" main module.
 */
return [
    'jw_dashboard' => [
        'standalone' => true,
        'position' => ['before' => 'content'],
        'access' => 'user',
        'path' => '/module/jw-dashboard',
        'labels' => 'LLL:EXT:jw_dashboard/Resources/Private/Language/locallang_module.xlf',
        'iconIdentifier' => 'ext-jw-dashboard',
        'routes' => [
            '_default' => [
                'target' => DashboardController::class . '::overviewAction',
            ],
        ],
    ],
];
