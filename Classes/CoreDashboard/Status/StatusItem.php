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
 * One row of the status strip. Texts are LLL keys (relative to locallang.xlf) plus sprintf arguments,
 * so the builder stays free of the LanguageService and can be unit tested.
 */
final readonly class StatusItem
{
    /**
     * @param array<int, string|int> $titleArguments
     * @param array<int, string|int> $bodyArguments
     * @param list<array{key: string, arguments: array<int, string|int>}> $details
     */
    public function __construct(
        public string $identifier,
        public StatusSeverity $severity,
        public string $icon,
        public string $title,
        public array $titleArguments = [],
        public string $body = '',
        public array $bodyArguments = [],
        public array $details = [],
        public string $actionUrl = '',
        public string $actionLabel = '',
        public bool $actionExternal = false,
        public string $actionModule = '',
    ) {}

    public function getState(): string
    {
        return $this->severity->value;
    }

    public function hasAction(): bool
    {
        return $this->actionLabel !== '' && ($this->actionUrl !== '' || $this->actionModule !== '');
    }
}
