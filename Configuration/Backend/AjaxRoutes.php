<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use JWeiland\JwDashboard\Controller\Ajax\WidgetRefreshController;

/**
 * AJAX route definitions for EXT:jw_dashboard.
 * These endpoints allow individual dashboard widgets to refresh their content
 * asynchronously without a full page reload.
 */
return [
    'jw_dashboard_widget_refresh' => [
        'path' => '/jw-dashboard/widget/refresh',
        'target' => WidgetRefreshController::class . '::refreshAction',
    ],
];
