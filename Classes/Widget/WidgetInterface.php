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
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Contract for all dashboard widgets. Every implementation is tagged automatically
 * and collected by the WidgetRegistry. Use #[AsTaggedItem(priority: n)] to control the order.
 */
#[AutoconfigureTag(WidgetInterface::TAG_NAME)]
interface WidgetInterface
{
    public const TAG_NAME = 'jw_dashboard.widget';

    /**
     * Unique identifier, also used to resolve the partial "Widget/<UpperCamelCase identifier>"
     */
    public function getIdentifier(): string;

    /**
     * LLL reference or plain label for the card header
     */
    public function getTitle(): string;

    public function getIconIdentifier(): string;

    public function getSize(): WidgetSize;

    public function isEnabled(DashboardExtConf $extConf): bool;

    public function getData(): WidgetData;

    /**
     * Same as getData(), but never throws
     */
    public function getSafeData(): WidgetData;
}
