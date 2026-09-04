<?php

namespace App\Services\Circulation;

use Carbon\CarbonImmutable;

/**
 * Immutable result of resolving a loan term for a copy.
 *
 * @property-read int $hours
 * @property-read CarbonImmutable $dueAt
 * @property-read array<string, mixed> $policySnapshot
 */
final class LoanTerms
{
    /** @param  array<string, mixed>  $policySnapshot */
    public function __construct(
        public readonly int $hours,
        public readonly CarbonImmutable $dueAt,
        public readonly array $policySnapshot,
    ) {}

    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        return $this->policySnapshot;
    }
}
