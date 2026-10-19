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
use JWeiland\JwDashboard\Service\ExtensionUpdateService;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 40)]
class ExtensionUpdatesWidget extends AbstractWidget
{
    public function __construct(
        private readonly ExtensionUpdateService $extensionUpdateService,
    ) {}

    public function getIdentifier(): string
    {
        return 'extensionUpdates';
    }

    public function getTitle(): string
    {
        return self::LANGUAGE_FILE . 'widget.extensionUpdates.title';
    }

    public function getIconIdentifier(): string
    {
        return 'actions-extension';
    }

    public function getSize(): WidgetSize
    {
        return WidgetSize::Full;
    }

    public function isEnabled(DashboardExtConf $extConf): bool
    {
        return $extConf->enableWidgetExtensionUpdates;
    }

    public function getData(): WidgetData
    {
        $extensions = $this->extensionUpdateService->getThirdPartyExtensions();

        return new WidgetData(
            widgetIdentifier: $this->getIdentifier(),
            available: true,
            payload: [
                'extensions' => $extensions,
                'count' => count($extensions),
            ],
        );
    }
}
