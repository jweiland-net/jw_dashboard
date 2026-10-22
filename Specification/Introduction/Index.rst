.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

JW Dashboard is a configurable TYPO3 backend module built for all jweiland.net
projects. It is registered as standalone main module (directly below the
TYPO3 Dashboard, without page tree), so backend users see relevant project
information without needing to navigate through multiple modules.

What Does It Do?
================

JW Dashboard provides a widget-based overview page in the TYPO3 backend with
the following information panels:

.. rst-class:: bignums

1. **TYPO3 Version** — Shows the installed vs. latest available version of the
   same major version and highlights pending (security) updates.

2. **TYPO3 News** — Pulls the latest entries from the official TYPO3 news feed so
   editors stay informed about the CMS ecosystem.

3. **Maintenance Contract** — Displays active contracts from the built-in
   contract records, with renewal warnings and a configurable sign-up prompt.

4. **Quick Links** — Configurable shortcut links to internal backend modules
   and external project resources.

5. **Site Health** — At-a-glance metrics: published pages, hidden pages, file
   count, broken links (EXT:linkvalidator), and active scheduler tasks
   (EXT:scheduler).

6. **Installed Extensions** — Lists active third-party extensions for review.

7. **Editor Guide** — Configurable welcome message and agency contact card —
   ideal for onboarding new editors.

8. **Content Activity** — Weekly column chart of record changes with KPIs.

9. **Recently Changed** — Latest edited pages and content elements with
   direct edit links.

10. **Storage** — FAL storage usage by file type.

All widgets can be individually disabled in the extension settings, and virtually
every value (URLs, cache TTLs, branding, quick links) is configurable.

Screenshots
===========

*(Add screenshots of the dashboard here after installation.)*
