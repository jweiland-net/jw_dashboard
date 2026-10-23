<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\CoreDashboard;

/**
 * Type-safe access to the untyped payload arrays of the JW Dashboard widgets
 */
final class Payload
{
    /**
     * @param array<mixed> $payload
     */
    public static function string(array $payload, string $key, string $default = ''): string
    {
        $value = $payload[$key] ?? $default;

        return is_scalar($value) ? (string)$value : $default;
    }

    /**
     * @param array<mixed> $payload
     */
    public static function int(array $payload, string $key, int $default = 0): int
    {
        $value = $payload[$key] ?? $default;

        return is_numeric($value) ? (int)$value : $default;
    }

    /**
     * Nested arrays of the payload, e.g. the list of contracts or links
     *
     * @param array<mixed> $payload
     * @return list<array<string, mixed>>
     */
    public static function listOfArrays(array $payload, string $key): array
    {
        $list = [];
        foreach (is_array($payload[$key] ?? null) ? $payload[$key] : [] as $entry) {
            if (is_array($entry)) {
                /** @var array<string, mixed> $entry */
                $list[] = $entry;
            }
        }

        return $list;
    }
}
