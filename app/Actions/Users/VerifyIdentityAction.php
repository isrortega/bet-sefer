<?php

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class VerifyIdentityAction
{
    /**
     * @param  array{document_type: string, document_number: string}  $data
     */
    public function handle(User $user, User $actor, array $data): User
    {
        $normalised = strtoupper(preg_replace('/[^A-Z0-9]/', '', $data['document_number']) ?? $data['document_number']);
        $hash = hash_hmac('sha256', $normalised, config('app.key'));

        $duplicate = User::withTrashed()
            ->where('document_hash', $hash)
            ->whereKeyNot($user->getKey())
            ->first();

        if ($duplicate !== null) {
            throw ValidationException::withMessages([
                'document_number' => 'Another account is already registered with this document. Contact the administrator.',
            ]);
        }

        $user->forceFill([
            'status' => UserStatus::Active,
            'document_type' => $data['document_type'],
            'document_number' => $data['document_number'],
            'document_hash' => $hash,
            'identity_verified_at' => now(),
            'identity_verified_by_id' => $actor->id,
            'blocked_until' => null,
            'suspension_reason' => null,
        ])->save();

        activity()
            ->performedOn($user)
            ->causedBy($actor)
            ->withProperties(['document_type' => $data['document_type']])
            ->log('identity_verified');

        return $user->fresh();
    }
}
