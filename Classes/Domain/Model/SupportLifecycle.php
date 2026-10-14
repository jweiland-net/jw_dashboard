<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jw-dashboard.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\JwDashboard\Domain\Model;

/**
 * Support timeline of a TYPO3 major version: release -> regular maintenance -> ELTS
 */
final readonly class SupportLifecycle
{
    public const PHASE_REGULAR = 'regular';
    public const PHASE_ELTS = 'elts';
    public const PHASE_UNSUPPORTED = 'unsupported';

    public function __construct(
        public int $releaseDate,
        public int $maintainedUntil,
        public int $eltsUntil,
    ) {}

    /**
     * @param array<mixed> $data Response of get.typo3.org/api/v1/major/<major> or a cached toArray() result
     */
    public static function fromArray(array $data): ?self
    {
        $releaseDate = self::toTimestamp($data['release_date'] ?? $data['releaseDate'] ?? null);
        $maintainedUntil = self::toTimestamp($data['maintained_until'] ?? $data['maintainedUntil'] ?? null);
        $eltsUntil = self::toTimestamp($data['elts_until'] ?? $data['eltsUntil'] ?? null);

        if ($releaseDate === 0 || $maintainedUntil <= $releaseDate) {
            return null;
        }

        return new self($releaseDate, $maintainedUntil, max($eltsUntil, $maintainedUntil));
    }

    public function getPhase(\DateTimeImmutable $today): string
    {
        $now = $today->getTimestamp();

        return match (true) {
            $now <= $this->maintainedUntil => self::PHASE_REGULAR,
            $now <= $this->eltsUntil => self::PHASE_ELTS,
            default => self::PHASE_UNSUPPORTED,
        };
    }

    /**
     * Days until the end of the current phase (0 when unsupported)
     */
    public function getDaysLeftInPhase(\DateTimeImmutable $today): int
    {
        $end = match ($this->getPhase($today)) {
            self::PHASE_REGULAR => $this->maintainedUntil,
            self::PHASE_ELTS => $this->eltsUntil,
            default => $today->getTimestamp(),
        };

        return (int)floor(max(0, $end - $today->getTimestamp()) / 86400);
    }

    /**
     * Position of a timestamp on the whole timeline (release = 0, end of ELTS = 100)
     */
    public function getPosition(int $timestamp): float
    {
        $span = $this->eltsUntil - $this->releaseDate;
        if ($span <= 0) {
            return 100.0;
        }

        return round(min(100, max(0, ($timestamp - $this->releaseDate) / $span * 100)), 2);
    }

    /**
     * @return array{
     *     releaseDate: int,
     *     maintainedUntil: int,
     *     eltsUntil: int,
     *     phase: string,
     *     daysLeft: int,
     *     yearsLeft: int,
     *     monthsLeft: int,
     *     todayPosition: float,
     *     regularShare: float,
     *     hasElts: bool,
     * }
     */
    public function toArray(\DateTimeImmutable $today): array
    {
        $daysLeft = $this->getDaysLeftInPhase($today);
        $remaining = $today->diff($today->modify('+' . $daysLeft . ' days'));

        return [
            'releaseDate' => $this->releaseDate,
            'maintainedUntil' => $this->maintainedUntil,
            'eltsUntil' => $this->eltsUntil,
            'phase' => $this->getPhase($today),
            'daysLeft' => $daysLeft,
            'yearsLeft' => $remaining->y,
            'monthsLeft' => $remaining->m,
            'todayPosition' => $this->getPosition($today->getTimestamp()),
            'regularShare' => $this->getPosition($this->maintainedUntil),
            'hasElts' => $this->eltsUntil > $this->maintainedUntil,
        ];
    }

    private static function toTimestamp(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && $value !== '' ? (int)strtotime($value) : 0;
    }
}
