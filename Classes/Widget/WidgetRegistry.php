<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Widget;

use JWeiland\JwDashboard\Configuration\ExtensionConfigurationManager;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Collects all tagged widgets (ordered by priority) and filters them by the feature flags
 * of the extension configuration.
 */
final readonly class WidgetRegistry
{
    /**
     * @param iterable<WidgetInterface> $widgets
     */
    public function __construct(
        #[AutowireIterator(WidgetInterface::TAG_NAME)] private iterable $widgets,
        private ExtensionConfigurationManager $extConfManager,
    ) {}

    /**
     * @return list<WidgetInterface>
     */
    public function getEnabledWidgets(): array
    {
        $extConf = $this->extConfManager->get();
        $enabledWidgets = [];
        foreach ($this->widgets as $widget) {
            if ($widget->isEnabled($extConf)) {
                $enabledWidgets[] = $widget;
            }
        }

        return $enabledWidgets;
    }

    public function findEnabledWidget(string $identifier): ?WidgetInterface
    {
        foreach ($this->getEnabledWidgets() as $widget) {
            if ($widget->getIdentifier() === $identifier) {
                return $widget;
            }
        }

        return null;
    }
}
