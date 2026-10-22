.. include:: /Includes.rst.txt

.. _widget-typo3-version:

=============
TYPO3 Version
=============

:Setting: :confval:`enableWidgetTypo3Version`
:Size: small
:Refresh button: yes

Compares the installed TYPO3 version with the latest release of the same
major version, provided by ``https://get.typo3.org/api/v1/major/<major>/release/latest``.

The widget shows one of these states:

- **Up to date** — the installed version is the latest release.
- **Update available** — a newer bugfix release exists.
- **Security update available** — the latest release is a security release.
- **Check failed** — the API could not be reached. Failed lookups are not cached.

Below the status, a timeline shows the support lifecycle of the installed major
version (``<url>major/<major>``): release, end of free (regular) support and
end of Extended Long Term Support (ELTS), with a marker for today and the
remaining time of the current phase.

The widget is loaded asynchronously when no cached data exists, so the
dashboard never waits for the external API.

Settings: :confval:`typo3VersionApiUrl`, :confval:`versionCacheTtl`,
:confval:`requestTimeout`.
