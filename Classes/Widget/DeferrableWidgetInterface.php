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
 * Widgets with slow data sources (e.g. external APIs). If isReady() returns false, the dashboard
 * renders a placeholder and loads the card body asynchronously, so the module never waits for them.
 */
interface DeferrableWidgetInterface extends WidgetInterface
{
    /**
     * True, if getData() can be answered without slow I/O (e.g. cache hit)
     */
    public function isReady(): bool;
}
