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
use JWeiland\JwDashboard\Domain\Model\SupportLifecycle;
use JWeiland\JwDashboard\Service\Typo3VersionService;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 100)]
class Typo3VersionWidget extends AbstractWidget implements RefreshableWidgetInterface, DeferrableWidgetInterface
{
    public function __construct(
        private readonly Typo3VersionService $versionService,
        private readonly ExtensionConfigurationManager $extConfManager,
    ) {}

    public function getIdentifier(): string
    {
        return 'typo3Version';
    }

    public function getTitle(): string
    {
        return self::LANGUAGE_FILE . 'widget.typo3Version.title';
    }

    public function getIconIdentifier(): string
    {
        return 'information-typo3-version';
    }

    public function getSize(): WidgetSize
    {
        return WidgetSize::Small;
    }

    public function isEnabled(DashboardExtConf $extConf): bool
    {
        return $extConf->enableWidgetTypo3Version;
    }

    public function isReady(): bool
    {
        return $this->versionService->hasCachedData($this->extConfManager->get()->typo3VersionApiUrl);
    }

    public function refresh(): void
    {
        $this->versionService->flushCache();
    }

    public function getData(): WidgetData
    {
        $extConf = $this->extConfManager->get();
        $payload = $this->versionService->getVersionData(
            $extConf->typo3VersionApiUrl,
            $extConf->versionCacheTtl,
            $extConf->requestTimeout,
        );

        $lifecycle = $payload['lifecycle'] ?? null;

        return new WidgetData(
            widgetIdentifier: $this->getIdentifier(),
            available: true,
            payload: array_merge($payload, [
                'majorVersion' => (int)$payload['installed'],
                'lifecycle' => $lifecycle instanceof SupportLifecycle ? $lifecycle->toArray(new \DateTimeImmutable()) : null,
            ]),
        );
    }
}
