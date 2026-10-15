<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Widget;

/**
 * Everything the Fluid templates need to render one widget card.
 */
final readonly class WidgetView
{
    public function __construct(
        public string $identifier,
        public string $title,
        public string $iconIdentifier,
        public string $size,
        public string $partial,
        public bool $refreshable,
        public bool $deferred,
        public WidgetData $data,
    ) {}

    /**
     * Deferrable widgets without ready data are rendered as placeholder and loaded via AJAX
     */
    public static function fromWidget(WidgetInterface $widget, bool $allowDeferring = false): self
    {
        $deferred = $allowDeferring && $widget instanceof DeferrableWidgetInterface && !$widget->isReady();

        return new self(
            identifier: $widget->getIdentifier(),
            title: $widget->getTitle(),
            iconIdentifier: $widget->getIconIdentifier(),
            size: $widget->getSize()->value,
            partial: 'Widget/' . ucfirst($widget->getIdentifier()),
            refreshable: $widget instanceof RefreshableWidgetInterface,
            deferred: $deferred,
            data: $deferred ? new WidgetData($widget->getIdentifier(), false, null) : $widget->getSafeData(),
        );
    }
}
