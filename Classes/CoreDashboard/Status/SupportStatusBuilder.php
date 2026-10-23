<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\CoreDashboard\Status;

use JWeiland\JwDashboard\Domain\Model\ContractRenewalState;
use JWeiland\JwDashboard\Domain\Model\MaintenanceContract;
use JWeiland\JwDashboard\Domain\Model\SupportLifecycle;

/**
 * Turns the payloads of the JW Dashboard widgets "TYPO3 Version" and "Maintenance Contract"
 * into the status strip of the core dashboard widget.
 *
 * Pure: no services, no LanguageService, no clock. Texts are returned as LLL keys plus arguments.
 */
final readonly class SupportStatusBuilder
{
    public const LABEL_PREFIX = 'coreWidget.support.status.';

    /**
     * Maximum number of contracts listed in the detail line of the contract row
     */
    public const MAX_CONTRACT_DETAILS = 3;

    private const DATE_FORMAT = 'd.m.Y';

    /**
     * @param array<string, mixed> $version Payload of Typo3VersionWidget, [] if disabled or unavailable
     * @param array<string, mixed> $contracts Payload of MaintenanceContractWidget, [] if disabled or unavailable
     * @param string $fallbackModule Module opened by the contract "Review" action if the user cannot edit contracts
     */
    public function build(array $version, array $contracts, string $fallbackModule = ''): SupportStatus
    {
        $items = [];
        $facts = [];

        if ($version !== []) {
            $versionItem = $this->buildVersionItem($version);
            if ($versionItem instanceof StatusItem) {
                $items[] = $versionItem;
            } else {
                $facts = [...$facts, ...$this->buildVersionFacts($version)];
            }
        }

        if ($contracts !== []) {
            $contractItem = $this->buildContractItem($contracts, $fallbackModule);
            if ($contractItem instanceof StatusItem) {
                $items[] = $contractItem;
            } else {
                $facts = [...$facts, ...$this->buildContractFacts($contracts)];
            }
        }

        // Facts are only shown in the "all good" strip
        return new SupportStatus($items, $items === [] ? $facts : []);
    }

    /**
     * @param array<string, mixed> $version
     */
    private function buildVersionItem(array $version): ?StatusItem
    {
        if ((bool)($version['checkFailed'] ?? false)) {
            return new StatusItem(
                identifier: 'typo3Version',
                severity: StatusSeverity::Info,
                icon: 'actions-info-circle',
                title: 'widget.typo3Version.checkFailed',
            );
        }

        $installed = (string)($version['installed'] ?? '');
        $latest = (string)($version['latest'] ?? '');
        $releaseUrl = (string)($version['releaseUrl'] ?? '');
        $lifecycle = is_array($version['lifecycle'] ?? null) ? $version['lifecycle'] : [];

        if (($lifecycle['phase'] ?? '') === SupportLifecycle::PHASE_UNSUPPORTED) {
            return new StatusItem(
                identifier: 'typo3Version',
                severity: StatusSeverity::Danger,
                icon: 'actions-exclamation-circle',
                title: self::LABEL_PREFIX . 'unsupported',
                titleArguments: [(int)($version['majorVersion'] ?? (int)$installed)],
                body: self::LABEL_PREFIX . 'unsupported.body',
                actionUrl: $releaseUrl,
                actionLabel: self::LABEL_PREFIX . 'releaseInfo',
                actionExternal: true,
            );
        }

        if ((bool)($version['isUpToDate'] ?? false)) {
            return null;
        }

        if ((bool)($version['isSecurityRelease'] ?? false)) {
            return new StatusItem(
                identifier: 'typo3Version',
                severity: StatusSeverity::Danger,
                icon: 'actions-shield',
                title: self::LABEL_PREFIX . 'security',
                body: 'widget.typo3Version.securityUpdate.body',
                bodyArguments: [$latest],
                actionUrl: $releaseUrl,
                actionLabel: self::LABEL_PREFIX . 'releaseInfo',
                actionExternal: true,
            );
        }

        return new StatusItem(
            identifier: 'typo3Version',
            severity: StatusSeverity::Warning,
            icon: 'actions-exclamation-triangle',
            title: self::LABEL_PREFIX . 'update',
            body: self::LABEL_PREFIX . 'update.body',
            bodyArguments: [$installed, $latest],
            actionUrl: $releaseUrl,
            actionLabel: self::LABEL_PREFIX . 'releaseInfo',
            actionExternal: true,
        );
    }

    /**
     * @param array<string, mixed> $version
     * @return list<array{key: string, arguments: array<int, string|int>}>
     */
    private function buildVersionFacts(array $version): array
    {
        $facts = [
            ['key' => self::LABEL_PREFIX . 'ok.version', 'arguments' => [(string)($version['installed'] ?? '')]],
        ];

        $lifecycle = is_array($version['lifecycle'] ?? null) ? $version['lifecycle'] : [];
        $phase = $lifecycle['phase'] ?? '';
        if ($phase === SupportLifecycle::PHASE_REGULAR && (int)($lifecycle['maintainedUntil'] ?? 0) > 0) {
            $facts[] = [
                'key' => self::LABEL_PREFIX . 'ok.supportUntil',
                'arguments' => [date(self::DATE_FORMAT, (int)$lifecycle['maintainedUntil'])],
            ];
        } elseif ($phase === SupportLifecycle::PHASE_ELTS && (int)($lifecycle['eltsUntil'] ?? 0) > 0) {
            $facts[] = [
                'key' => self::LABEL_PREFIX . 'ok.eltsUntil',
                'arguments' => [date(self::DATE_FORMAT, (int)$lifecycle['eltsUntil'])],
            ];
        }

        return $facts;
    }

    /**
     * @param array<string, mixed> $contracts
     */
    private function buildContractItem(array $contracts, string $fallbackModule): ?StatusItem
    {
        $attention = array_values(array_filter(
            is_array($contracts['contracts'] ?? null) ? $contracts['contracts'] : [],
            static fn(mixed $entry): bool => is_array($entry)
                && in_array($entry['state'] ?? '', [ContractRenewalState::Expiring->value, ContractRenewalState::Expired->value], true),
        ));

        if ($attention !== []) {
            $count = count($attention);
            $manageUrl = (string)($contracts['manageUrl'] ?? '');

            return new StatusItem(
                identifier: 'maintenanceContract',
                severity: StatusSeverity::Warning,
                icon: 'actions-certificate',
                title: self::LABEL_PREFIX . ($count === 1 ? 'contracts.one' : 'contracts.many'),
                titleArguments: [$count],
                details: $this->buildContractDetails($attention),
                actionUrl: $manageUrl,
                actionLabel: self::LABEL_PREFIX . 'review',
                actionModule: $manageUrl === '' ? $fallbackModule : '',
            );
        }

        if ((bool)($contracts['showSignupPrompt'] ?? false)) {
            return new StatusItem(
                identifier: 'maintenanceContract',
                severity: StatusSeverity::Info,
                icon: 'actions-certificate',
                title: 'widget.maintenanceContract.noContract',
                body: 'widget.maintenanceContract.signupPrompt',
                actionUrl: (string)($contracts['signupUrl'] ?? ''),
                actionLabel: 'widget.maintenanceContract.signupLink',
                actionExternal: true,
            );
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $attention
     * @return list<array{key: string, arguments: array<int, string|int>}>
     */
    private function buildContractDetails(array $attention): array
    {
        $details = [];
        foreach (array_slice($attention, 0, self::MAX_CONTRACT_DETAILS) as $entry) {
            $contract = $entry['contract'] ?? null;
            $title = $contract instanceof MaintenanceContract ? $contract->getTitle() : '';
            $days = $entry['daysRemaining'] ?? null;

            $details[] = match (true) {
                $entry['state'] === ContractRenewalState::Expired->value => [
                    'key' => self::LABEL_PREFIX . 'contract.expired',
                    'arguments' => [$title],
                ],
                is_int($days) && $days > 0 => [
                    'key' => self::LABEL_PREFIX . ($days === 1 ? 'contract.endsTomorrow' : 'contract.endsIn'),
                    'arguments' => $days === 1 ? [$title] : [$title, $days],
                ],
                default => [
                    'key' => self::LABEL_PREFIX . 'contract.endsToday',
                    'arguments' => [$title],
                ],
            };
        }

        $more = count($attention) - self::MAX_CONTRACT_DETAILS;
        if ($more > 0) {
            $details[] = ['key' => self::LABEL_PREFIX . 'contract.more', 'arguments' => [$more]];
        }

        return $details;
    }

    /**
     * @param array<string, mixed> $contracts
     * @return list<array{key: string, arguments: array<int, string|int>}>
     */
    private function buildContractFacts(array $contracts): array
    {
        $count = count(is_array($contracts['contracts'] ?? null) ? $contracts['contracts'] : []);
        if ($count === 0) {
            return [];
        }

        return [[
            'key' => self::LABEL_PREFIX . ($count === 1 ? 'ok.contracts.one' : 'ok.contracts.many'),
            'arguments' => [$count],
        ]];
    }
}
