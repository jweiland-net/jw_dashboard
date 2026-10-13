<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Configuration;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

/**
 * Reads the raw extension configuration array and hydrates a DashboardExtConf DTO.
 * Inject this service wherever typed access to extension settings is required.
 */
final class ExtensionConfigurationManager
{
    private const EXTENSION_KEY = 'jw_dashboard';

    private ?DashboardExtConf $extConf = null;

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function get(): DashboardExtConf
    {
        if ($this->extConf !== null) {
            return $this->extConf;
        }

        $raw = $this->extensionConfiguration->get(self::EXTENSION_KEY);
        $raw = is_array($raw) ? $raw : [];

        $this->extConf = new DashboardExtConf(
            enableWidgetTypo3Version: $this->getBool($raw, 'enableWidgetTypo3Version', true),
            enableWidgetTypo3News: $this->getBool($raw, 'enableWidgetTypo3News', true),
            enableWidgetMaintenanceContract: $this->getBool($raw, 'enableWidgetMaintenanceContract', true),
            enableWidgetQuickLinks: $this->getBool($raw, 'enableWidgetQuickLinks', true),
            enableWidgetSiteHealth: $this->getBool($raw, 'enableWidgetSiteHealth', true),
            enableWidgetExtensionUpdates: $this->getBool($raw, 'enableWidgetExtensionUpdates', true),
            enableWidgetEditorGuide: $this->getBool($raw, 'enableWidgetEditorGuide', true),
            enableWidgetContentActivity: $this->getBool($raw, 'enableWidgetContentActivity', true),
            enableWidgetRecentChanges: $this->getBool($raw, 'enableWidgetRecentChanges', true),
            enableWidgetStorage: $this->getBool($raw, 'enableWidgetStorage', true),
            activityWeeks: max(1, min(52, $this->getInt($raw, 'activityWeeks', 12))),
            recentChangesCount: max(1, min(25, $this->getInt($raw, 'recentChangesCount', 8))),
            typo3VersionApiUrl: $this->getString($raw, 'typo3VersionApiUrl', 'https://get.typo3.org/api/v1/'),
            typo3NewsFeedUrl: $this->getString($raw, 'typo3NewsFeedUrl', 'https://news.typo3.com/rss'),
            newsCacheTtl: $this->getInt($raw, 'newsCacheTtl', 3600),
            versionCacheTtl: $this->getInt($raw, 'versionCacheTtl', 86400),
            newsItemCount: $this->getInt($raw, 'newsItemCount', 5),
            requestTimeout: $this->getInt($raw, 'requestTimeout', 10),
            agencyName: $this->getString($raw, 'agencyName', 'jweiland.net'),
            agencyEmail: $this->getString($raw, 'agencyEmail', 'projects@jweiland.net'),
            agencyPhone: $this->getString($raw, 'agencyPhone', '+49 711 94969-60'),
            agencyWebsite: $this->getString($raw, 'agencyWebsite', 'https://jweiland.net'),
            welcomeMessage: $this->getString($raw, 'welcomeMessage', ''),
            showAgencyLogo: $this->getBool($raw, 'showAgencyLogo', true),
            customQuickLinks: $this->getString($raw, 'customQuickLinks', ''),
            contractRenewalWarningDays: $this->getInt($raw, 'contractRenewalWarningDays', 30),
            showContractSignupPrompt: $this->getBool($raw, 'showContractSignupPrompt', true),
            contractSignupUrl: $this->getString($raw, 'contractSignupUrl', 'https://jweiland.net/typo3-wartungsvertrag.html'),
        );

        return $this->extConf;
    }

    /**
     * @param array<mixed> $raw
     */
    private function getBool(array $raw, string $key, bool $default): bool
    {
        $value = $raw[$key] ?? $default;

        return is_scalar($value) ? (bool)$value : $default;
    }

    /**
     * @param array<mixed> $raw
     */
    private function getInt(array $raw, string $key, int $default): int
    {
        $value = $raw[$key] ?? $default;

        return is_numeric($value) ? (int)$value : $default;
    }

    /**
     * @param array<mixed> $raw
     */
    private function getString(array $raw, string $key, string $default): string
    {
        $value = $raw[$key] ?? $default;

        return is_scalar($value) ? trim((string)$value) : $default;
    }
}
