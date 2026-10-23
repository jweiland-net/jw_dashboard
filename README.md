# JW Dashboard — `EXT:jw_dashboard`

A configurable TYPO3 14 LTS backend module that gives editors an at-a-glance
overview of their project's health, version status, news, and contract
information — all powered by jweiland.net tooling.

---

## Features

| Widget | Description |
|---|---|
| **TYPO3 Version** | Installed vs. latest version, highlights security updates |
| **TYPO3 News** | Latest entries from the official TYPO3 news feed |
| **Maintenance Contract** | Contract records with renewal warnings |
| **Quick Links** | Configurable shortcuts to backend modules or external URLs |
| **Site Health** | Pages, files, broken links, scheduled task counts |
| **Installed Extensions** | List of active third-party extensions |
| **Editor Guide** | Configurable welcome message and agency contact card |
| **Content Activity** | Weekly column chart of record changes with KPIs |
| **Recently Changed** | Latest edited pages and content elements |
| **Storage** | FAL storage usage by file type |

Every widget can be toggled individually via extension settings.

### TYPO3 core dashboard widget

If the system extension `dashboard` is installed, the widget **jweiland.net Support**
(group *jweiland.net*) is available in *Dashboard › Add widget*. It shows the agency
contact, help links and the system status in the layout of a classic agency support
widget and links to the JW Dashboard module. It has no data of its own: everything is
read from the configured JW Dashboard widgets (see `Specification/Widgets/CoreDashboard.rst`).

---

## Requirements

- TYPO3 **14.0.0+**
- PHP **8.2+**

---

## Installation

```bash
composer require jweiland/jw-dashboard
vendor/bin/typo3 extension:activate jw_dashboard
vendor/bin/typo3 database:updateschema
```

---

## Configuration

Open **Admin Tools → Settings → Extension Configuration → jw_dashboard**.

All settings are documented in `Specification/Configuration/ExtensionSettings.rst`.

---

## Usage

JW Dashboard is a standalone main module directly below the TYPO3 Dashboard
(no page tree). Maintenance contracts and contact persons are global records,
created by admins on the root level (page id 0) via **Content → Records** or the
**Manage contracts** link in the Maintenance Contract widget.

Demo contracts and contact persons: `vendor/bin/typo3 jwdashboard:sample-data`
(remove with `--remove`).

Styles (`Resources/Public/Css/dashboard.css`) and JavaScript
(`Resources/Public/JavaScript/dashboard.js`, native ES module) are shipped
ready to use, no build step is required.

---

## Documentation

Full documentation lives in `Specification/`. The entry point is
`Specification/Index.rst`.

---

## Tests

```bash
composer install

# Unit tests
composer test:unit

# Functional tests (real TYPO3 instance incl. EXT:dashboard), e.g. with SQLite
typo3DatabaseDriver=pdo_sqlite composer test:functional
```

The phpunit configurations live in `Build/phpunit/`. Functional tests also run against
MySQL/MariaDB via the usual `typo3DatabaseHost`, `typo3DatabaseName`, `typo3DatabaseUsername`
and `typo3DatabasePassword` environment variables.

---

## License

GPL-2.0-or-later — see `LICENSE.txt`.
