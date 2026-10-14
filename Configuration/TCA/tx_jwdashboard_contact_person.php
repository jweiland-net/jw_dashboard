<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return (static function (): array {
    $languageFile = 'LLL:EXT:jw_dashboard/Resources/Private/Language/locallang.xlf';

    return [
        'ctrl' => [
            'title' => $languageFile . ':tx_jwdashboard_contact_person',
            'label' => 'name',
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'sortby' => 'sorting',
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
            'searchFields' => 'name,email,role',
            'iconfile' => 'EXT:jw_dashboard/Resources/Public/Icons/module_jw_dashboard.svg',
            // Global records: stored on root level (admins) or in any sysfolder/page
            'rootLevel' => -1,
            'security' => [
                'ignorePageTypeRestriction' => true,
            ],
        ],
        'types' => [
            '1' => [
                'showitem' => '
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                        name, role, is_main_contact,
                    --div--;' . $languageFile . ':tab.contact,
                        email, phone, mobile,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:images,
                        photo,
                    --div--;' . $languageFile . ':tab.bio,
                        bio,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                        hidden,
                ',
            ],
        ],
        'columns' => [
            'hidden' => [
                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'items' => [
                        ['label' => '', 'value' => 1],
                    ],
                ],
            ],
            'name' => [
                'label' => $languageFile . ':tx_jwdashboard_contact_person.name',
                'config' => [
                    'type' => 'input',
                    'size' => 50,
                    'max' => 255,
                    'eval' => 'trim',
                    'required' => true,
                ],
            ],
            'email' => [
                'label' => $languageFile . ':tx_jwdashboard_contact_person.email',
                'config' => [
                    'type' => 'email',
                    'size' => 50,
                ],
            ],
            'phone' => [
                'label' => $languageFile . ':tx_jwdashboard_contact_person.phone',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'max' => 100,
                    'eval' => 'trim',
                ],
            ],
            'mobile' => [
                'label' => $languageFile . ':tx_jwdashboard_contact_person.mobile',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'max' => 100,
                    'eval' => 'trim',
                ],
            ],
            'role' => [
                'label' => $languageFile . ':tx_jwdashboard_contact_person.role',
                'config' => [
                    'type' => 'input',
                    'size' => 50,
                    'max' => 255,
                    'eval' => 'trim',
                ],
            ],
            'photo' => [
                'label' => $languageFile . ':tx_jwdashboard_contact_person.photo',
                'config' => [
                    'type' => 'file',
                    'allowed' => 'common-image-types',
                    'maxitems' => 1,
                    'appearance' => [
                        'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                    ],
                    'overrideChildTca' => [
                        'types' => [
                            '0' => [
                                'showitem' => '
                                    --palette--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                    --palette--;;filePalette
                                ',
                            ],
                        ],
                    ],
                ],
            ],
            'is_main_contact' => [
                'label' => $languageFile . ':tx_jwdashboard_contact_person.is_main_contact',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'items' => [
                        ['label' => '', 'value' => 1],
                    ],
                ],
            ],
            'bio' => [
                'label' => $languageFile . ':tx_jwdashboard_contact_person.bio',
                'config' => [
                    'type' => 'text',
                    'cols' => 60,
                    'rows' => 5,
                    'eval' => 'trim',
                ],
            ],
        ],
    ];
})();
