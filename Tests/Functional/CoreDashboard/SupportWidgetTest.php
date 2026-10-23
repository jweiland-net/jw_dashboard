<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Tests\Functional\CoreDashboard;

use JWeiland\JwDashboard\CoreDashboard\SupportWidget;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Settings\Settings;
use TYPO3\CMS\Dashboard\DashboardPresetRegistry;
use TYPO3\CMS\Dashboard\WidgetGroupRegistry;
use TYPO3\CMS\Dashboard\WidgetRegistry;
use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Renders the "jweiland.net Support" widget the way EXT:dashboard does (WidgetRendererInterface)
 * against a real TYPO3 instance with database records.
 */
final class SupportWidgetTest extends FunctionalTestCase
{
    private const WIDGET_IDENTIFIER = 'jwDashboardSupport';

    protected array $coreExtensionsToLoad = [
        'dashboard',
    ];

    protected array $testExtensionsToLoad = [
        'jweiland/jw-dashboard',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'jw_dashboard' => [
                // No remote requests in tests unless a test enables the version check on purpose
                'enableWidgetTypo3Version' => '0',
                'enableWidgetMaintenanceContract' => '1',
                'agencyName' => 'jweiland.net',
                'agencyEmail' => 'projects@jweiland.net',
                'agencyPhone' => '+49 711 94969-60',
                'agencyWebsite' => 'https://jweiland.net',
                'welcomeMessage' => '<p>Welcome to <strong>your</strong> website.</p><script>alert(1)</script>',
                'showAgencyLogo' => '1',
                'customQuickLinks' => "TYPO3 help|https://docs.typo3.org|actions-question-circle\nBroken|javascript:alert(1)|",
                'legalRepresentative' => 'Jochen Weiland',
                'legalSeat' => 'Filderstadt',
                'legalRegister' => 'Amtsgericht Stuttgart, HRA 741874',
                'imprintUrl' => 'https://jweiland.net/impressum.html',
                'privacyUrl' => 'https://jweiland.net/datenschutz.html',
                'termsUrl' => 'https://jweiland.net/agb.html',
                'newsletterUrl' => 'https://jweiland.net/typo3-newsletter.html',
                'showContractSignupPrompt' => '1',
                'contractSignupUrl' => 'https://jweiland.net/typo3-wartungsvertrag.html',
                'contractRenewalWarningDays' => '30',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
    }

    #[Test]
    public function widgetIsRegisteredInDashboardWidgetRegistry(): void
    {
        $widgets = $this->get(WidgetRegistry::class)->getAllWidgets();

        self::assertArrayHasKey(self::WIDGET_IDENTIFIER, $widgets);
        $configuration = $widgets[self::WIDGET_IDENTIFIER];
        self::assertSame(['jweiland'], $configuration->getGroupNames());
        self::assertSame('ext-jw-dashboard', $configuration->getIconIdentifier());
        self::assertSame('large', $configuration->getWidth());
        self::assertSame('large', $configuration->getHeight());
        self::assertSame('jw_dashboard.coreWidget.support', $configuration->getServiceName());
        self::assertInstanceOf(SupportWidget::class, $this->get($configuration->getServiceName()));
    }

    #[Test]
    public function widgetGroupIsRegistered(): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

        $groups = $this->get(WidgetGroupRegistry::class)->getWidgetGroups();

        self::assertArrayHasKey('jweiland', $groups);
        self::assertSame('jweiland.net', $groups['jweiland']->getTitle());
        self::assertArrayHasKey(self::WIDGET_IDENTIFIER, $this->get(WidgetRegistry::class)->getAllWidgets());
    }

    #[Test]
    public function dashboardPresetContainsTheWidget(): void
    {
        $presets = $this->get(DashboardPresetRegistry::class)->getDashboardPresets();

        self::assertArrayHasKey('jweiland', $presets);
        self::assertSame(
            [self::WIDGET_IDENTIFIER],
            array_column($presets['jweiland']->getDefaultWidgets(), 'identifier'),
        );
        self::assertTrue($presets['jweiland']->isShowInWizard());
    }

    #[Test]
    public function adminSeesContactPersonWithAgencyHotline(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tx_jwdashboard_contact_person.csv');

        $html = $this->renderWidget(1);

        self::assertStringContainsString('Your personal contact', $html);
        self::assertStringContainsString('Anna Becker', $html);
        self::assertStringContainsString('Project Manager', $html);
        self::assertStringContainsString('>AB</span>', $html);
        self::assertStringContainsString('href="tel:+4971100000010"', $html);
        self::assertStringContainsString('href="mailto:anna.becker@example.com"', $html);
        self::assertStringContainsString('href="tel:+497119496960"', $html, 'Agency hotline');
        self::assertStringNotContainsString('Max Mustermann', $html, 'Only the primary contact is shown');
    }

    #[Test]
    public function agencyTeamIsTheContactWithoutContactPerson(): void
    {
        $html = $this->renderWidget(1);

        self::assertStringContainsString('Your contact', $html);
        self::assertStringContainsString('jweiland.net team', $html);
        self::assertStringContainsString('href="tel:+497119496960"', $html);
        self::assertStringContainsString('href="mailto:projects@jweiland.net"', $html);
    }

