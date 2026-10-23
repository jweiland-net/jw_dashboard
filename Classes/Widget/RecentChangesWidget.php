<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Widget;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use JWeiland\JwDashboard\Configuration\DashboardExtConf;
use JWeiland\JwDashboard\Configuration\ExtensionConfigurationManager;
use JWeiland\JwDashboard\Service\RecentChangesService;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Timeline of the latest edited pages and content elements with direct edit links
 */
#[AsTaggedItem(priority: 85)]
class RecentChangesWidget extends AbstractWidget implements RefreshableWidgetInterface
{
    public function __construct(
        private readonly RecentChangesService $recentChangesService,
        private readonly ExtensionConfigurationManager $extConfManager,
        private readonly UriBuilder $uriBuilder,
    ) {}

    public function getIdentifier(): string
    {
        return 'recentChanges';
    }

    public function getTitle(): string
    {
        return self::LANGUAGE_FILE . 'widget.recentChanges.title';
    }

    public function getIconIdentifier(): string
    {
        return 'actions-document-edit';
    }

    public function getSize(): WidgetSize
    {
        return WidgetSize::Small;
    }

    public function isEnabled(DashboardExtConf $extConf): bool
    {
        return $extConf->enableWidgetRecentChanges;
    }

    public function refresh(): void
    {
        // Queried live, nothing to discard
    }

    public function getData(): WidgetData
    {
        $backendUser = $this->getBackendUser();
        if (!$backendUser instanceof BackendUserAuthentication) {
            return WidgetData::unavailable($this->getIdentifier());
        }

        $now = time();
        $ageLabels = $this->getLanguageService()?->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears') ?? '';
        $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('jw_dashboard');

        $items = [];
        foreach ($this->recentChangesService->getRecentChanges($this->extConfManager->get()->recentChangesCount, $backendUser) as $change) {
            $items[] = array_merge($change, [
                'age' => BackendUtility::calcAge(max(60, $now - $change['tstamp']), $ageLabels),
                'editUrl' => (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                    'edit' => [$change['table'] => [$change['uid'] => 'edit']],
                    'returnUrl' => $returnUrl,
                ]),
                'pageUrl' => (string)$this->uriBuilder->buildUriFromRoute('web_layout', ['id' => $change['pageId']]),
            ]);
        }

        return new WidgetData(
            widgetIdentifier: $this->getIdentifier(),
            available: true,
            payload: ['items' => $items],
        );
    }
}
