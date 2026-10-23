# TYPO3 Extension `jw_dashboard`

[![Packagist][packagist-logo-stable]][extension-packagist-url]
[![Latest Stable Version][extension-build-shield]][extension-ter-url]
[![Total Downloads][extension-downloads-badge]][extension-packagist-url]
[![Monthly Downloads][extension-monthly-downloads]][extension-packagist-url]
[![TYPO3 14.3][TYPO3-shield]][TYPO3-14-url]

![Build Status](https://github.com/jweiland-net/jw_dashboard/actions/workflows/ci.yml/badge.svg)

`jw_dashboard` is an extension for TYPO3 CMS. It adds a backend module that gives
editors an overview of their project: TYPO3 version status, news, maintenance
contracts, site health and the contact data of their agency.

## 1 Features

* Configurable widgets: TYPO3 version, TYPO3 news, maintenance contracts, quick links,
  site health, installed extensions, editor guide, content activity, recent changes
  and storage
* "jweiland.net Support" widget for the TYPO3 core dashboard (if EXT:dashboard is installed)

## 2 Usage

### 2.1 Installation

#### Installation using Composer

The recommended way to install the extension is using Composer.

Run the following command within your Composer based TYPO3 project:

```
composer require jweiland/jw-dashboard
```

#### Installation as extension from TYPO3 Extension Repository (TER)

Download and install `jw_dashboard` with the extension manager module.

### 2.2 Minimal setup

1) Update the database schema.
2) Configure the extension in *Admin Tools › Settings › Extension Configuration › jw_dashboard*.
3) Open the *JW Dashboard* module or add the *jweiland.net Support* widget to the TYPO3 dashboard.

## 3 Support

Free Support is available via [GitHub Issue Tracker](https://github.com/jweiland-net/jw_dashboard/issues).

For commercial support, please contact us at [support@jweiland.net](mailto:support@jweiland.net).

<!-- MARKDOWN LINKS & IMAGES -->

[extension-build-shield]: https://poser.pugx.org/jweiland/jw-dashboard/v/stable.svg?style=for-the-badge

[extension-downloads-badge]: https://poser.pugx.org/jweiland/jw-dashboard/d/total.svg?style=for-the-badge

[extension-monthly-downloads]: https://poser.pugx.org/jweiland/jw-dashboard/d/monthly?style=for-the-badge

[extension-ter-url]: https://extensions.typo3.org/extension/jw_dashboard/

[extension-packagist-url]: https://packagist.org/packages/jweiland/jw-dashboard/

[packagist-logo-stable]: https://img.shields.io/badge/--grey.svg?style=for-the-badge&logo=packagist&logoColor=white

[TYPO3-14-url]: https://get.typo3.org/version/14

[TYPO3-shield]: https://img.shields.io/badge/TYPO3-14.3-green.svg?style=for-the-badge&logo=typo3
