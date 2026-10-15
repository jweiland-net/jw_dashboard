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
 * Widgets implementing this interface get a refresh button in the card header.
 * refresh() is called by the AJAX endpoint before the widget gets re-rendered.
 */
interface RefreshableWidgetInterface extends WidgetInterface
{
    /**
     * Discards cached data, so the next getData() call fetches fresh data
     */
    public function refresh(): void;
}
