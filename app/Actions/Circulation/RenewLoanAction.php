<?php

namespace App\Actions\Circulation;

use App\Models\Loan;
use App\Models\User;
use App\Services\Circulation\BusinessCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class RenewLoanAction
{
    public function __construct(private readonly BusinessCalendar $calendar = new BusinessCalendar) {}

    public function handle(Loan $loan, User $actor): Loan
    {
        if ($loan->returned_at !== null) {
            throw ValidationException::withMessages(['code' => 'This loan has already been returned.']);
        }

        if ($loan->isOverdue()) {
            throw ValidationException::withMessages(['code' => 'Overdue loans cannot be renewed.']);
        }

        $snapshot = $loan->policy_snapshot;
        $allowed = (int) ($snapshot['renewals_allowed'] ?? 0);

        if ($loan->renewals_count >= $allowed) {
            throw ValidationException::withMessages(['code' => 'This loan has no renewals left.']);
        }

        return \DB::transaction(function () use ($loan, $actor) {
            $term = (int) ($loan->policy_snapshot['default_hours'] ?? $loan->policy_snapshot['hours'] ?? 0);

            $dueAt = $this->calendar->nextOpeningMoment(
                CarbonImmutable::instance($loan->due_at)->addHours(max(1, $term))
            );

            $loan->forceFill([
                'due_at' => $dueAt,
                'renewals_count' => $loan->renewals_count + 1,
            ])->save();

            activity()
                ->performedOn($loan)
                ->causedBy($actor)
                ->withProperties(['new_due' => $dueAt->toIso8601String()])
                ->log('loan_renewed');

            return $loan;
        });
    }
}
