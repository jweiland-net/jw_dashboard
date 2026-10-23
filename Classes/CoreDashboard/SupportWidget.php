<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\CoreDashboard;

use JWeiland\JwDashboard\Configuration\ExtensionConfigurationManager;
use JWeiland\JwDashboard\CoreDashboard\Status\SupportStatusBuilder;
use JWeiland\JwDashboard\Widget\EditorGuideWidget;
use JWeiland\JwDashboard\Widget\MaintenanceContractWidget;
use JWeiland\JwDashboard\Widget\QuickLinksWidget;
use JWeiland\JwDashboard\Widget\Typo3VersionWidget;
use JWeiland\JwDashboard\Widget\WidgetInterface as JwWidgetInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetContext;
use TYPO3\CMS\Dashboard\Widgets\WidgetRendererInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetResult;

/**
 * Widget for the TYPO3 core dashboard (EXT:dashboard): system status, personal contact, agency
 * help links and imprint, plus a button that opens the JW Dashboard backend module.
 *
 * No data of its own: everything is taken from the already configured JW Dashboard widgets
 * (extension settings, contact person and contract records, TYPO3 version check), so both
 * dashboards always show the same information.
 *
 * Registered in Configuration/Services.php only if EXT:dashboard is installed.
 */
final readonly class SupportWidget implements WidgetRendererInterface, AdditionalCssInterface
{
    public const MODULE_IDENTIFIER = 'jw_dashboard';

    public const TEMPLATE = 'CoreWidget/Support';

    /**
     * Maximum number of tiles in "Help & tools"
     */
    public const MAX_TOOL_LINKS = 6;

    public function __construct(
        private WidgetConfigurationInterface $configuration,
        private BackendViewFactory $backendViewFactory,
        private ExtensionConfigurationManager $extConfManager,
        private EditorGuideWidget $editorGuideWidget,
        private QuickLinksWidget $quickLinksWidget,
        private Typo3VersionWidget $typo3VersionWidget,
        private MaintenanceContractWidget $maintenanceContractWidget,
        private ModuleProvider $moduleProvider,
        private UriBuilder $uriBuilder,
    ) {}

    public function getSettingsDefinitions(): array
    {
        return [];
    }

    public function getCssFiles(): array
    {
        return ['EXT:jw_dashboard/Resources/Public/Css/core-widget.css'];
    }

    public function renderWidget(WidgetContext $context): WidgetResult
    {
        $view = $this->backendViewFactory->create($context->request, ['typo3/cms-dashboard', 'jweiland/jw-dashboard']);
        $view->assignMultiple($this->getViewData());

        return new WidgetResult(
            content: $view->render(self::TEMPLATE),
            refreshable: true,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $agency = $this->getPayload($this->editorGuideWidget, true);
        $moduleLink = $this->getModuleLink();

        return [
            'configuration' => $this->configuration,
            'status' => (new SupportStatusBuilder())->build(
                $this->getPayload($this->typo3VersionWidget),
                $this->getPayload($this->maintenanceContractWidget),
                $moduleLink['identifier'] ?? '',
            ),
            'contact' => SupportContact::fromAgencyPayload($agency),
            'agency' => $agency,
            'quickLinks' => $this->getToolLinks($this->getPayload($this->quickLinksWidget, true)),
            'legal' => $this->getLegalInformation(),
            'moduleLink' => $moduleLink,
        ];
    }

    /**
     * Payload of a JW Dashboard widget. Status sections (version check, contracts) follow the widget
     * toggles of the extension settings, so e.g. a disabled version check never triggers a remote request.
     *
     * @return array<string, mixed>
     */
    private function getPayload(JwWidgetInterface $widget, bool $ignoreToggle = false): array
    {
        if (!$ignoreToggle && !$widget->isEnabled($this->extConfManager->get())) {
            return [];
        }

        $data = $widget->getSafeData();

        return $data->available && is_array($data->payload) ? $data->payload : [];
    }

    /**
     * Tiles of "Help & tools": the configured custom quick links (agency help pages, upload or
     * credential services, ...). Without custom links, the backend modules of the Quick Links
     * widget the user has access to are offered instead, so the section is never empty.
     *
     * @param array<string, mixed> $quickLinks Payload of the QuickLinksWidget
     * @return list<array<string, mixed>>
     */
    private function getToolLinks(array $quickLinks): array
    {
        $customLinks = is_array($quickLinks['customLinks'] ?? null) ? $quickLinks['customLinks'] : [];
        if ($customLinks !== []) {
            return array_slice(array_values($customLinks), 0, self::MAX_TOOL_LINKS);
        }

        $moduleLinks = [];
        foreach (is_array($quickLinks['moduleLinks'] ?? null) ? $quickLinks['moduleLinks'] : [] as $link) {
            if (!is_array($link) || ($link['url'] ?? '') === '') {
                continue;
            }
            $moduleLinks[] = [
                'label' => (string)($link['label'] ?? ''),
                'url' => (string)$link['url'],
                'icon' => (string)($link['icon'] ?? 'actions-link'),
                'external' => false,
                'module' => (string)($link['identifier'] ?? ''),
            ];
        }

        return array_slice($moduleLinks, 0, self::MAX_TOOL_LINKS);
    }

    /**
     * Imprint, privacy, terms and newsletter of the agency, shown in the widget footer
     *
     * @return array{representative: string, seat: string, register: string, imprintUrl: string, privacyUrl: string, termsUrl: string, newsletterUrl: string, hasDetails: bool, showImprintLink: bool, hasLinks: bool}
     */
    private function getLegalInformation(): array
    {
        $extConf = $this->extConfManager->get();

        $legal = [
            'representative' => $extConf->legalRepresentative,
            'seat' => $extConf->legalSeat,
            'register' => $extConf->legalRegister,
            'imprintUrl' => $extConf->imprintUrl,
            'privacyUrl' => $extConf->privacyUrl,
            'termsUrl' => $extConf->termsUrl,
            'newsletterUrl' => $extConf->newsletterUrl,
        ];
        $legal['hasDetails'] = $legal['representative'] !== '' || $legal['seat'] !== '' || $legal['register'] !== '';
        // Without details there is nothing to fold out, so the imprint becomes a plain link
        $legal['showImprintLink'] = $legal['imprintUrl'] !== '' && !$legal['hasDetails'];
        $legal['hasLinks'] = $legal['hasDetails']
            || $legal['imprintUrl'] !== ''
            || $legal['privacyUrl'] !== ''
            || $legal['termsUrl'] !== ''
            || $legal['newsletterUrl'] !== '';

        return $legal;
    }

    /**
     * @return array{url: string, identifier: string}|null
     */
    private function getModuleLink(): ?array
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if (!$backendUser instanceof BackendUserAuthentication
            || !$this->moduleProvider->accessGranted(self::MODULE_IDENTIFIER, $backendUser)
        ) {
            return null;
        }

        return [
            'url' => (string)$this->uriBuilder->buildUriFromRoute(self::MODULE_IDENTIFIER),
            'identifier' => self::MODULE_IDENTIFIER,
        ];
    }
}
