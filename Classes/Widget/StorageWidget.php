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
use JWeiland\JwDashboard\Service\StorageService;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Part-to-whole view of the FAL storage by file type
 */
#[AsTaggedItem(priority: 65)]
class StorageWidget extends AbstractWidget
{
    public function __construct(
        private readonly StorageService $storageService,
    ) {}

    public function getIdentifier(): string
    {
        return 'storage';
    }

    public function getTitle(): string
    {
        return self::LANGUAGE_FILE . 'widget.storage.title';
    }

    public function getIconIdentifier(): string
    {
        return 'apps-filetree-mount';
    }

    public function getSize(): WidgetSize
    {
        return WidgetSize::Small;
    }

    public function isEnabled(DashboardExtConf $extConf): bool
    {
        return $extConf->enableWidgetStorage;
    }

    public function getData(): WidgetData
    {
        $statistics = $this->storageService->getStatistics();

        return new WidgetData(
            widgetIdentifier: $this->getIdentifier(),
            available: true,
            payload: array_merge($statistics, [
                // Categories without files are not drawn, but keep their color slot (fixed order)
                'segments' => array_map(
                    static fn(array $category): array => array_merge($category, [
                        // Tiny but non-empty shares must not read as "0 %"
                        'isTiny' => $category['size'] > 0 && $category['share'] < 0.1,
                    ]),
                    array_values(array_filter(
                        $statistics['categories'],
                        static fn(array $category): bool => $category['size'] > 0 || $category['count'] > 0,
                    )),
                ),
            ]),
        );
    }
}
