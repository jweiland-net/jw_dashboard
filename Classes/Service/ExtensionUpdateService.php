<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Service;

use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Lists active third-party extensions (everything except TYPO3 system extensions) for review.
 */
class ExtensionUpdateService
{
    public function __construct(
        private readonly PackageManager $packageManager,
    ) {}

    /**
     * @return list<array{key: string, title: string, version: string}>
     */
    public function getThirdPartyExtensions(): array
    {
        $extensions = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            if ($package->getPackageMetaData()->isFrameworkType()) {
                continue;
            }

            $extensions[] = [
                'key' => $package->getPackageKey(),
                'title' => $this->getTitle($package),
                'version' => (string)$package->getPackageMetaData()->getVersion(),
            ];
        }

        usort($extensions, static fn(array $a, array $b): int => strcasecmp($a['key'], $b['key']));

        return $extensions;
    }

    private function getTitle(PackageInterface $package): string
    {
        $metaData = $package->getPackageMetaData();

        return (string)($metaData->getTitle() ?: $metaData->getDescription() ?: $package->getPackageKey());
    }
}
