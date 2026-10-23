<?php

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'JW Dashboard',
    'description' => 'A configurable backend dashboard module for all jweiland projects. Displays TYPO3 version status, latest news, maintenance contract info, site health, extension updates, and quick links.',
    'category' => 'be',
    'author' => 'Stefan Froemken, Hoja Mustaffa Abdul Latheef',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0-14.99.99',
            'backend' => '14.0.0-14.99.99',
            'extbase' => '14.0.0-14.99.99',
            'fluid' => '14.0.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [
            'dashboard' => '',
            'scheduler' => '',
            'reports' => '',
            'linkvalidator' => '',
        ],
    ],
];
