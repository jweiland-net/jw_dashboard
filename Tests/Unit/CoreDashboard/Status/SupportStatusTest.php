<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Tests\Unit\CoreDashboard\Status;

use JWeiland\JwDashboard\CoreDashboard\Status\StatusItem;
use JWeiland\JwDashboard\CoreDashboard\Status\StatusSeverity;
use JWeiland\JwDashboard\CoreDashboard\Status\SupportStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SupportStatusTest extends TestCase
{
    /**
     * @return \Generator<string, array{list<StatusSeverity>, StatusSeverity, int}>
     */
    public static function severityDataProvider(): \Generator
    {
        yield 'no items' => [[], StatusSeverity::Success, 0];
        yield 'info only' => [[StatusSeverity::Info], StatusSeverity::Info, 0];
        yield 'warning and info' => [[StatusSeverity::Info, StatusSeverity::Warning], StatusSeverity::Warning, 1];
        yield 'danger wins' => [[StatusSeverity::Warning, StatusSeverity::Danger, StatusSeverity::Info], StatusSeverity::Danger, 2];
    }

    /**
     * @param list<StatusSeverity> $severities
     */
    #[Test]
    #[DataProvider('severityDataProvider')]
    public function severityIsTheHighestItemSeverity(array $severities, StatusSeverity $expected, int $attentionCount): void
    {
        $subject = new SupportStatus(array_map(
            static fn(StatusSeverity $severity): StatusItem => new StatusItem('test', $severity, 'actions-info', 'title'),
            $severities,
        ));

        self::assertSame($expected, $subject->getSeverity());
        self::assertSame($expected->value, $subject->getState());
        self::assertSame($attentionCount, $subject->getAttentionCount());
    }

    #[Test]
    public function statusWithFactsOnlyIsAllGood(): void
    {
        $subject = new SupportStatus([], [['key' => 'k', 'arguments' => []]]);

        self::assertTrue($subject->isAllGood());
        self::assertFalse($subject->isEmpty());
    }

    #[Test]
    public function statusWithoutItemsAndFactsIsEmpty(): void
    {
        $subject = new SupportStatus();

        self::assertTrue($subject->isEmpty());
        self::assertFalse($subject->isAllGood());
    }

    #[Test]
    public function statusItemWithoutTargetHasNoAction(): void
    {
        self::assertFalse((new StatusItem('a', StatusSeverity::Info, 'i', 't', actionLabel: 'label'))->hasAction());
        self::assertFalse((new StatusItem('a', StatusSeverity::Info, 'i', 't', actionUrl: 'https://example.org'))->hasAction());
        self::assertTrue((new StatusItem('a', StatusSeverity::Info, 'i', 't', actionUrl: 'https://example.org', actionLabel: 'label'))->hasAction());
        self::assertTrue((new StatusItem('a', StatusSeverity::Info, 'i', 't', actionLabel: 'label', actionModule: 'jw_dashboard'))->hasAction());
    }
}
