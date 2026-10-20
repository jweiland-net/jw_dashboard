<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'ext-jw-dashboard' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:jw_dashboard/Resources/Public/Icons/module_jw_dashboard.svg',
    ],
];
