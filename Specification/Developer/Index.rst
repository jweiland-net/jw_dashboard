.. include:: /Includes.rst.txt

.. _developer:

=========
Developer
=========

This chapter is aimed at developers who want to extend or customise
EXT:jw_dashboard.

Architecture Overview
=====================

The extension follows a layered architecture:

- **Configuration layer** — ``ExtensionConfigurationManager`` hydrates
  a ``DashboardExtConf`` readonly DTO from the TYPO3 extension settings.

- **Service layer** — Services (``Typo3VersionService``, ``NewsService``,
  ``SiteHealthService``, ``ExtensionUpdateService``) perform the actual data
  retrieval and caching.

- **Widget layer** — Each widget implements ``WidgetInterface`` (usually by
  extending ``AbstractWidget``), calls one or more services and wraps the
  result in a ``WidgetData`` DTO. All widgets are collected by the
  ``WidgetRegistry`` (Strategy pattern), which also evaluates the feature
  flags of ``DashboardExtConf``.

- **Controller layer** — ``DashboardController`` renders a card for every
  enabled widget of the registry. ``WidgetRefreshController`` (AJAX route
  ``jw_dashboard_widget_refresh``) re-renders the body of a single widget.

The backend module is a plain (non-Extbase) controller on purpose: Extbase
modules are always attached to the :guilabel:`Content` main module including
the page tree, while JW Dashboard is a standalone main module.

Adding a Custom Widget
======================

1. Create a class extending ``JWeiland\JwDashboard\Widget\AbstractWidget``
   (or implementing ``WidgetInterface``).
2. Implement ``getIdentifier()``, ``getTitle()``, ``getIconIdentifier()``,
   ``isEnabled()`` and ``getData()``. Override ``getSize()`` to change the
   column span (``small``, ``medium``, ``large`` or ``full``).
3. Register the class as service with ``autoconfigure: true`` (default in most
   extensions). The ``jw_dashboard.widget`` tag is added automatically.
   Use ``#[AsTaggedItem(priority: n)]`` to define the position, core widgets
   use priorities between 100 and 40.
4. Add the Fluid partial ``Partials/Widget/<UpperCamelCase identifier>.fluid.html``.
   It receives ``widget`` (``WidgetView``) and ``payload``. Register your
   template folder as backend template override in the global page TSconfig
   (e.g. :file:`Configuration/page.tsconfig`) of your extension:

   .. code-block:: typoscript

      templates.jweiland/jw-dashboard.1700000000 = vendor/my-extension:Resources/Private/JwDashboard

Example:

.. code-block:: php

   namespace Vendor\MyExtension\Widget;

   use JWeiland\JwDashboard\Configuration\DashboardExtConf;
   use JWeiland\JwDashboard\Widget\AbstractWidget;
   use JWeiland\JwDashboard\Widget\WidgetData;
   use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

   #[AsTaggedItem(priority: 45)]
   final class MyCustomWidget extends AbstractWidget
   {
       public function getIdentifier(): string { return 'myCustomWidget'; }
       public function getTitle(): string { return 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:widget.title'; }
       public function getIconIdentifier(): string { return 'actions-star'; }
       public function isEnabled(DashboardExtConf $extConf): bool { return true; }

       public function getData(): WidgetData
       {
           return new WidgetData(
               widgetIdentifier: $this->getIdentifier(),
               available: true,
               payload: ['key' => 'value'],
           );
       }
   }

Refreshable Widgets
===================

Implement ``RefreshableWidgetInterface`` to get a refresh button in the card
header. ``refresh()`` is called before the widget is re-rendered, use it to
discard cached data.

Deferred Widgets
================

Implement ``DeferrableWidgetInterface`` for widgets with slow data sources.
If ``isReady()`` returns ``false`` (e.g. cache miss), the card is rendered with
a placeholder and its body is loaded via the AJAX route afterwards.

Safe Data Pattern
=================

Templates always receive data via ``getSafeData()``. The safe wrapper catches
all ``\Throwable`` exceptions so that a single malfunctioning widget never
crashes the entire dashboard; the card shows a notice instead.

Caches
======

Two dedicated file caches are registered:

- ``jw_dashboard_news`` — for RSS feed responses.
- ``jw_dashboard_version`` — for TYPO3 version API responses.

Failed requests are not cached, so the next page load tries again. Both caches
are flushed by the refresh button of the corresponding widget and by
:guilabel:`Flush all caches`.
