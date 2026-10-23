.. include:: /Includes.rst.txt

.. _widget-core-dashboard:

====================================
Core dashboard: jweiland.net Support
====================================

:Class: :php:`JWeiland\JwDashboard\CoreDashboard\SupportWidget`
:Identifier: ``jwDashboardSupport`` (group ``jweiland``)
:Size: width large, height large
:Refresh button: yes
:Requires: system extension ``dashboard`` (optional, see ``suggest``)

Widget for the TYPO3 core dashboard (:guilabel:`Dashboard` module). It is
registered in :file:`Configuration/Services.php` only if EXT:dashboard is
installed, so the extension keeps working without it.

The widget has no data of its own. All content is read from the payload of
the JW Dashboard widgets, so both dashboards always show the same information.

Layout
======

The layout reacts to the width of the widget (container queries), not to the
browser window: one column in narrow widgets, contact and agency side by side
from about 720 px.

=========================  ===============================================  =========================
Section                    Source                                           Respects widget toggle
=========================  ===============================================  =========================
System status (top)        :ref:`widget-typo3-version` and                  yes
                           :ref:`widget-maintenance-contract`
Contact card               Primary contact person, falling back to the      no
                           agency team (:confval:`agencyPhone`,
                           :confval:`agencyEmail`)
Agency card                :ref:`widget-editor-guide` (agency settings,     no
                           :confval:`welcomeMessage`, logo) and
                           :confval:`customQuickLinks` as "Help & tools"
Footer                     Legal settings (imprint, privacy, terms,         no
                           newsletter) and the module button
=========================  ===============================================  =========================

System status
-------------

Built by :php:`JWeiland\JwDashboard\CoreDashboard\Status\SupportStatusBuilder`:

*   **All good** (TYPO3 up to date, no contract needs attention): one compact
    green line "System up to date" with the installed version, the end of the
    free support (or ELTS) and the number of active contracts.
*   Otherwise one row per topic, each with one action:

    ==============================  ========  ===================================
    State                           Severity  Action
    ==============================  ========  ===================================
    Version check failed            info      none
    Major version unsupported       danger    Release info
    Security update available       danger    Release info
    Update available                warning   Release info
    Contracts expiring or expired   warning   Review (contract records for
                                              admins, else the JW Dashboard)
    No contract (signup prompt)     info      Learn more
    ==============================  ========  ===================================

    The contract row lists up to three affected contracts
    ("Hosting ends in 12 days · Support expired").

Contact card
------------

Built by :php:`JWeiland\JwDashboard\CoreDashboard\SupportContact`. With a
primary contact person: photo or initials, name, role, :guilabel:`Call` and
:guilabel:`Write e-mail` buttons, the direct line and the agency hotline (only
if it differs). Without one, the agency team is the contact.

Footer
------

Privacy, terms and newsletter links. Representative, seat and register fold out
of :guilabel:`Imprint` (native ``details`` element, no JavaScript). Without
those details, :guilabel:`Imprint` is a plain link to :confval:`imprintUrl`.

The button :guilabel:`Open JW Dashboard` opens the JW Dashboard module via the
module menu (``TYPO3.ModuleMenu.showModule``). It is only rendered if the
current backend user has access to the module.

Tests
=====

*   Unit: :file:`Tests/Unit/CoreDashboard/` (status builder, contact, widget
    view data, labels in English and German).
*   Functional: :file:`Tests/Functional/CoreDashboard/SupportWidgetTest.php`
    renders the widget in a real TYPO3 instance with EXT:dashboard.

Widget group and dashboard preset
=================================

*   Widget group ``jweiland`` (:file:`Configuration/Backend/DashboardWidgetGroups.php`):
    tab :guilabel:`jweiland.net` in :guilabel:`Add widget`.
*   Dashboard preset ``jweiland`` (:file:`Configuration/Backend/DashboardPresets.php`):
    :guilabel:`Create dashboard › jweiland.net` creates a dashboard that already
    contains the widget.

Both are read from the code cache, so run ``typo3 cache:flush`` (or
:guilabel:`Admin Tools › Maintenance › Flush TYPO3 and PHP Cache`) after changes.
