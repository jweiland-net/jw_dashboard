<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Widget;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Base class for dashboard widgets. Provides the safe data wrapper,
 * so a single failing widget never crashes the entire dashboard.
 */
abstract class AbstractWidget implements WidgetInterface
{
    protected const LANGUAGE_FILE = 'LLL:EXT:jw_dashboard/Resources/Private/Language/locallang.xlf:';

    public function getSize(): WidgetSize
    {
        return WidgetSize::Medium;
    }

    final public function getSafeData(): WidgetData
    {
        try {
            return $this->getData();
        } catch (\Throwable $throwable) {
            return WidgetData::unavailable(
                $this->getIdentifier(),
                $throwable->getMessage(),
            );
        }
    }

    protected function getBackendUser(): ?BackendUserAuthentication
    {
        $backendUser = $GLOBALS['BE_USER'] ?? null;

        return $backendUser instanceof BackendUserAuthentication ? $backendUser : null;
    }

    protected function getLanguageService(): ?LanguageService
    {
        $languageService = $GLOBALS['LANG'] ?? null;

        return $languageService instanceof LanguageService ? $languageService : null;
    }
}