    #[Test]
    public function agencyCardShowsSanitizedWelcomeMessageAndSafeQuickLinks(): void
    {
        $html = $this->renderWidget(1);

        self::assertStringContainsString('Welcome to <strong>your</strong> website.', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('Help &amp; tools', $html);
        self::assertStringContainsString('href="https://docs.typo3.org"', $html);
        self::assertStringContainsString('TYPO3 help', $html);
        self::assertStringNotContainsString('javascript:', $html);
        self::assertStringContainsString('jw-support__logo', $html);
    }

    #[Test]
    public function contractsThatNeedAttentionAreListedInTheStatusStrip(): void
    {
        $this->insertContract('Hosting', '+12 days');
        $this->insertContract('Support', '-3 days');
        $this->insertContract('Maintenance', '+200 days');

        $html = $this->renderWidget(1);

        self::assertStringContainsString('2 maintenance contracts need attention', $html);
        self::assertStringContainsString('Hosting ends in 12 days', $html);
        self::assertStringContainsString('Support expired', $html);
        self::assertStringNotContainsString('Maintenance ends', $html);
        self::assertStringContainsString('Needs attention', $html);
        self::assertStringContainsString('jw-support__status-item--warning', $html);
        // Admins can edit the contract records directly
        self::assertMatchesRegularExpression('/href="[^"]*records[^"]*tx_jwdashboard_contract[^"]*"[^>]*class="jw-support__status-action"/s', $html);
    }

    #[Test]
    public function activeContractsShowCompactAllGoodStrip(): void
    {
        $this->insertContract('Hosting', '+200 days');

        $html = $this->renderWidget(1);

        self::assertStringContainsString('jw-support__ok', $html);
        self::assertStringContainsString('System up to date', $html);
        self::assertStringContainsString('1 maintenance contract active', $html);
        self::assertStringNotContainsString('jw-support__status-list', $html);
    }

    #[Test]
    public function missingContractShowsSignupPrompt(): void
    {
        $html = $this->renderWidget(1);

        self::assertStringContainsString('No maintenance contract found.', $html);
        self::assertStringContainsString('href="https://jweiland.net/typo3-wartungsvertrag.html"', $html);
        self::assertStringContainsString('jw-support__status-item--info', $html);
    }

    #[Test]
    public function failedVersionCheckIsShownAsInfo(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['jw_dashboard']['enableWidgetTypo3Version'] = '1';
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['jw_dashboard']['typo3VersionApiUrl'] = 'http://127.0.0.1:9/';
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['jw_dashboard']['requestTimeout'] = '1';
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['jw_dashboard']['showContractSignupPrompt'] = '0';

        $html = $this->renderWidget(1);

        self::assertStringContainsString('The latest TYPO3 version could not be determined.', $html);
        self::assertStringContainsString('data-status="typo3Version"', $html);
    }

    #[Test]
    public function footerContainsLegalLinksImprintDetailsAndModuleButton(): void
    {
        $html = $this->renderWidget(1);

        self::assertStringContainsString('href="https://jweiland.net/datenschutz.html"', $html);
        self::assertStringContainsString('href="https://jweiland.net/agb.html"', $html);
        self::assertStringContainsString('href="https://jweiland.net/typo3-newsletter.html"', $html);
        self::assertStringContainsString('<details class="jw-support-footer__imprint">', $html);
        self::assertStringContainsString('Jochen Weiland', $html);
        self::assertStringContainsString('Amtsgericht Stuttgart, HRA 741874', $html);
        self::assertStringContainsString('Open JW Dashboard', $html);
        self::assertStringContainsString('data-dispatch-action="TYPO3.ModuleMenu.showModule"', $html);
        self::assertStringContainsString('data-dispatch-args-list="jw_dashboard"', $html);
    }

    #[Test]
    public function editorWithoutModuleAccessDoesNotSeeModuleButton(): void
    {
        $this->insertContract('Hosting', '+12 days');

        $html = $this->renderWidget(2);

        self::assertStringNotContainsString('Open JW Dashboard', $html);
        self::assertStringNotContainsString('data-dispatch-args-list="jw_dashboard"', $html);
        // Neither contract records nor the module are accessible, so the row has no action
        self::assertStringContainsString('Hosting ends in 12 days', $html);
        self::assertStringNotContainsString('jw-support__status-action', $html);
    }

    #[Test]
    public function germanBackendUserGetsGermanLabels(): void
    {
        $this->getConnectionPool()->getConnectionForTable('be_users')->update('be_users', ['lang' => 'de'], ['uid' => 1]);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tx_jwdashboard_contact_person.csv');

        $html = $this->renderWidget(1);

        self::assertStringContainsString('Anrufen', $html);
        self::assertStringContainsString('E-Mail schreiben', $html);
        self::assertStringContainsString('Datenschutzerklärung', $html);
    }

    private function renderWidget(int $backendUserUid): string
    {
        $backendUser = $this->setUpBackendUser($backendUserUid);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $request = (new ServerRequest('https://typo3-testing.local/typo3/', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $configuration = $this->get(WidgetRegistry::class)->getAllWidgets()[self::WIDGET_IDENTIFIER];
        $widget = $this->get($configuration->getServiceName());
        self::assertInstanceOf(SupportWidget::class, $widget);

        $result = $widget->renderWidget(new WidgetContext(
            identifier: self::WIDGET_IDENTIFIER,
            rawData: [],
            configuration: $configuration,
            settings: new Settings([]),
            request: $request,
        ));

        self::assertTrue($result->refreshable);

        return $result->content;
    }

    private function insertContract(string $title, string $endDateModifier): void
    {
        $today = new \DateTimeImmutable('today');
        $this->getConnectionPool()->getConnectionForTable('tx_jwdashboard_contract')->insert('tx_jwdashboard_contract', [
            'pid' => 0,
            'title' => $title,
            'start_date' => $today->modify('-1 year')->getTimestamp(),
            'end_date' => $today->modify($endDateModifier)->getTimestamp(),
            'status' => 'active',
            'contract_type' => 'maintenance',
            'renewal_prompt_days' => 30,
        ]);
    }
}
