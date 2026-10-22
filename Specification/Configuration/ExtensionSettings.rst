.. include:: /Includes.rst.txt

.. _extension-settings:

==================
Extension Settings
==================

All settings are managed in :guilabel:`Admin Tools > Settings > Extension Configuration > jw_dashboard`.

Widgets
=======

.. confval-menu::
   :display: table
   :type:
   :Default:

   .. confval:: enableWidgetTypo3Version
      :type: boolean
      :Default: true

      Show or hide the TYPO3 Version widget.

   .. confval:: enableWidgetTypo3News
      :type: boolean
      :Default: true

      Show or hide the TYPO3 News widget.

   .. confval:: enableWidgetMaintenanceContract
      :type: boolean
      :Default: true

      Show or hide the Maintenance Contract widget.

   .. confval:: enableWidgetQuickLinks
      :type: boolean
      :Default: true

      Show or hide the Quick Links widget.

   .. confval:: enableWidgetSiteHealth
      :type: boolean
      :Default: true

      Show or hide the Site Health widget.

   .. confval:: enableWidgetExtensionUpdates
      :type: boolean
      :Default: true

      Show or hide the Extension Updates widget.

   .. confval:: enableWidgetEditorGuide
      :type: boolean
      :Default: true

      Show or hide the Editor Guide widget.

   .. confval:: enableWidgetContentActivity
      :type: boolean
      :Default: true

      Show or hide the Content Activity widget.

   .. confval:: enableWidgetRecentChanges
      :type: boolean
      :Default: true

      Show or hide the Recently Changed widget.

   .. confval:: enableWidgetStorage
      :type: boolean
      :Default: true

      Show or hide the Storage widget.

   .. confval:: activityWeeks
      :type: integer
      :Default: 12

      Number of calendar weeks shown in the Content Activity chart (1-52).

   .. confval:: recentChangesCount
      :type: integer
      :Default: 8

      Number of records listed in the Recently Changed widget (1-25).

API / Data Sources
==================

.. confval-menu::
   :display: table

   .. confval:: typo3VersionApiUrl
      :type: string
      :Default: https://get.typo3.org/api/v1/

      Base URL of the get.typo3.org API. The latest release of the installed
      major version is requested from ``<url>major/<major>/release/latest``,
      e.g. ``https://get.typo3.org/api/v1/major/14/release/latest``.

   .. confval:: typo3NewsFeedUrl
      :type: string
      :Default: https://news.typo3.com/rss

      RSS feed URL for TYPO3 news.

   .. confval:: newsCacheTtl
      :type: integer
      :Default: 3600

      Cache lifetime in seconds for the news feed response.

   .. confval:: versionCacheTtl
      :type: integer
      :Default: 86400

      Cache lifetime in seconds for the version API response.

   .. confval:: newsItemCount
      :type: integer
      :Default: 5

      Number of news items to display.

   .. confval:: requestTimeout
      :type: integer
      :Default: 10

      HTTP request timeout in seconds for all external API calls.

Branding
========

.. confval-menu::
   :display: table

   .. confval:: agencyName
      :type: string
      :Default: jweiland.net

      Agency name displayed in the Editor Guide widget.

   .. confval:: agencyEmail
      :type: string
      :Default: projects@jweiland.net

      Contact e-mail displayed in the Editor Guide widget.

   .. confval:: agencyPhone
      :type: string
      :Default: +49 711 94969-60

      Contact phone number displayed in the Editor Guide widget.

   .. confval:: agencyWebsite
      :type: string
      :Default: https://jweiland.net

      Agency website URL displayed in the Editor Guide widget.

   .. confval:: welcomeMessage
      :type: string (HTML)
      :Default: (empty)

      Optional welcome or onboarding message shown at the top of the Editor
      Guide widget. HTML is allowed.

   .. confval:: showAgencyLogo
      :type: boolean
      :Default: true

      Toggle visibility of the agency logo and contact card.

Quick Links
===========

.. confval:: customQuickLinks
   :type: text
   :Default: (empty)

   One quick link per line, in the format::

      Label|https://url.example.com|icon-identifier

   The icon identifier must be a registered TYPO3 backend icon (see
   :ref:`t3coreapi:icon`). If the icon column is omitted or the icon is not
   registered, ``actions-link`` is used as a fallback.

   Only ``http``, ``https``, ``mailto`` and site-relative URLs (starting with
   ``/``) are accepted, other lines are skipped. External URLs open in a new
   window.

Maintenance Contracts
=====================

.. confval-menu::
   :display: table

   .. confval:: contractRenewalWarningDays
      :type: integer
      :Default: 30

      Number of days before a contract end date at which a renewal warning is
      displayed. Used as fallback for contract records with an empty
      :guilabel:`Renewal Warning (Days)` field.

   .. confval:: showContractSignupPrompt
      :type: boolean
      :Default: true

      Show a call-to-action sign-up link when no contract records exist.

   .. confval:: contractSignupUrl
      :type: string
      :Default: https://jweiland.net/typo3-wartungsvertrag.html

      URL of the contract sign-up page linked from the prompt.
