<?php

namespace App\Actions\Catalog;

use App\Models\Edition;
use Illuminate\Validation\ValidationException;

final class DeleteEditionAction
{
    public function handle(Edition $edition): void
    {
        if ($edition->copies()->where('status', 'on_loan')->exists()) {
            throw ValidationException::withMessages([
                'edition' => __('errors.copy_on_loan_edition'),
            ]);
        }

        $hasHistory = $edition->copies()
            ->whereHas('loans')
            ->exists();

        if ($hasHistory) {
            // Soft delete: the loans still reference the copies.
            $edition->copies()->delete();
            $edition->delete();
            activity()->performedOn($edition)->log('edition_soft_deleted');

            return;
        }

        // Hard delete: no history at all ("created by mistake" case).
        $edition->copies()->forceDelete();
        $edition->forceDelete();
        activity()->performedOn($edition)->log('edition_hard_deleted');
    }
}
