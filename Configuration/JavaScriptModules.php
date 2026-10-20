<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    'dependencies' => [
        'backend',
        'core',
    ],
    'tags' => [
        'backend.module',
    ],
    'imports' => [
        '@jweiland/jw-dashboard/' => 'EXT:jw_dashboard/Resources/Public/JavaScript/',
    ],
];
