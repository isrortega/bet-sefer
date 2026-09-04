<?php

namespace App\Actions\Circulation;

use App\Enums\CopyStatus;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\LoanPolicy;
use App\Models\User;
use App\Services\Circulation\CopyStateMachine;
use App\Services\Circulation\LoanPolicyResolver;
use App\Support\CrockfordCode;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CheckoutCopyAction
{
    public function __construct(
        private readonly LoanPolicyResolver $resolver = new LoanPolicyResolver,
        private readonly CopyStateMachine $machine = new CopyStateMachine,
    ) {}

    public function handle(Copy $copy, User $reader, User $actor, ?int $requestedHours = null): Loan
    {
        $this->assertReaderEligible($reader);
        $this->assertCopyAvailable($copy);

        try {
            return \DB::transaction(function () use ($copy, $reader, $actor, $requestedHours) {
                $locked = Copy::query()->whereKey($copy->id)->lockForUpdate()->firstOrFail();
                $this->assertCopyAvailable($locked);

                $terms = $this->resolver->resolve($locked, $requestedHours);

                $loan = Loan::create([
                    'ulid' => (string) Str::ulid(),
                    'code' => CrockfordCode::withPrefix('LN'),
                    'copy_id' => $locked->id,
                    'user_id' => $reader->id,
                    'checked_out_by_id' => $actor->id,
                    'checked_out_at' => now(),
                    'due_at' => $terms->dueAt,
                    'renewals_count' => 0,
                    'policy_snapshot' => $terms->snapshot(),
                ]);

                $this->machine->transition($locked, CopyStatus::OnLoan, $actor, ['loan_id' => $loan->id]);

                activity()
                    ->performedOn($loan)
                    ->causedBy($actor)
                    ->withProperties(['reader' => $reader->email, 'copy' => $locked->code, 'hours' => $terms->hours])
                    ->log('loan_created');

                return $loan;
            });
        } catch (Throwable $e) {
            if ($this->isUniqueViolation($e)) {
                throw ValidationException::withMessages([
                    'code' => 'This copy was just checked out by someone else.',
                ]);
            }

            throw $e;
        }
    }

    private function assertReaderEligible(User $reader): void
    {
        if (! $reader->isActive()) {
            throw ValidationException::withMessages([
                'code' => 'Reader identity has not been verified yet.',
            ]);
        }

        if ($reader->blocked_until !== null && $reader->blocked_until->isFuture()) {
            throw ValidationException::withMessages(['code' => 'This reader is blocked from borrowing.']);
        }

        $max = LoanPolicy::query()->where('loan_type', 'general')->value('max_active_loans_per_user') ?? 5;
        if ($reader->loans()->whereNull('returned_at')->count() >= $max) {
            throw ValidationException::withMessages(['code' => 'This reader has reached the loan limit.']);
        }

        if ($reader->loans()->whereNull('returned_at')->where('due_at', '<', now())->exists()) {
            throw ValidationException::withMessages(['code' => 'This reader has an overdue loan.']);
        }
    }

    private function assertCopyAvailable(Copy $copy): void
    {
        if ($copy->isLoanRestricted()) {
            throw ValidationException::withMessages(['code' => 'This copy is not for loan.']);
        }

        if ($copy->status !== CopyStatus::Available) {
            throw ValidationException::withMessages(['code' => 'This copy is not available right now.']);
        }
    }

    private function isUniqueViolation(Throwable $e): bool
    {
        return $e instanceof UniqueConstraintViolationException;
    }
}
