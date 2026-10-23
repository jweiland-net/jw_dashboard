<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use JWeiland\JwDashboard\CoreDashboard\SupportWidget;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Dashboard\WidgetRegistry;

/**
 * Widgets for the TYPO3 core dashboard. EXT:dashboard is optional (see "suggest"),
 * so they are only registered if the system extension is installed.
 */
return static function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder): void {
    if (!$containerBuilder->hasDefinition(WidgetRegistry::class)) {
        return;
    }

    $services = $configurator->services();
    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private();

    $services->set('jw_dashboard.coreWidget.support', SupportWidget::class)
        ->tag('dashboard.widget', [
            'identifier' => 'jwDashboardSupport',
            'groupNames' => 'jweiland',
            'title' => 'LLL:EXT:jw_dashboard/Resources/Private/Language/locallang.xlf:coreWidget.support.title',
            'description' => 'LLL:EXT:jw_dashboard/Resources/Private/Language/locallang.xlf:coreWidget.support.description',
            'iconIdentifier' => 'ext-jw-dashboard',
            'height' => 'large',
            'width' => 'large',
        ]);
};
