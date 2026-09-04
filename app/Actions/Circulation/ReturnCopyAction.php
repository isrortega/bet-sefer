<?php

namespace App\Actions\Circulation;

use App\Enums\CopyStatus;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\User;
use App\Services\Circulation\CopyStateMachine;
use Illuminate\Validation\ValidationException;

final class ReturnCopyAction
{
    public function __construct(private readonly CopyStateMachine $machine = new CopyStateMachine) {}

    public function handle(Copy $copy, User $actor, ?string $note = null): Loan
    {
        $loan = $copy->loans()->whereNull('returned_at')->latest('id')->first();

        if ($loan === null) {
            throw ValidationException::withMessages([
                'code' => 'This copy is not currently on loan.',
            ]);
        }

        return \DB::transaction(function () use ($copy, $actor, $loan, $note) {
            $loan->forceFill([
                'returned_at' => now(),
                'checked_in_by_id' => $actor->id,
                'notes' => $note,
            ])->save();

            $this->machine->transition($copy, CopyStatus::AtReception, $actor, ['loan_id' => $loan->id]);

            activity()
                ->performedOn($loan)
                ->causedBy($actor)
                ->log('loan_returned');

            return $loan;
        });
    }
}
