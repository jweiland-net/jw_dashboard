<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Configuration;

/**
 * Immutable value object holding all resolved extension configuration values.
 * Populated by ExtensionConfigurationManager from the TYPO3 extension settings.
 */
final readonly class DashboardExtConf
{
    public function __construct(
        // Widget visibility
        public bool $enableWidgetTypo3Version,
        public bool $enableWidgetTypo3News,
        public bool $enableWidgetMaintenanceContract,
        public bool $enableWidgetQuickLinks,
        public bool $enableWidgetSiteHealth,
        public bool $enableWidgetExtensionUpdates,
        public bool $enableWidgetEditorGuide,
        public bool $enableWidgetContentActivity,
        public bool $enableWidgetRecentChanges,
        public bool $enableWidgetStorage,
        public int $activityWeeks,
        public int $recentChangesCount,

        // API / data sources
        public string $typo3VersionApiUrl,
        public string $typo3NewsFeedUrl,
        public int $newsCacheTtl,
        public int $versionCacheTtl,
        public int $newsItemCount,
        public int $requestTimeout,

        // Branding
        public string $agencyName,
        public string $agencyEmail,
        public string $agencyPhone,
        public string $agencyWebsite,
        public string $welcomeMessage,
        public bool $showAgencyLogo,

        // Quick links (raw multi-line string: "label|url|icon" per line)
        public string $customQuickLinks,

        // Maintenance contract
        public int $contractRenewalWarningDays,
        public bool $showContractSignupPrompt,
        public string $contractSignupUrl,
    ) {}

    /**
     * Returns the parsed quick-links as an array of associative arrays.
     *
     * @return array<int, array{label: string, url: string, icon: string}>
     */
    public function getParsedQuickLinks(): array
    {
        $links = [];
        foreach (explode("\n", $this->customQuickLinks) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_pad(explode('|', $line, 3), 3, '');
            $links[] = [
                'label' => trim($parts[0]),
                'url' => trim($parts[1]),
                'icon' => trim($parts[2]) ?: 'actions-link',
            ];
        }

        return $links;
    }
}
