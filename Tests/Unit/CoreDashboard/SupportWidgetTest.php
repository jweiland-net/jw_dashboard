<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Tests\Unit\CoreDashboard;

use JWeiland\JwDashboard\Configuration\ExtensionConfigurationManager;
use JWeiland\JwDashboard\CoreDashboard\Status\SupportStatus;
use JWeiland\JwDashboard\CoreDashboard\SupportContact;
use JWeiland\JwDashboard\CoreDashboard\SupportWidget;
use JWeiland\JwDashboard\Domain\Model\ContactPerson;
use JWeiland\JwDashboard\Widget\EditorGuideWidget;
use JWeiland\JwDashboard\Widget\MaintenanceContractWidget;
use JWeiland\JwDashboard\Widget\QuickLinksWidget;
use JWeiland\JwDashboard\Widget\Typo3VersionWidget;
use JWeiland\JwDashboard\Widget\WidgetData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;

final class SupportWidgetTest extends TestCase
{
    private MockObject&EditorGuideWidget $editorGuideWidget;
    private MockObject&QuickLinksWidget $quickLinksWidget;
    private MockObject&Typo3VersionWidget $typo3VersionWidget;
    private MockObject&MaintenanceContractWidget $maintenanceContractWidget;
    private MockObject&ModuleProvider $moduleProvider;
    private MockObject&UriBuilder $uriBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editorGuideWidget = $this->createMock(EditorGuideWidget::class);
        $this->quickLinksWidget = $this->createMock(QuickLinksWidget::class);
        $this->typo3VersionWidget = $this->createMock(Typo3VersionWidget::class);
        $this->maintenanceContractWidget = $this->createMock(MaintenanceContractWidget::class);
        $this->moduleProvider = $this->createMock(ModuleProvider::class);
        $this->uriBuilder = $this->createMock(UriBuilder::class);

