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
 * Column span of a widget card within the 12 column dashboard grid
 */
enum WidgetSize: string
{
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';
    case Full = 'full';
}
