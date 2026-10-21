<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Controller\Ajax;

use JWeiland\JwDashboard\Widget\DeferrableWidgetInterface;
use JWeiland\JwDashboard\Widget\RefreshableWidgetInterface;
use JWeiland\JwDashboard\Widget\WidgetInterface;
use JWeiland\JwDashboard\Widget\WidgetRegistry;
use JWeiland\JwDashboard\Widget\WidgetView;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * AJAX endpoint returning the rendered card body of a single widget.
 *
 * - widget=<id>            loads a deferred widget
 * - widget=<id>&refresh=1  discards cached data of a refreshable widget first
 */
final readonly class WidgetRefreshController
{
    public function __construct(
        private WidgetRegistry $widgetRegistry,
        private BackendViewFactory $backendViewFactory,
    ) {}

    public function refreshAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $widgetIdentifier = $queryParams['widget'] ?? '';
        $widget = is_string($widgetIdentifier) ? $this->widgetRegistry->findEnabledWidget($widgetIdentifier) : null;
        $refresh = ($queryParams['refresh'] ?? '') === '1';

        if (!$widget instanceof WidgetInterface || !$this->isAllowed($widget, $refresh)) {
            return new JsonResponse(['success' => false, 'html' => ''], 400);
        }

        if ($refresh && $widget instanceof RefreshableWidgetInterface) {
            $widget->refresh();
        }

        $widgetView = WidgetView::fromWidget($widget);
        $view = $this->backendViewFactory->create($request, ['jweiland/jw-dashboard']);
        $view->assign('widget', $widgetView);

        return new JsonResponse([
            'success' => $widgetView->data->available,
            'html' => $view->render('Widget/Body'),
        ]);
    }

    private function isAllowed(WidgetInterface $widget, bool $refresh): bool
    {
        return $refresh
            ? $widget instanceof RefreshableWidgetInterface
            : $widget instanceof DeferrableWidgetInterface || $widget instanceof RefreshableWidgetInterface;
    }
}
