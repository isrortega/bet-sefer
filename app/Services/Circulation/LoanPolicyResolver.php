<?php

namespace App\Services\Circulation;

use App\Enums\LoanType;
use App\Exceptions\LoanTermsOutOfRange;
use App\Models\Copy;
use App\Models\LoanPolicy;
use Carbon\CarbonImmutable;

/**
 * Resolves how long a copy may be borrowed and when it is due.
 *
 * All date maths for loan terms lives here (plus BusinessCalendar). Callers
 * never re-compute due dates.
 */
final class LoanPolicyResolver
{
    public function __construct(private readonly BusinessCalendar $calendar = new BusinessCalendar) {}

    public function resolve(Copy $copy, ?int $requestedHours = null): LoanTerms
    {
        $policy = $this->policyFor($copy);

        [$min, $max, $default] = $this->applyFactor($policy, $copy->edition->special_material);

        if ($requestedHours !== null && ($requestedHours < $min || $requestedHours > $max)) {
            throw new LoanTermsOutOfRange(
                "Requested {$requestedHours}h is outside the allowed {$min}h-{$max}h range for this material."
            );
        }

        $hours = $requestedHours ?? $default;

        $dueAt = $this->calendar->nextOpeningMoment(
            CarbonImmutable::now()->addHours($hours)
        );

        return new LoanTerms(
            hours: $hours,
            dueAt: $dueAt,
            policySnapshot: [
                'loan_type' => $policy->loan_type instanceof LoanType ? $policy->loan_type->value : (string) $policy->loan_type,
                'hours' => $hours,
                'min_hours' => $min,
                'max_hours' => $max,
                'default_hours' => $default,
                'renewals_allowed' => $policy->renewals_allowed,
                'special_material_factor' => $policy->special_material_factor,
                'grace_hours' => $policy->grace_hours,
                'daily_fine_amount' => $policy->daily_fine_amount,
                'max_active_loans_per_user' => $policy->max_active_loans_per_user,
            ],
        );
    }

    private function policyFor(Copy $copy): LoanPolicy
    {
        $type = $copy->edition->loan_type instanceof LoanType
            ? $copy->edition->loan_type
            : LoanType::from((string) $copy->edition->loan_type);

        return LoanPolicy::query()
            ->where('loan_type', $type)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * @return array{0: int, 1: int, 2: int} [min, max, default] after applying the
     *                                       special-material factor (rounded down, min 1h)
     */
    private function applyFactor(LoanPolicy $policy, bool $specialMaterial): array
    {
        if (! $specialMaterial) {
            return [$policy->min_hours, $policy->max_hours, $policy->default_hours];
        }

        $factor = max(0.0, (float) $policy->special_material_factor);

        return [
            max(1, intdiv((int) floor($policy->min_hours * $factor), 1)),
            max(1, intdiv((int) floor($policy->max_hours * $factor), 1)),
            max(1, intdiv((int) floor($policy->default_hours * $factor), 1)),
        ];
    }
}
