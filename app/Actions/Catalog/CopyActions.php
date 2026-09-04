<?php

namespace App\Actions\Catalog;

use App\Enums\CopyStatus;
use App\Models\Copy;
use App\Models\Edition;
use App\Support\CrockfordCode;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CopyActions
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function store(Edition $edition, array $data): Copy
    {
        return Copy::create([
            'ulid' => (string) Str::ulid(),
            'code' => CrockfordCode::withPrefix('BS'),
            'edition_id' => $edition->id,
            'location_id' => $data['location_id'] ?? null,
            'status' => CopyStatus::Available,
            'condition' => $data['condition'] ?? 'good',
            'loan_restricted' => null,
            'acquisition_date' => $data['acquisition_date'] ?? null,
            'status_changed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function update(Copy $copy, array $data): Copy
    {
        $copy->forceFill([
            'location_id' => $data['location_id'] ?? $copy->location_id,
            'condition' => $data['condition'] ?? $copy->condition,
            'loan_restricted' => array_key_exists('loan_restricted', $data)
                ? self::restrictedValue((string) $data['loan_restricted'])
                : $copy->loan_restricted,
            'internal_notes' => $data['internal_notes'] ?? $copy->internal_notes,
        ])->save();

        return $copy->fresh();
    }

    public static function destroy(Copy $copy): void
    {
        if ($copy->status === CopyStatus::OnLoan || $copy->loans()->whereNull('returned_at')->exists()) {
            throw ValidationException::withMessages([
                'copy' => __('errors.copy_on_loan'),
            ]);
        }

        if ($copy->loans()->exists()) {
            $copy->delete();

            return;
        }

        $copy->forceDelete();
    }

    private static function restrictedValue(string $value): ?bool
    {
        return match ($value) {
            '1', 'true', 'yes' => true,
            '0', 'false', 'no' => false,
            default => null, // inherit
        };
    }
}
