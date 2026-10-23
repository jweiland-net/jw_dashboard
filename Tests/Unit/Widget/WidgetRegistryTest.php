<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Tests\Unit\Widget;

use JWeiland\JwDashboard\Configuration\DashboardExtConf;
use JWeiland\JwDashboard\Configuration\ExtensionConfigurationManager;
use JWeiland\JwDashboard\Widget\AbstractWidget;
use JWeiland\JwDashboard\Widget\DeferrableWidgetInterface;
use JWeiland\JwDashboard\Widget\WidgetData;
use JWeiland\JwDashboard\Widget\WidgetInterface;
use JWeiland\JwDashboard\Widget\WidgetRegistry;
use JWeiland\JwDashboard\Widget\WidgetView;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final class WidgetRegistryTest extends TestCase
{
    #[Test]
    public function getEnabledWidgetsKeepsOrderAndSkipsDisabledWidgets(): void
    {
        $subject = $this->createSubject([
            $this->createWidget('first', true),
            $this->createWidget('disabled', false),
            $this->createWidget('second', true),
        ]);

        $identifiers = array_map(
            static fn(WidgetInterface $widget): string => $widget->getIdentifier(),
            $subject->getEnabledWidgets(),
        );

        self::assertSame(['first', 'second'], $identifiers);
    }

    #[Test]
    public function findEnabledWidgetReturnsNullForDisabledOrUnknownWidgets(): void
    {
        $subject = $this->createSubject([
            $this->createWidget('enabled', true),
            $this->createWidget('disabled', false),
        ]);

        self::assertSame('enabled', $subject->findEnabledWidget('enabled')?->getIdentifier());
        self::assertNull($subject->findEnabledWidget('disabled'));
        self::assertNull($subject->findEnabledWidget('unknown'));
    }

    #[Test]
    public function widgetViewCatchesExceptionsOfFailingWidgets(): void
    {
        $widget = $this->createWidget('failing', true, new \RuntimeException('API down'));

        $view = WidgetView::fromWidget($widget);

        self::assertFalse($view->data->available);
        self::assertSame('API down', $view->data->errorMessage);
        self::assertSame('Widget/Failing', $view->partial);
        self::assertFalse($view->refreshable);
    }

    #[Test]
    public function widgetViewDefersNotReadyWidgetsOnlyIfAllowed(): void
    {
        $widget = new class () extends AbstractWidget implements DeferrableWidgetInterface {
            public int $calls = 0;

            public function getIdentifier(): string
            {
                return 'slow';
            }

            public function getTitle(): string
            {
                return 'Slow';
            }

            public function getIconIdentifier(): string
            {
                return 'actions-link';
            }

            public function isEnabled(DashboardExtConf $extConf): bool
            {
                return true;
            }

            public function isReady(): bool
            {
                return false;
            }

            public function getData(): WidgetData
            {
                $this->calls++;

                return new WidgetData('slow', true, []);
            }
        };

        $deferredView = WidgetView::fromWidget($widget, true);
        self::assertTrue($deferredView->deferred);
        self::assertSame(0, $widget->calls);

        $directView = WidgetView::fromWidget($widget);
        self::assertFalse($directView->deferred);
        self::assertTrue($directView->data->available);
        self::assertSame(1, $widget->calls);
    }

    /**
     * @param list<AbstractWidget> $widgets
     */
    private function createSubject(array $widgets): WidgetRegistry
    {
        $extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $extensionConfiguration->method('get')->willReturn([]);

        return new WidgetRegistry($widgets, new ExtensionConfigurationManager($extensionConfiguration));
    }

    private function createWidget(string $identifier, bool $enabled, ?\Throwable $exception = null): AbstractWidget
    {
        return new class ($identifier, $enabled, $exception) extends AbstractWidget {
            public function __construct(
                private readonly string $identifier,
                private readonly bool $enabled,
                private readonly ?\Throwable $exception,
            ) {}

            public function getIdentifier(): string
            {
                return $this->identifier;
            }

            public function getTitle(): string
            {
                return $this->identifier;
            }

            public function getIconIdentifier(): string
            {
                return 'actions-link';
            }

            public function isEnabled(DashboardExtConf $extConf): bool
            {
                return $this->enabled;
            }

            public function getData(): WidgetData
            {
                if ($this->exception instanceof \Throwable) {
                    throw $this->exception;
                }

                return new WidgetData($this->identifier, true, []);
            }
        };
    }
}
