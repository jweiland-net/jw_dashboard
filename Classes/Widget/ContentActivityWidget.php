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
use JWeiland\JwDashboard\Service\ContentActivityService;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Column chart of record changes per week, based on the record history
 */
#[AsTaggedItem(priority: 90)]
class ContentActivityWidget extends AbstractWidget implements RefreshableWidgetInterface
{
    public function __construct(
        private readonly ContentActivityService $contentActivityService,
        private readonly ExtensionConfigurationManager $extConfManager,
    ) {}

    public function getIdentifier(): string
    {
        return 'contentActivity';
    }

    public function getTitle(): string
    {
        return self::LANGUAGE_FILE . 'widget.contentActivity.title';
    }

    public function getIconIdentifier(): string
    {
        return 'actions-history';
    }

    public function getSize(): WidgetSize
    {
        return WidgetSize::Large;
    }

    public function isEnabled(DashboardExtConf $extConf): bool
    {
        return $extConf->enableWidgetContentActivity;
    }

    public function refresh(): void
    {
        // Queried live, nothing to discard
    }

    public function getData(): WidgetData
    {
        $numberOfWeeks = $this->extConfManager->get()->activityWeeks;
        $activity = $this->contentActivityService->getWeeklyActivity($numberOfWeeks, new \DateTimeImmutable());
        $axisMax = self::getNiceAxisMaximum($activity['maxCount']);

        $peakIndex = $activity['maxCount'] > 0
            ? (int)array_search($activity['maxCount'], array_column($activity['weeks'], 'count'), true)
            : -1;

        $bars = [];
        foreach ($activity['weeks'] as $index => $week) {
            $bars[] = array_merge($week, [
                'height' => $axisMax > 0 ? round($week['count'] / $axisMax * 100, 2) : 0,
                'isPeak' => $index === $peakIndex,
                // Label the extremes only: the peak and the current week
                'showLabel' => $week['count'] > 0 && ($index === $peakIndex || $week['isCurrent']),
            ]);
        }

        return new WidgetData(
            widgetIdentifier: $this->getIdentifier(),
            available: true,
            payload: [
                'bars' => $bars,
                'axisMax' => $axisMax,
                'axisMid' => intdiv($axisMax, 2),
                'numberOfWeeks' => $numberOfWeeks,
                'total' => $activity['total'],
                'created' => $activity['created'],
                'updated' => $activity['updated'],
                'deleted' => $activity['deleted'],
                'editors' => $activity['editors'],
                'peak' => $peakIndex >= 0 ? $bars[$peakIndex] : null,
            ],
        );
    }

    /**
     * Rounds up to a clean axis value with an integer mid tick:
     * even numbers up to 10, above 1, 2 or 5 times a power of ten
     */
    public static function getNiceAxisMaximum(int $value): int
    {
        if ($value <= 10) {
            return max(4, $value + $value % 2);
        }

        $magnitude = 10 ** (int)floor(log10($value));
        foreach ([1, 2, 5] as $step) {
            if ($step * $magnitude >= $value) {
                return $step * $magnitude;
            }
        }

        return 10 * $magnitude;
    }
}
