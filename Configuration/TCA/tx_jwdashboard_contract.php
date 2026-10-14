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
            'title' => $languageFile . ':tx_jwdashboard_contract',
            'label' => 'title',
            'label_alt' => 'client_name',
            'label_alt_force' => true,
            'tstamp' => 'tstamp',
            'crdate' => 'crdate',
            'sortby' => 'sorting',
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
            'searchFields' => 'title,client_name,client_number',
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
                        title, client_name, client_number, contract_type, status,
                    --div--;' . $languageFile . ':tab.dates,
                        start_date, end_date, renewal_prompt_days,
                    --div--;' . $languageFile . ':tab.contacts,
                        contact_persons,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                        notes,
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
            'title' => [
                'label' => $languageFile . ':tx_jwdashboard_contract.title',
                'config' => [
                    'type' => 'input',
                    'size' => 50,
                    'max' => 255,
                    'eval' => 'trim',
                    'required' => true,
                ],
            ],
            'client_name' => [
                'label' => $languageFile . ':tx_jwdashboard_contract.client_name',
                'config' => [
                    'type' => 'input',
                    'size' => 50,
                    'max' => 255,
                    'eval' => 'trim',
                    'required' => true,
                ],
            ],
            'client_number' => [
                'label' => $languageFile . ':tx_jwdashboard_contract.client_number',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'max' => 100,
                    'eval' => 'trim',
                ],
            ],
            'start_date' => [
                'label' => $languageFile . ':tx_jwdashboard_contract.start_date',
                'config' => [
                    'type' => 'datetime',
                    'format' => 'date',
                ],
            ],
            'end_date' => [
                'label' => $languageFile . ':tx_jwdashboard_contract.end_date',
                'config' => [
                    'type' => 'datetime',
                    'format' => 'date',
                ],
            ],
            'status' => [
                'label' => $languageFile . ':tx_jwdashboard_contract.status',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['label' => $languageFile . ':contract.status.active', 'value' => 'active'],
                        ['label' => $languageFile . ':contract.status.expiring', 'value' => 'expiring'],
                        ['label' => $languageFile . ':contract.status.expired', 'value' => 'expired'],
                        ['label' => $languageFile . ':contract.status.cancelled', 'value' => 'cancelled'],
                    ],
                    'default' => 'active',
                ],
            ],
            'contract_type' => [
                'label' => $languageFile . ':tx_jwdashboard_contract.contract_type',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['label' => $languageFile . ':contract.type.maintenance', 'value' => 'maintenance'],
                        ['label' => $languageFile . ':contract.type.hosting', 'value' => 'hosting'],
                        ['label' => $languageFile . ':contract.type.support', 'value' => 'support'],
                        ['label' => $languageFile . ':contract.type.full_service', 'value' => 'full_service'],
                    ],
                    'default' => 'maintenance',
                ],
            ],
            'renewal_prompt_days' => [
                'label' => $languageFile . ':tx_jwdashboard_contract.renewal_prompt_days',
                'config' => [
                    'type' => 'number',
                    'size' => 5,
                    'range' => ['lower' => 1, 'upper' => 365],
                    'default' => 30,
                ],
            ],
            'notes' => [
                'label' => $languageFile . ':tx_jwdashboard_contract.notes',
                'config' => [
                    'type' => 'text',
                    'cols' => 60,
                    'rows' => 5,
                    'eval' => 'trim',
                ],
            ],
            'contact_persons' => [
                'label' => $languageFile . ':tx_jwdashboard_contract.contact_persons',
                'config' => [
                    'type' => 'inline',
                    'foreign_table' => 'tx_jwdashboard_contact_person',
                    'MM' => 'tx_jwdashboard_contract_contactperson_mm',
                    'maxitems' => 10,
                    'appearance' => [
                        'collapseAll' => true,
                        'expandSingle' => true,
                        'useSortable' => true,
                        'showPossibleLocalizationRecords' => false,
                        'showAllLocalizationLink' => false,
                        'showSynchronizationLink' => false,
                        'enabledControls' => [
                            'info' => false,
                            'new' => true,
                            'dragdrop' => true,
                            'sort' => true,
                            'hide' => true,
                            'delete' => true,
                        ],
                    ],
                ],
            ],
        ],
    ];
})();
