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
use JWeiland\JwDashboard\CoreDashboard\Status\SupportStatusBuilder;
use JWeiland\JwDashboard\Domain\Model\ContractRenewalState;
use JWeiland\JwDashboard\Domain\Model\MaintenanceContract;
use JWeiland\JwDashboard\Domain\Model\SupportLifecycle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SupportStatusBuilderTest extends TestCase
{
    private const PREFIX = SupportStatusBuilder::LABEL_PREFIX;

    private SupportStatusBuilder $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new SupportStatusBuilder();
    }

    #[Test]
    public function buildReturnsEmptyStatusWithoutPayloads(): void
    {
        $status = $this->subject->build([], []);

        self::assertTrue($status->isEmpty());
        self::assertFalse($status->isAllGood());
        self::assertSame(StatusSeverity::Success, $status->getSeverity());
    }

    /**
     * @return \Generator<string, array{array<string, mixed>, StatusSeverity, string, string}>
     */
    public static function versionItemDataProvider(): \Generator
    {
        yield 'check failed' => [
            self::version(['checkFailed' => true, 'latest' => '']),
            StatusSeverity::Info,
            'actions-info-circle',
            'widget.typo3Version.checkFailed',
        ];
        yield 'update available' => [
            self::version(['isUpToDate' => false]),
            StatusSeverity::Warning,
            'actions-exclamation-triangle',
            self::PREFIX . 'update',
        ];
        yield 'security update' => [
            self::version(['isUpToDate' => false, 'isSecurityRelease' => true]),
            StatusSeverity::Danger,
            'actions-shield',
            self::PREFIX . 'security',
        ];
        yield 'unsupported major version wins over up to date' => [
            self::version(['lifecycle' => ['phase' => SupportLifecycle::PHASE_UNSUPPORTED]]),
            StatusSeverity::Danger,
            'actions-exclamation-circle',
            self::PREFIX . 'unsupported',
        ];
    }

    /**
     * @param array<string, mixed> $version
     */
    #[Test]
    #[DataProvider('versionItemDataProvider')]
    public function buildCreatesVersionItem(array $version, StatusSeverity $severity, string $icon, string $title): void
    {
        $status = $this->subject->build($version, []);

        self::assertCount(1, $status->items);
        $item = $status->items[0];
        self::assertSame('typo3Version', $item->identifier);
        self::assertSame($severity, $item->severity);
        self::assertSame($icon, $item->icon);
        self::assertSame($title, $item->title);
        self::assertSame([], $status->facts);
    }

    #[Test]
    public function updateItemContainsInstalledAndLatestVersionAndReleaseLink(): void
    {
        $item = $this->subject->build(self::version(['isUpToDate' => false]), [])->items[0];

        self::assertSame(self::PREFIX . 'update.body', $item->body);
        self::assertSame(['14.3.9', '14.3.10'], $item->bodyArguments);
        self::assertSame('https://get.typo3.org/version/14', $item->actionUrl);
        self::assertSame(self::PREFIX . 'releaseInfo', $item->actionLabel);
        self::assertTrue($item->actionExternal);
        self::assertTrue($item->hasAction());
    }

    #[Test]
    public function securityItemUsesLatestVersionAsBodyArgument(): void
    {
        $item = $this->subject->build(self::version(['isUpToDate' => false, 'isSecurityRelease' => true]), [])->items[0];

        self::assertSame('widget.typo3Version.securityUpdate.body', $item->body);
        self::assertSame(['14.3.10'], $item->bodyArguments);
    }

    #[Test]
    public function unsupportedItemUsesMajorVersionAsTitleArgument(): void
    {
        $item = $this->subject->build(
            self::version(['majorVersion' => 11, 'lifecycle' => ['phase' => SupportLifecycle::PHASE_UNSUPPORTED]]),
            [],
        )->items[0];

        self::assertSame([11], $item->titleArguments);
        self::assertSame(self::PREFIX . 'unsupported.body', $item->body);
    }

    #[Test]
    public function checkFailedItemHasNoAction(): void
    {
        $item = $this->subject->build(self::version(['checkFailed' => true]), [])->items[0];

        self::assertFalse($item->hasAction());
    }

    #[Test]
    public function upToDateVersionCreatesFactsInsteadOfItem(): void
    {
        $maintainedUntil = (new \DateTimeImmutable('2029-04-30 12:00:00'))->getTimestamp();
        $status = $this->subject->build(
            self::version(['lifecycle' => ['phase' => SupportLifecycle::PHASE_REGULAR, 'maintainedUntil' => $maintainedUntil]]),
            [],
        );

        self::assertTrue($status->isAllGood());
        self::assertSame(
            [
                ['key' => self::PREFIX . 'ok.version', 'arguments' => ['14.3.9']],
                ['key' => self::PREFIX . 'ok.supportUntil', 'arguments' => ['30.04.2029']],
            ],
            $status->facts,
        );
    }

    #[Test]
    public function upToDateVersionInEltsPhaseShowsEltsDate(): void
    {
        $eltsUntil = (new \DateTimeImmutable('2031-04-30 12:00:00'))->getTimestamp();
        $status = $this->subject->build(
            self::version(['lifecycle' => ['phase' => SupportLifecycle::PHASE_ELTS, 'eltsUntil' => $eltsUntil]]),
            [],
        );

        self::assertSame(['key' => self::PREFIX . 'ok.eltsUntil', 'arguments' => ['30.04.2031']], $status->facts[1]);
    }

    #[Test]
    public function contractsThatNeedAttentionCreateWarningItemWithDetails(): void
    {
        $status = $this->subject->build([], self::contracts([
            self::contract('Hosting', ContractRenewalState::Expiring, 12),
            self::contract('Support', ContractRenewalState::Expired, -3),
            self::contract('Maintenance', ContractRenewalState::Active, 200),
        ]));

        self::assertCount(1, $status->items);
        $item = $status->items[0];
        self::assertSame('maintenanceContract', $item->identifier);
        self::assertSame(StatusSeverity::Warning, $item->severity);
        self::assertSame(self::PREFIX . 'contracts.many', $item->title);
        self::assertSame([2], $item->titleArguments);
        self::assertSame(
            [
                ['key' => self::PREFIX . 'contract.endsIn', 'arguments' => ['Hosting', 12]],
                ['key' => self::PREFIX . 'contract.expired', 'arguments' => ['Support']],
            ],
            $item->details,
        );
        self::assertSame(1, $status->getAttentionCount());
    }

    #[Test]
    public function singleContractUsesSingularTitle(): void
    {
        $item = $this->subject->build([], self::contracts([
            self::contract('Hosting', ContractRenewalState::Expiring, 0),
        ]))->items[0];

        self::assertSame(self::PREFIX . 'contracts.one', $item->title);
        self::assertSame([['key' => self::PREFIX . 'contract.endsToday', 'arguments' => ['Hosting']]], $item->details);
    }

    #[Test]
    public function contractEndingTomorrowUsesOwnLabel(): void
    {
        $item = $this->subject->build([], self::contracts([
            self::contract('Hosting', ContractRenewalState::Expiring, 1),
        ]))->items[0];

        self::assertSame([['key' => self::PREFIX . 'contract.endsTomorrow', 'arguments' => ['Hosting']]], $item->details);
    }

    #[Test]
    public function contractDetailsAreLimitedAndSummarizeTheRest(): void
    {
        $entries = [];
        for ($i = 1; $i <= SupportStatusBuilder::MAX_CONTRACT_DETAILS + 2; $i++) {
            $entries[] = self::contract('Contract ' . $i, ContractRenewalState::Expired, -$i);
        }

        $details = $this->subject->build([], self::contracts($entries))->items[0]->details;

        self::assertCount(SupportStatusBuilder::MAX_CONTRACT_DETAILS + 1, $details);
        self::assertSame(['key' => self::PREFIX . 'contract.more', 'arguments' => [2]], end($details));
    }

    #[Test]
    public function contractActionLinksToRecordListForEditors(): void
    {
        $item = $this->subject->build(
            [],
            self::contracts([self::contract('Hosting', ContractRenewalState::Expiring, 5)], ['manageUrl' => '/typo3/module/records']),
            'jw_dashboard',
        )->items[0];

        self::assertSame('/typo3/module/records', $item->actionUrl);
        self::assertSame('', $item->actionModule);
        self::assertFalse($item->actionExternal);
        self::assertSame(self::PREFIX . 'review', $item->actionLabel);
    }

    #[Test]
    public function contractActionFallsBackToModuleWithoutManageUrl(): void
    {
        $item = $this->subject->build(
            [],
            self::contracts([self::contract('Hosting', ContractRenewalState::Expiring, 5)]),
            'jw_dashboard',
        )->items[0];

        self::assertSame('', $item->actionUrl);
        self::assertSame('jw_dashboard', $item->actionModule);
        self::assertTrue($item->hasAction());
    }

    #[Test]
    public function contractItemHasNoActionWithoutManageUrlAndModuleAccess(): void
    {
        $item = $this->subject->build([], self::contracts([self::contract('Hosting', ContractRenewalState::Expiring, 5)]))->items[0];

        self::assertFalse($item->hasAction());
    }

    #[Test]
    public function missingContractsShowSignupPrompt(): void
    {
        $item = $this->subject->build([], self::contracts([], [
            'showSignupPrompt' => true,
            'signupUrl' => 'https://jweiland.net/typo3-wartungsvertrag.html',
        ]))->items[0];

        self::assertSame(StatusSeverity::Info, $item->severity);
        self::assertSame('widget.maintenanceContract.noContract', $item->title);
        self::assertSame('https://jweiland.net/typo3-wartungsvertrag.html', $item->actionUrl);
        self::assertTrue($item->actionExternal);
    }

    #[Test]
    public function activeContractsCreateFacts(): void
    {
        $status = $this->subject->build([], self::contracts([
            self::contract('Hosting', ContractRenewalState::Active, 100),
            self::contract('Support', ContractRenewalState::Unlimited, null),
        ]));

        self::assertTrue($status->isAllGood());
        self::assertSame([['key' => self::PREFIX . 'ok.contracts.many', 'arguments' => [2]]], $status->facts);
    }

    #[Test]
    public function singleActiveContractUsesSingularFact(): void
    {
        $status = $this->subject->build([], self::contracts([self::contract('Hosting', ContractRenewalState::Active, 100)]));

        self::assertSame([['key' => self::PREFIX . 'ok.contracts.one', 'arguments' => [1]]], $status->facts);
    }

    #[Test]
    public function noContractsAndNoSignupPromptCreatesNothing(): void
    {
        self::assertTrue($this->subject->build([], self::contracts([]))->isEmpty());
    }

    #[Test]
    public function factsAreDroppedAsSoonAsOneItemNeedsAttention(): void
    {
        $status = $this->subject->build(
            self::version(),
            self::contracts([self::contract('Hosting', ContractRenewalState::Expiring, 5)]),
        );

        self::assertCount(1, $status->items);
        self::assertSame([], $status->facts);
        self::assertFalse($status->isAllGood());
    }

    #[Test]
    public function itemsAreOrderedVersionFirst(): void
    {
        $status = $this->subject->build(
            self::version(['isUpToDate' => false, 'isSecurityRelease' => true]),
            self::contracts([self::contract('Hosting', ContractRenewalState::Expiring, 5)]),
        );

        self::assertSame(
            ['typo3Version', 'maintenanceContract'],
            array_map(static fn(StatusItem $item): string => $item->identifier, $status->items),
        );
        self::assertSame(StatusSeverity::Danger, $status->getSeverity());
        self::assertSame(2, $status->getAttentionCount());
    }

    /**
     * Payload as returned by Typo3VersionWidget, up to date by default
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private static function version(array $overrides = []): array
    {
        return array_replace([
            'installed' => '14.3.9',
            'latest' => '14.3.10',
            'checkFailed' => false,
            'isUpToDate' => true,
            'isSecurityRelease' => false,
            'releaseDate' => 0,
            'releaseUrl' => 'https://get.typo3.org/version/14',
            'majorVersion' => 14,
            'lifecycle' => null,
        ], $overrides);
    }

    /**
     * Payload as returned by MaintenanceContractWidget
     *
     * @param list<array<string, mixed>> $contracts
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private static function contracts(array $contracts, array $overrides = []): array
    {
        return array_replace([
            'contracts' => $contracts,
            'expiringCount' => 0,
            'showSignupPrompt' => false,
            'signupUrl' => '',
            'manageUrl' => '',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private static function contract(string $title, ContractRenewalState $state, ?int $daysRemaining): array
    {
        $contract = new MaintenanceContract();
        $contract->setTitle($title);

        return [
            'contract' => $contract,
            'state' => $state->value,
            'severity' => $state->getSeverity(),
            'daysRemaining' => $daysRemaining,
            'progress' => null,
            'hasProgress' => false,
        ];
    }
}
