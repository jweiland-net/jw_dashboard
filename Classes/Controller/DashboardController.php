<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Controller;

use JWeiland\JwDashboard\Widget\WidgetInterface;
use JWeiland\JwDashboard\Widget\WidgetRegistry;
use JWeiland\JwDashboard\Widget\WidgetView;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Main backend controller for the JW Dashboard module.
 * Renders a card for each enabled widget of the WidgetRegistry.
 *
 * Plain (non-Extbase) controller on purpose: Extbase modules are always attached to the "content"
 * main module (incl. page tree), while this module is a standalone main module.
 */
#[AsController]
final readonly class DashboardController
{
    private const LANGUAGE_FILE = 'LLL:EXT:jw_dashboard/Resources/Private/Language/locallang.xlf:';

    public function __construct(
        private ModuleTemplateFactory $moduleTemplateFactory,
        private ComponentFactory $componentFactory,
        private PageRenderer $pageRenderer,
        private WidgetRegistry $widgetRegistry,
        private Context $context,
    ) {}

    public function overviewAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->pageRenderer->addCssFile('EXT:jw_dashboard/Resources/Public/Css/dashboard.css');
        $this->pageRenderer->loadJavaScriptModule('@jweiland/jw-dashboard/dashboard.js');
        $this->pageRenderer->addInlineLanguageLabelFile(
            'EXT:jw_dashboard/Resources/Private/Language/locallang.xlf',
            'dashboard.widget.',
        );

        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->setTitle($this->getLanguageService()->sL(self::LANGUAGE_FILE . 'dashboard.title'));
        $this->addDocHeaderButtons($moduleTemplate, $request);

        $moduleTemplate->assignMultiple([
            'widgets' => array_map(
                static fn(WidgetInterface $widget): WidgetView => WidgetView::fromWidget($widget, true),
                $this->widgetRegistry->getEnabledWidgets(),
            ),
            'greeting' => $this->getGreetingKey(),
            'userName' => $this->getUserName(),
        ]);

        return $moduleTemplate->renderResponse('Dashboard/Overview');
    }

    private function addDocHeaderButtons(ModuleTemplate $moduleTemplate, ServerRequestInterface $request): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $buttonBar->addButton(
            $this->componentFactory->createReloadButton($request->getUri()),
            ButtonBar::BUTTON_POSITION_RIGHT,
        );
        $buttonBar->addButton(
            $this->componentFactory->createShortcutButton()
                ->setRouteIdentifier('jw_dashboard')
                ->setDisplayName($this->getLanguageService()->sL(self::LANGUAGE_FILE . 'dashboard.title')),
        );
    }

    /**
     * Label key suffix of the time-of-day greeting: morning, afternoon or evening
     */
    private function getGreetingKey(): string
    {
        $now = $this->context->getPropertyFromAspect('date', 'full');
        $hour = $now instanceof \DateTimeInterface ? (int)$now->format('G') : 12;

        return match (true) {
            $hour >= 5 && $hour < 12 => 'morning',
            $hour >= 12 && $hour < 18 => 'afternoon',
            default => 'evening',
        };
    }

    private function getUserName(): string
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if (!$backendUser instanceof BackendUserAuthentication) {
            return '';
        }

        $realName = trim((string)($backendUser->user['realName'] ?? ''));

        return $realName !== '' ? $realName : (string)($backendUser->user['username'] ?? '');
    }

    private function getLanguageService(): LanguageService
    {
        $languageService = $GLOBALS['LANG'] ?? null;
        if (!$languageService instanceof LanguageService) {
            throw new \RuntimeException('LanguageService is not initialized', 1792137600);
        }

        return $languageService;
    }
}
