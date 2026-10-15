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
 * Immutable value object returned by every widget's getData() call.
 * Widgets may carry arbitrary $payload; the template is responsible for
 * casting it appropriately.
 */
final readonly class WidgetData
{
    public function __construct(
        public string $widgetIdentifier,
        public bool $available,
        public mixed $payload,
        public string $errorMessage = '',
    ) {}

    public static function unavailable(string $widgetIdentifier, string $reason = ''): self
    {
        return new self(
            widgetIdentifier: $widgetIdentifier,
            available: false,
            payload: null,
            errorMessage: $reason,
        );
    }
}
