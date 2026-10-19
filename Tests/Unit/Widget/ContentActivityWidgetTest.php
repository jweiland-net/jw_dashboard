<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Tests\Unit\Widget;

use JWeiland\JwDashboard\Configuration\ExtensionConfigurationManager;
use JWeiland\JwDashboard\Service\ContentActivityService;
use JWeiland\JwDashboard\Widget\ContentActivityWidget;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final class ContentActivityWidgetTest extends TestCase
{
    /**
     * @return \Generator<string, array{int, int}>
     */
    public static function niceAxisMaximumDataProvider(): \Generator
    {
        yield 'zero' => [0, 4];
        yield 'small odd value' => [5, 6];
        yield 'ten' => [10, 10];
        yield 'eleven' => [11, 20];
        yield 'below fifty' => [42, 50];
        yield 'hundred' => [100, 100];
        yield 'above hundred' => [186, 200];
        yield 'above two hundred' => [201, 500];
        yield 'above five hundred' => [501, 1000];
    }

    #[Test]
    #[DataProvider('niceAxisMaximumDataProvider')]
    public function getNiceAxisMaximumReturnsCleanValueWithIntegerMidTick(int $value, int $expected): void
    {
        $axisMaximum = ContentActivityWidget::getNiceAxisMaximum($value);

        self::assertSame($expected, $axisMaximum);
        self::assertSame(0, $axisMaximum % 2);
    }

    #[Test]
    public function getDataLabelsOnlyPeakAndCurrentWeek(): void
    {
        $weeks = [];
        foreach ([3, 9, 0, 4] as $index => $count) {
            $weeks[] = ['start' => $index, 'end' => $index, 'count' => $count, 'isCurrent' => $index === 3];
        }

        $service = $this->createMock(ContentActivityService::class);
        $service->method('getWeeklyActivity')->willReturn([
            'weeks' => $weeks,
            'total' => 16,
            'created' => 6,
            'updated' => 10,
            'deleted' => 0,
            'editors' => 2,
            'maxCount' => 9,
        ]);

        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn(['activityWeeks' => '4']);

        $subject = new ContentActivityWidget($service, new ExtensionConfigurationManager($extensionConfiguration));
        /** @var array{axisMax: int, bars: list<array{showLabel: bool, height: float}>, peak: array{count: int}} $payload */
        $payload = $subject->getData()->payload;

        self::assertSame(10, $payload['axisMax']);
        self::assertSame([false, true, false, true], array_column($payload['bars'], 'showLabel'));
        self::assertSame(90.0, $payload['bars'][1]['height']);
        self::assertSame(9, $payload['peak']['count']);
    }
}
