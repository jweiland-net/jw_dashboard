<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/**
 * Dashboard preset for the TYPO3 core dashboard (EXT:dashboard), ignored if EXT:dashboard is not installed.
 * Offered in "Dashboard › Create dashboard" and pre-filled with the jweiland.net widgets.
 */
return [
    'jweiland' => [
        'title' => 'LLL:EXT:jw_dashboard/Resources/Private/Language/locallang.xlf:coreWidget.preset.title',
        'description' => 'LLL:EXT:jw_dashboard/Resources/Private/Language/locallang.xlf:coreWidget.preset.description',
        'iconIdentifier' => 'ext-jw-dashboard',
        'defaultWidgets' => ['jwDashboardSupport'],
        'showInWizard' => true,
    ],
];
