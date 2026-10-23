<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */


namespace JWeiland\JwDashboard\CoreDashboard\Status;

/**
 * Result of the SupportStatusBuilder: rows that need the editor's attention and,
 * if there are none, the facts shown in the compact "System up to date" strip.
 */
final readonly class SupportStatus
{
    /**
     * @param list<StatusItem> $items
     * @param list<array{key: string, arguments: array<int, string|int>}> $facts
     */
    public function __construct(
        public array $items = [],
        public array $facts = [],
    ) {}

    public function getSeverity(): StatusSeverity
    {
        $severity = StatusSeverity::Success;
        foreach ($this->items as $item) {
            if ($item->severity->getWeight() > $severity->getWeight()) {
                $severity = $item->severity;
            }
        }

        return $severity;
    }

    public function getState(): string
    {
        return $this->getSeverity()->value;
    }

    public function getAttentionCount(): int
    {
        return count(array_filter(
            $this->items,
            static fn(StatusItem $item): bool => $item->severity->needsAttention(),
        ));
    }

    public function isAllGood(): bool
    {
        return $this->items === [] && $this->facts !== [];
    }

    public function isEmpty(): bool
    {
        return $this->items === [] && $this->facts === [];
    }
}
