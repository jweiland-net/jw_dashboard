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
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconRegistry;

#[AsTaggedItem(priority: 50)]
class QuickLinksWidget extends AbstractWidget
{
    /**
     * Backend modules offered as shortcut, if registered and accessible for the current user
     */
    private const BACKEND_MODULES = [
        'web_layout',
        'records',
        'media_management',
        'site_configuration',
        'scheduler',
        'extensionmanager',
        'system_settings',
    ];

    private const FALLBACK_ICON = 'actions-link';

    public function __construct(
        private readonly ExtensionConfigurationManager $extConfManager,
        private readonly ModuleProvider $moduleProvider,
        private readonly UriBuilder $uriBuilder,
        private readonly IconRegistry $iconRegistry,
    ) {}

    public function getIdentifier(): string
    {
        return 'quickLinks';
    }

    public function getTitle(): string
    {
        return self::LANGUAGE_FILE . 'widget.quickLinks.title';
    }

    public function getIconIdentifier(): string
    {
        return 'actions-link';
    }

    public function getSize(): WidgetSize
    {
        return WidgetSize::Small;
    }

    public function isEnabled(DashboardExtConf $extConf): bool
    {
        return $extConf->enableWidgetQuickLinks;
    }

    public function getData(): WidgetData
    {
        return new WidgetData(
            widgetIdentifier: $this->getIdentifier(),
            available: true,
            payload: [
                'customLinks' => $this->getCustomLinks(),
                'moduleLinks' => $this->getModuleLinks(),
            ],
        );
    }

    /**
     * @return list<array{label: string, url: string, icon: string, external: bool}>
     */
    private function getCustomLinks(): array
    {
        $links = [];
        foreach ($this->extConfManager->get()->getParsedQuickLinks() as $link) {
            if ($link['label'] === '' || !$this->isAllowedUrl($link['url'])) {
                continue;
            }

            $links[] = [
                'label' => $link['label'],
                'url' => $link['url'],
                'icon' => $this->iconRegistry->isRegistered($link['icon']) ? $link['icon'] : self::FALLBACK_ICON,
                'external' => !str_starts_with($link['url'], '/'),
            ];
        }

        return $links;
    }

    /**
     * @return list<array{label: string, url: string, icon: string, identifier: string}>
     */
    private function getModuleLinks(): array
    {
        $backendUser = $this->getBackendUser();
        if (!$backendUser instanceof BackendUserAuthentication) {
            return [];
        }

        $links = [];
        foreach (self::BACKEND_MODULES as $identifier) {
            $module = $this->moduleProvider->getModule($identifier, $backendUser);
            if (!$module instanceof ModuleInterface) {
                continue;
            }

            $links[] = [
                'label' => $module->getTitle(),
                'url' => (string)$this->uriBuilder->buildUriFromRoute($module->getIdentifier()),
                'icon' => $module->getIconIdentifier(),
                'identifier' => $module->getIdentifier(),
            ];
        }

        return $links;
    }

    /**
     * Prevents "javascript:" and similar schemes coming from the extension settings
     */
    private function isAllowedUrl(string $url): bool
    {
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true;
        }

        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto'], true);
    }
}
