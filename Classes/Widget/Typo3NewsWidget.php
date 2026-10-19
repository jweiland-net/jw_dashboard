<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Widget;

use JWeiland\JwDashboard\Configuration\DashboardExtConf;
use JWeiland\JwDashboard\Configuration\ExtensionConfigurationManager;
use JWeiland\JwDashboard\Service\NewsService;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 60)]
class Typo3NewsWidget extends AbstractWidget implements RefreshableWidgetInterface, DeferrableWidgetInterface
{
    public function __construct(
        private readonly NewsService $newsService,
        private readonly ExtensionConfigurationManager $extConfManager,
    ) {}

    public function getIdentifier(): string
    {
        return 'typo3News';
    }

    public function getTitle(): string
    {
        return self::LANGUAGE_FILE . 'widget.typo3News.title';
    }

    public function getIconIdentifier(): string
    {
        return 'actions-newspaper';
    }

    public function getSize(): WidgetSize
    {
        return WidgetSize::Large;
    }

    public function isEnabled(DashboardExtConf $extConf): bool
    {
        return $extConf->enableWidgetTypo3News;
    }

    public function isReady(): bool
    {
        $extConf = $this->extConfManager->get();

        return $this->newsService->hasCachedData($extConf->typo3NewsFeedUrl, $extConf->newsItemCount);
    }

    public function refresh(): void
    {
        $this->newsService->flushCache();
    }

    public function getData(): WidgetData
    {
        $extConf = $this->extConfManager->get();
        $items = $this->newsService->getLatestNews(
            $extConf->typo3NewsFeedUrl,
            $extConf->newsItemCount,
            $extConf->newsCacheTtl,
            $extConf->requestTimeout,
        );

        return new WidgetData(
            widgetIdentifier: $this->getIdentifier(),
            available: $items !== [],
            payload: [
                'items' => $items,
                'feedUrl' => $extConf->typo3NewsFeedUrl,
            ],
        );
    }
}