        $this->editorGuideWidget->method('getIdentifier')->willReturn('editorGuide');
        $this->quickLinksWidget->method('getIdentifier')->willReturn('quickLinks');
        $this->typo3VersionWidget->method('getIdentifier')->willReturn('typo3Version');
        $this->maintenanceContractWidget->method('getIdentifier')->willReturn('maintenanceContract');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);

        parent::tearDown();
    }

    #[Test]
    public function getCssFilesReturnsExistingStylesheet(): void
    {
        $files = $this->createSubject()->getCssFiles();

        self::assertSame(['EXT:jw_dashboard/Resources/Public/Css/core-widget.css'], $files);
        self::assertFileExists(__DIR__ . '/../../../Resources/Public/Css/core-widget.css');
    }

    #[Test]
    public function widgetHasNoSettings(): void
    {
        self::assertSame([], $this->createSubject()->getSettingsDefinitions());
    }

    #[Test]
    public function getViewDataUsesPayloadsOfJwDashboardWidgets(): void
    {
        $person = new ContactPerson();
        $person->setName('Anna Becker');
        $person->setPhone('+49 711 000000-10');
        $this->mockPayload($this->editorGuideWidget, [
            'agencyName' => 'jweiland.net',
            'agencyPhone' => '+49 711 94969-60',
            'agencyEmail' => 'projects@jweiland.net',
            'mainContact' => $person,
        ]);
        $quickLinks = [['label' => 'TYPO3 help', 'url' => 'https://docs.typo3.org', 'icon' => 'actions-link', 'external' => true]];
        $this->mockPayload($this->quickLinksWidget, ['customLinks' => $quickLinks, 'moduleLinks' => []]);
        $this->typo3VersionWidget->method('isEnabled')->willReturn(true);
        $this->mockPayload($this->typo3VersionWidget, [
            'installed' => '14.3.9',
            'latest' => '14.3.10',
            'checkFailed' => false,
            'isUpToDate' => false,
            'isSecurityRelease' => false,
            'releaseUrl' => 'https://get.typo3.org/version/14',
        ]);

        $viewData = $this->createSubject()->getViewData();

        self::assertInstanceOf(SupportContact::class, $viewData['contact']);
        self::assertTrue($viewData['contact']->isPerson);
        self::assertSame('Anna Becker', $viewData['contact']->name);
        self::assertSame($quickLinks, $viewData['quickLinks']);
        self::assertSame('jweiland.net', $viewData['agency']['agencyName']);
        self::assertInstanceOf(SupportStatus::class, $viewData['status']);
        self::assertCount(1, $viewData['status']->items);
        self::assertSame('typo3Version', $viewData['status']->items[0]->identifier);
    }

    #[Test]
    public function disabledStatusWidgetsAreNotQueried(): void
    {
        $this->typo3VersionWidget->method('isEnabled')->willReturn(false);
        $this->typo3VersionWidget->expects(self::never())->method('getData');
        $this->maintenanceContractWidget->method('isEnabled')->willReturn(false);
        $this->maintenanceContractWidget->expects(self::never())->method('getData');

        $viewData = $this->createSubject()->getViewData();

        self::assertTrue($viewData['status']->isEmpty());
    }

    #[Test]
    public function agencyAndQuickLinksIgnoreTheModuleWidgetToggles(): void
    {
        $this->editorGuideWidget->method('isEnabled')->willReturn(false);
        $this->quickLinksWidget->method('isEnabled')->willReturn(false);
        $this->mockPayload($this->editorGuideWidget, ['agencyName' => 'jweiland.net']);
        $this->mockPayload($this->quickLinksWidget, ['customLinks' => [['label' => 'Help']]]);

        $viewData = $this->createSubject()->getViewData();

        self::assertSame('jweiland.net', $viewData['contact']->name);
        self::assertSame([['label' => 'Help']], $viewData['quickLinks']);
    }

    #[Test]
    public function failingWidgetDoesNotBreakTheSupportWidget(): void
    {
        $this->typo3VersionWidget->method('isEnabled')->willReturn(true);
        $this->typo3VersionWidget->method('getData')->willThrowException(new \RuntimeException('API down', 1792800000));

        $viewData = $this->createSubject()->getViewData();

        self::assertTrue($viewData['status']->isEmpty());
    }

    #[Test]
    public function moduleLinkIsNullWithoutBackendUser(): void
    {
        $this->moduleProvider->expects(self::never())->method('accessGranted');

        self::assertNull($this->createSubject()->getViewData()['moduleLink']);
    }

    #[Test]
    public function moduleLinkIsNullWithoutModuleAccess(): void
    {
        $GLOBALS['BE_USER'] = $this->createMock(BackendUserAuthentication::class);
        $this->moduleProvider->method('accessGranted')->with('jw_dashboard')->willReturn(false);

        self::assertNull($this->createSubject()->getViewData()['moduleLink']);
    }

    #[Test]
    public function moduleLinkPointsToJwDashboardModule(): void
    {
        $GLOBALS['BE_USER'] = $this->createMock(BackendUserAuthentication::class);
        $this->moduleProvider->method('accessGranted')->with('jw_dashboard')->willReturn(true);
        $this->uriBuilder->method('buildUriFromRoute')->with('jw_dashboard')->willReturn(new Uri('/typo3/module/jw-dashboard'));

        self::assertSame(
            ['url' => '/typo3/module/jw-dashboard', 'identifier' => 'jw_dashboard'],
            $this->createSubject()->getViewData()['moduleLink'],
        );
    }

    #[Test]
    public function contractReviewFallsBackToModuleForUsersWithModuleAccess(): void
    {
        $GLOBALS['BE_USER'] = $this->createMock(BackendUserAuthentication::class);
        $this->moduleProvider->method('accessGranted')->willReturn(true);
        $this->uriBuilder->method('buildUriFromRoute')->willReturn(new Uri('/typo3/module/jw-dashboard'));
        $this->maintenanceContractWidget->method('isEnabled')->willReturn(true);
        $this->mockPayload($this->maintenanceContractWidget, [
            'contracts' => [['contract' => null, 'state' => 'expired', 'daysRemaining' => -1]],
            'manageUrl' => '',
        ]);

        $item = $this->createSubject()->getViewData()['status']->items[0];

        self::assertSame('jw_dashboard', $item->actionModule);
    }

    #[Test]
    public function legalInformationIsTakenFromExtensionSettings(): void
    {
        $legal = $this->createSubject([
            'legalRepresentative' => 'Jochen Weiland',
            'legalSeat' => '',
            'legalRegister' => '',
            'imprintUrl' => 'https://jweiland.net/impressum.html',
            'privacyUrl' => '',
            'termsUrl' => '',
            'newsletterUrl' => '',
        ])->getViewData()['legal'];

        self::assertSame('Jochen Weiland', $legal['representative']);
        self::assertTrue($legal['hasDetails']);
        self::assertFalse($legal['showImprintLink'], 'Imprint URL is shown inside the details');
        self::assertTrue($legal['hasLinks']);
    }

    #[Test]
    public function imprintBecomesPlainLinkWithoutDetails(): void
    {
        $legal = $this->createSubject([
            'legalRepresentative' => '',
            'legalSeat' => '',
            'legalRegister' => '',
            'imprintUrl' => 'https://jweiland.net/impressum.html',
            'privacyUrl' => '',
            'termsUrl' => '',
            'newsletterUrl' => '',
        ])->getViewData()['legal'];

        self::assertFalse($legal['hasDetails']);
        self::assertTrue($legal['showImprintLink']);
        self::assertTrue($legal['hasLinks']);
    }

    #[Test]
    public function footerLinksAreHiddenWithoutLegalSettings(): void
    {
        $legal = $this->createSubject([
            'legalRepresentative' => '',
            'legalSeat' => '',
            'legalRegister' => '',
            'imprintUrl' => '',
            'privacyUrl' => '',
            'termsUrl' => '',
            'newsletterUrl' => '',
        ])->getViewData()['legal'];

        self::assertFalse($legal['hasLinks']);
        self::assertFalse($legal['showImprintLink']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function mockPayload(MockObject $widget, array $payload): void
    {
        $widget->method('getData')->willReturn(new WidgetData(
            widgetIdentifier: 'mock',
            available: true,
            payload: $payload,
        ));
    }

    /**
     * @param array<string, mixed> $extensionSettings
     */
    private function createSubject(array $extensionSettings = []): SupportWidget
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->with('jw_dashboard')->willReturn($extensionSettings);

        return new SupportWidget(
            $this->createMock(WidgetConfigurationInterface::class),
            // Final and not used by getViewData(), rendering is covered by the functional tests
            (new \ReflectionClass(BackendViewFactory::class))->newInstanceWithoutConstructor(),
            new ExtensionConfigurationManager($extensionConfiguration),
            $this->editorGuideWidget,
            $this->quickLinksWidget,
            $this->typo3VersionWidget,
            $this->maintenanceContractWidget,
            $this->moduleProvider,
            $this->uriBuilder,
        );
    }
}
