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
use JWeiland\JwDashboard\Service\SiteHealthService;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 95)]
class SiteHealthWidget extends AbstractWidget implements RefreshableWidgetInterface
{
    public function __construct(
        private readonly SiteHealthService $siteHealthService,
    ) {}

    public function getIdentifier(): string
    {
        return 'siteHealth';
    }

    public function getTitle(): string
    {
        return self::LANGUAGE_FILE . 'widget.siteHealth.title';
    }

    public function getIconIdentifier(): string
    {
        return 'actions-heart';
    }

    public function getSize(): WidgetSize
    {
        return WidgetSize::Large;
    }

    public function isEnabled(DashboardExtConf $extConf): bool
    {
        return $extConf->enableWidgetSiteHealth;
    }

    public function refresh(): void
    {
        // Metrics are queried live, nothing to discard
    }

    public function getData(): WidgetData
    {
        $metrics = $this->siteHealthService->collectMetrics();

        return new WidgetData(
            widgetIdentifier: $this->getIdentifier(),
            available: true,
            payload: array_merge($metrics, [
                'linkvalidatorInstalled' => $metrics['brokenLinksCount'] !== null,
                'schedulerInstalled' => $metrics['schedulerTaskCount'] !== null,
            ]),
        );
    }
}
