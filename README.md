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
.Build/bin/phpunit -c .Build/vendor/typo3/testing-framework/Resources/Core/Build/UnitTests.xml Tests/Unit
```

---

## License

GPL-2.0-or-later — see `LICENSE.txt`.
