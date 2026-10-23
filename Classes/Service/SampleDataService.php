<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates and removes demo maintenance contracts and contact persons on root level (pid 0).
 * All dates are relative to today, so every renewal state of the dashboard widget is visible.
 * Sample records are tagged with MARKER, removal never touches other records.
 */
class SampleDataService
{
    public const MARKER = '[jw_dashboard sample data]';

    private const CONTRACT_TABLE = 'tx_jwdashboard_contract';
    private const PERSON_TABLE = 'tx_jwdashboard_contact_person';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    /**
     * @return array{contracts: int, persons: int, errors: list<string>}
     */
    public function create(\DateTimeImmutable $today): array
    {
        $this->remove();

        $date = static fn(string $modifier): string => $today->modify($modifier)->setTime(0, 0)->format('c');

        // Contact persons first: the inline MM field of the contracts needs their real uids
        $persons = $this->process([
            self::PERSON_TABLE => [
                'NEW_person_1' => $this->person('Anna Becker', 'Project Manager', 'anna.becker@example.com', '+49 711 000000-10', true),
                'NEW_person_2' => $this->person('Lukas Wagner', 'TYPO3 Developer', 'lukas.wagner@example.com', '+49 711 000000-20'),
                'NEW_person_3' => $this->person('Mia Schneider', 'Hosting & Operations', 'mia.schneider@example.com', '+49 711 000000-30'),
            ],
        ]);
        $uid = static fn(string $placeholder): string => (string)($persons['ids'][$placeholder] ?? '');

        $contracts = $this->process([
            self::CONTRACT_TABLE => [
                'NEW_contract_1' => $this->contract(
                    'TYPO3 Maintenance Premium',
                    'maintenance',
                    'SAMPLE-1001',
                    $date('-11 months -10 days'),
                    $date('+20 days'),
                    30,
                    $uid('NEW_person_1') . ',' . $uid('NEW_person_2'),
                ),
                'NEW_contract_2' => $this->contract(
                    'Managed Hosting Business',
                    'hosting',
                    'SAMPLE-1002',
                    $date('-6 months'),
                    $date('+6 months'),
                    30,
                    $uid('NEW_person_3'),
                ),
                'NEW_contract_3' => $this->contract(
                    'Support Package 20 h',
                    'support',
                    'SAMPLE-1003',
                    $date('-1 year -10 days'),
                    $date('-10 days'),
                    14,
                    $uid('NEW_person_2'),
                ),
                'NEW_contract_4' => $this->contract(
                    'Full Service SLA',
                    'full_service',
                    'SAMPLE-1004',
                    $date('-2 months'),
                    '',
                    30,
                    $uid('NEW_person_1'),
                ),
            ],
        ]);

        return [
            'contracts' => count($this->findSampleUids(self::CONTRACT_TABLE, 'notes')),
            'persons' => count($this->findSampleUids(self::PERSON_TABLE, 'bio')),
            'errors' => array_merge($persons['errors'], $contracts['errors']),
        ];
    }

    /**
     * @param array<string, array<string, array<string, int|string>>> $dataMap
     * @return array{ids: array<string, int>, errors: list<string>}
     */
    private function process(array $dataMap): array
    {
        $dataHandler = $this->createDataHandler();
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();

        $ids = [];
        foreach ($dataHandler->substNEWwithIDs as $placeholder => $id) {
            $ids[(string)$placeholder] = (int)$id;
        }

        return ['ids' => $ids, 'errors' => array_map(strval(...), $dataHandler->errorLog)];
    }

    /**
     * @return array{contracts: int, persons: int, errors: list<string>}
     */
    public function remove(): array
    {
        $contracts = $this->findSampleUids(self::CONTRACT_TABLE, 'notes');
        $persons = $this->findSampleUids(self::PERSON_TABLE, 'bio');

        $cmdMap = [];
        foreach ($contracts as $uid) {
            $cmdMap[self::CONTRACT_TABLE][$uid]['delete'] = 1;
        }
        foreach ($persons as $uid) {
            $cmdMap[self::PERSON_TABLE][$uid]['delete'] = 1;
        }

        $errors = [];
        if ($cmdMap !== []) {
            $dataHandler = $this->createDataHandler();
            $dataHandler->start([], $cmdMap);
            $dataHandler->process_cmdmap();
            $errors = array_map(strval(...), $dataHandler->errorLog);
        }

        return ['contracts' => count($contracts), 'persons' => count($persons), 'errors' => $errors];
    }

    /**
     * @return array<string, int|string>
     */
    private function person(string $name, string $role, string $email, string $phone, bool $isMainContact = false): array
    {
        return [
            'pid' => 0,
            'name' => $name,
            'role' => $role,
            'email' => $email,
            'phone' => $phone,
            'is_main_contact' => (int)$isMainContact,
            'bio' => self::MARKER . ' Fictional contact person for demonstration purposes.',
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function contract(
        string $title,
        string $type,
        string $clientNumber,
        string $startDate,
        string $endDate,
        int $renewalPromptDays,
        string $contactPersons,
    ): array {
        return [
            'pid' => 0,
            'title' => $title,
            'client_name' => 'Example Company Ltd.',
            'client_number' => $clientNumber,
            'contract_type' => $type,
            'status' => 'active',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'renewal_prompt_days' => $renewalPromptDays,
            'contact_persons' => $contactPersons,
            'notes' => self::MARKER . ' Demonstration record, remove with "typo3 jwdashboard:sample-data --remove".',
        ];
    }

    /**
     * @return list<int>
     */
    private function findSampleUids(string $table, string $markerField): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $uids = $queryBuilder
            ->select('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->like(
                    $markerField,
                    $queryBuilder->createNamedParameter($queryBuilder->escapeLikeWildcards(self::MARKER) . '%'),
                ),
                $queryBuilder->expr()->eq('deleted', 0),
            )
            ->executeQuery()
            ->fetchFirstColumn();

        return array_values(array_map(intval(...), array_filter($uids, is_numeric(...))));
    }

    /**
     * DataHandler is stateful, so a fresh instance is required per run
     */
    private function createDataHandler(): DataHandler
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->bypassAccessCheckForRecords = true;

        return $dataHandler;
    }
}
