<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Tests\Unit\Domain\Model;

use JWeiland\JwDashboard\Domain\Model\SupportLifecycle;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SupportLifecycleTest extends TestCase
{
    private const API_RESPONSE = [
        'release_date' => '2026-04-21T00:00:00+02:00',
        'maintained_until' => '2029-06-30T00:00:00+02:00',
        'elts_until' => '2032-06-30T00:00:00+02:00',
    ];

    #[Test]
    public function fromArrayReturnsNullForIncompleteData(): void
    {
        self::assertNull(SupportLifecycle::fromArray([]));
        self::assertNull(SupportLifecycle::fromArray(['release_date' => '2026-04-21']));
    }

    #[Test]
    public function fromArrayUsesEndOfMaintenanceIfEltsIsMissing(): void
    {
        $subject = SupportLifecycle::fromArray(['release_date' => '2026-04-21', 'maintained_until' => '2029-06-30']);

        self::assertNotNull($subject);
        self::assertSame($subject->maintainedUntil, $subject->eltsUntil);
        self::assertFalse($subject->toArray(new \DateTimeImmutable('2027-01-01'))['hasElts']);
    }

    #[Test]
    public function regularPhaseCountsDownToEndOfMaintenance(): void
    {
        $subject = $this->createSubject();
        $data = $subject->toArray(new \DateTimeImmutable('2026-10-16T12:00:00+02:00'));

        self::assertSame(SupportLifecycle::PHASE_REGULAR, $data['phase']);
        self::assertSame(2, $data['yearsLeft']);
        self::assertSame(8, $data['monthsLeft']);
        self::assertGreaterThan(0, $data['todayPosition']);
        self::assertLessThan($data['regularShare'], $data['todayPosition']);
        self::assertTrue($data['hasElts']);
    }

    #[Test]
    public function eltsPhaseCountsDownToEndOfElts(): void
    {
        $subject = $this->createSubject();
        $today = new \DateTimeImmutable('2031-06-30T00:00:00+02:00');

        self::assertSame(SupportLifecycle::PHASE_ELTS, $subject->getPhase($today));
        self::assertSame(366, $subject->getDaysLeftInPhase($today));
    }

    #[Test]
    public function unsupportedPhaseHasNoDaysLeftAndFullPosition(): void
    {
        $subject = $this->createSubject();
        $today = new \DateTimeImmutable('2033-01-01');

        self::assertSame(SupportLifecycle::PHASE_UNSUPPORTED, $subject->getPhase($today));
        self::assertSame(0, $subject->getDaysLeftInPhase($today));
        self::assertSame(100.0, $subject->getPosition($today->getTimestamp()));
    }

    private function createSubject(): SupportLifecycle
    {
        $subject = SupportLifecycle::fromArray(self::API_RESPONSE);
        self::assertNotNull($subject);

        return $subject;
    }
}
