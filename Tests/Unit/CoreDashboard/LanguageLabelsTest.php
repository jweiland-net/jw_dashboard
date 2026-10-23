<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Tests\Unit\CoreDashboard;

use JWeiland\JwDashboard\CoreDashboard\Status\SupportStatusBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Labels of the core dashboard widget are referenced dynamically (LLL keys built in PHP), so a typo
 * would only show up as an empty string in the backend. These tests catch that early.
 */
final class LanguageLabelsTest extends TestCase
{
    private const EXTENSION_PATH = __DIR__ . '/../../../';

    private const LABEL_FILE = 'Resources/Private/Language/locallang.xlf';

    private const GERMAN_LABEL_FILE = 'Resources/Private/Language/de.locallang.xlf';

    /**
     * @return \Generator<string, array{string}>
     */
    public static function usedLabelKeyDataProvider(): \Generator
    {
        foreach (self::getTemplateKeys() as $key) {
            yield 'template: ' . $key => [$key];
        }
        foreach (self::getStatusBuilderKeys() as $key) {
            yield 'status builder: ' . $key => [$key];
        }
    }

    #[Test]
    #[DataProvider('usedLabelKeyDataProvider')]
    public function usedLabelExistsInEnglishAndGerman(string $key): void
    {
        self::assertArrayHasKey($key, $this->readLabels(self::LABEL_FILE), 'Missing in ' . self::LABEL_FILE);
        self::assertArrayHasKey($key, $this->readLabels(self::GERMAN_LABEL_FILE), 'Missing in ' . self::GERMAN_LABEL_FILE);
    }

    #[Test]
    public function everyCoreWidgetLabelIsTranslatedToGerman(): void
    {
        $english = array_filter(
            $this->readLabels(self::LABEL_FILE),
            static fn(string $key): bool => str_starts_with($key, 'coreWidget.'),
            ARRAY_FILTER_USE_KEY,
        );
        $german = $this->readLabels(self::GERMAN_LABEL_FILE);

        foreach ($english as $key => $source) {
            self::assertArrayHasKey($key, $german, 'Missing German label ' . $key);
            self::assertNotSame('', $german[$key], 'Empty German label ' . $key);
        }
    }

    #[Test]
    public function placeholdersOfGermanLabelsMatchEnglishSource(): void
    {
        $english = $this->readLabels(self::LABEL_FILE);
        foreach ($this->readLabels(self::GERMAN_LABEL_FILE) as $key => $target) {
            if (!str_starts_with($key, 'coreWidget.') || !isset($english[$key])) {
                continue;
            }
            self::assertSame($this->getPlaceholders($english[$key]), $this->getPlaceholders($target), 'Placeholders differ in ' . $key);
        }
    }

    /**
     * @return array<string, string> label key => target (German file) or source (English file)
     */
    private function readLabels(string $file): array
    {
        $xml = simplexml_load_file(self::EXTENSION_PATH . $file);
        self::assertNotFalse($xml, 'Unable to parse ' . $file);

        $labels = [];
        foreach ($xml->file->body->{'trans-unit'} as $unit) {
            $labels[(string)$unit['id']] = isset($unit->target) ? (string)$unit->target : (string)$unit->source;
        }

        return $labels;
    }

    /**
     * @return list<string>
     */
    private static function getTemplateKeys(): array
    {
        $files = [
            ...glob(self::EXTENSION_PATH . 'Resources/Private/Templates/CoreWidget/*.html') ?: [],
            ...glob(self::EXTENSION_PATH . 'Resources/Private/Partials/CoreWidget/*.html') ?: [],
        ];

        $keys = [];
        foreach ($files as $file) {
            preg_match_all('/jw_dashboard\.messages:([A-Za-z0-9.]+)/', (string)file_get_contents($file), $matches);
            $keys = [...$keys, ...$matches[1]];
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return list<string>
     */
    private static function getStatusBuilderKeys(): array
    {
        $source = (string)file_get_contents(self::EXTENSION_PATH . 'Classes/CoreDashboard/Status/SupportStatusBuilder.php');

        preg_match_all("/LABEL_PREFIX \\. \\(?'([A-Za-z0-9.]+)'/", $source, $prefixed);
        preg_match_all("/\\? '([A-Za-z0-9.]+)' : '([A-Za-z0-9.]+)'/", $source, $ternaries);
        preg_match_all("/'((?:widget)\\.[A-Za-z0-9.]+)'/", $source, $plain);

        $keys = array_map(
            static fn(string $key): string => SupportStatusBuilder::LABEL_PREFIX . $key,
            [...$prefixed[1], ...$ternaries[1], ...$ternaries[2]],
        );

        return array_values(array_unique([...$keys, ...$plain[1]]));
    }

    /**
     * @return list<string>
     */
    private function getPlaceholders(string $label): array
    {
        preg_match_all('/%(?:\d+\$)?[sd]/', $label, $matches);
        sort($matches[0]);

        return $matches[0];
    }
}
