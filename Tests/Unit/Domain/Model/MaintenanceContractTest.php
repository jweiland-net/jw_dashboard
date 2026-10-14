<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Tests\Unit\Domain\Model;

use JWeiland\JwDashboard\Domain\Model\ContractRenewalState;
use JWeiland\JwDashboard\Domain\Model\MaintenanceContract;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MaintenanceContractTest extends TestCase
{
    private const TODAY = '2026-10-16 14:30:00';

    #[Test]
    public function getDaysRemainingReturnsNullWithoutEndDate(): void
    {
        self::assertNull((new MaintenanceContract())->getDaysRemaining(new \DateTimeImmutable(self::TODAY)));
    }

    #[Test]
    public function getDaysRemainingIgnoresTimeOfDay(): void
    {
        $subject = new MaintenanceContract();
        $subject->setEndDate(new \DateTimeImmutable('2026-10-17 00:00:00'));

        self::assertSame(1, $subject->getDaysRemaining(new \DateTimeImmutable(self::TODAY)));
    }

    #[Test]
    public function getDaysRemainingIsNegativeForExpiredContracts(): void
    {
        $subject = new MaintenanceContract();
        $subject->setEndDate(new \DateTimeImmutable('2026-10-06'));

        self::assertSame(-10, $subject->getDaysRemaining(new \DateTimeImmutable(self::TODAY)));
    }

    /**
     * @return \Generator<string, array{?string, int, int, ContractRenewalState}>
     */
    public static function renewalStateDataProvider(): \Generator
    {
        yield 'no end date' => [null, 30, 30, ContractRenewalState::Unlimited];
        yield 'expired yesterday' => ['2026-10-15', 30, 30, ContractRenewalState::Expired];
        yield 'ends today' => ['2026-10-16', 30, 30, ContractRenewalState::Expiring];
        yield 'within record warning period' => ['2026-11-15', 30, 7, ContractRenewalState::Expiring];
        yield 'outside record warning period' => ['2026-11-16', 30, 7, ContractRenewalState::Active];
        yield 'falls back to default warning period' => ['2026-10-20', 0, 7, ContractRenewalState::Expiring];
        yield 'outside default warning period' => ['2026-10-30', 0, 7, ContractRenewalState::Active];
    }

    #[Test]
    #[DataProvider('renewalStateDataProvider')]
    public function getRenewalStateRespectsWarningPeriods(
        ?string $endDate,
        int $recordWarningDays,
        int $defaultWarningDays,
        ContractRenewalState $expectedState,
    ): void {
        $subject = new MaintenanceContract();
        $subject->setEndDate($endDate === null ? null : new \DateTimeImmutable($endDate));
        $subject->setRenewalPromptDays($recordWarningDays);

        self::assertSame(
            $expectedState,
            $subject->getRenewalState(new \DateTimeImmutable(self::TODAY), $defaultWarningDays),
        );
    }

    #[Test]
    public function getTermProgressReturnsNullWithoutCompleteTerm(): void
    {
        $subject = new MaintenanceContract();
        self::assertNull($subject->getTermProgress(new \DateTimeImmutable(self::TODAY)));

        $subject->setEndDate(new \DateTimeImmutable('2027-01-01'));
        self::assertNull($subject->getTermProgress(new \DateTimeImmutable(self::TODAY)));
    }

    #[Test]
    public function getTermProgressIsClampedBetweenZeroAndHundred(): void
    {
        $subject = new MaintenanceContract();
        $subject->setStartDate(new \DateTimeImmutable('2026-01-01'));
        $subject->setEndDate(new \DateTimeImmutable('2026-12-31'));

        self::assertSame(0.0, $subject->getTermProgress(new \DateTimeImmutable('2025-06-01')));
        self::assertSame(100.0, $subject->getTermProgress(new \DateTimeImmutable('2027-06-01')));
        self::assertEqualsWithDelta(50.0, $subject->getTermProgress(new \DateTimeImmutable('2026-07-02')), 0.5);
    }

    #[Test]
    public function contactPersonsAreInitialized(): void
    {
        self::assertCount(0, (new MaintenanceContract())->getContactPersons());
    }
}
