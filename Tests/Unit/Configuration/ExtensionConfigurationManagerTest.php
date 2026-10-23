<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Tests\Unit\Configuration;

use JWeiland\JwDashboard\Configuration\DashboardExtConf;
use JWeiland\JwDashboard\Configuration\ExtensionConfigurationManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

final class ExtensionConfigurationManagerTest extends TestCase
{
    private MockObject&ExtensionConfiguration $extensionConfiguration;
    private ExtensionConfigurationManager $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extensionConfiguration = $this->createMock(ExtensionConfiguration::class);
        $this->subject = new ExtensionConfigurationManager($this->extensionConfiguration);
    }

    #[Test]
    public function getReturnsDashboardExtConfInstance(): void
    {
        $this->extensionConfiguration->method('get')->with('jw_dashboard')->willReturn([]);

        $result = $this->subject->get();

        self::assertInstanceOf(DashboardExtConf::class, $result);
    }

    #[Test]
    public function getReturnsCachedInstanceOnSubsequentCalls(): void
    {
        $this->extensionConfiguration->expects(self::once())->method('get')->willReturn([]);

        $first = $this->subject->get();
        $second = $this->subject->get();

        self::assertSame($first, $second);
    }

    #[Test]
    public function getUsesDefaultsWhenRawConfigIsEmpty(): void
    {
        $this->extensionConfiguration->method('get')->willReturn([]);

        $extConf = $this->subject->get();

        self::assertTrue($extConf->enableWidgetTypo3Version);
        self::assertTrue($extConf->enableWidgetTypo3News);
        self::assertSame('jweiland.net', $extConf->agencyName);
        self::assertSame('https://jweiland.net/impressum.html', $extConf->imprintUrl);
        self::assertSame(5, $extConf->newsItemCount);
        self::assertSame(30, $extConf->contractRenewalWarningDays);
    }

    #[Test]
    public function getOverridesDefaultsWithConfiguredValues(): void
    {
        $this->extensionConfiguration->method('get')->willReturn([
            'enableWidgetTypo3News' => '0',
            'agencyName' => 'My Agency',
            'newsItemCount' => '10',
        ]);

        $extConf = $this->subject->get();

        self::assertFalse($extConf->enableWidgetTypo3News);
        self::assertSame('My Agency', $extConf->agencyName);
        self::assertSame(10, $extConf->newsItemCount);
    }

    #[Test]
    public function getParsedQuickLinksReturnsParsedArray(): void
    {
        $this->extensionConfiguration->method('get')->willReturn([
            'customQuickLinks' => "Docs|https://docs.typo3.org|actions-document-view\nNews|https://typo3.org/news|apps-pagetree-page-default",
        ]);

        $extConf = $this->subject->get();
        $links = $extConf->getParsedQuickLinks();

        self::assertCount(2, $links);
        self::assertSame('Docs', $links[0]['label']);
        self::assertSame('https://docs.typo3.org', $links[0]['url']);
        self::assertSame('actions-document-view', $links[0]['icon']);
        self::assertSame('News', $links[1]['label']);
    }

    #[Test]
    public function getParsedQuickLinksUsesDefaultIconWhenMissing(): void
    {
        $this->extensionConfiguration->method('get')->willReturn([
            'customQuickLinks' => 'Link|https://example.com',
        ]);

        $extConf = $this->subject->get();
        $links = $extConf->getParsedQuickLinks();

        self::assertSame('actions-link', $links[0]['icon']);
    }
}
