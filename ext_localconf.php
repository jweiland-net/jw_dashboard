<?php

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3')) {
    die('Access denied.');
}

use TYPO3\CMS\Core\Cache\Backend\FileBackend;

// Register dedicated caches so the dashboard widgets do not pollute the
// general framework cache and TTLs can be managed independently.
if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['jw_dashboard_news'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['jw_dashboard_news'] = [
        'backend' => FileBackend::class,
        'options' => [
            'defaultLifetime' => 3600,
        ],
    ];
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['jw_dashboard_version'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['jw_dashboard_version'] = [
        'backend' => FileBackend::class,
        'options' => [
            'defaultLifetime' => 86400,
        ],
    ];
}
