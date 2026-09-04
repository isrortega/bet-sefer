<?php

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\UserAdministrationGuard;
use Illuminate\Validation\ValidationException;

final class UpdateUserAction
{
    /**
     * @param  array{name?: string, email?: string, role?: string}  $data
     */
    public function handle(User $user, User $actor, array $data): User
    {
        $roleChanged = isset($data['role']) && ! $user->hasRole($data['role']);

        if ($roleChanged) {
            UserAdministrationGuard::assertCanChangeRole($user, $actor, $data['role']);
        }

        $user->fill([
            'name' => $data['name'] ?? $user->name,
            'email' => isset($data['email']) ? strtolower($data['email']) : $user->email,
        ])->save();

        if ($roleChanged) {
            $user->syncRoles([$data['role']]);
        }

        activity()->performedOn($user)->causedBy($actor)->withProperties(['fields' => array_keys($data)])->log('user_updated');

        return $user->fresh();
    }

    public function activate(User $user, User $actor): User
    {
        UserAdministrationGuard::assertNotLastActiveAdmin($user, $actor);

        $user->forceFill([
            'status' => UserStatus::Active,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'blocked_until' => null,
            'suspension_reason' => null,
        ])->save();

        activity()->performedOn($user)->causedBy($actor)->log('user_activated');

        return $user->fresh();
    }

    public function suspend(User $user, User $actor, ?string $reason): User
    {
        UserAdministrationGuard::assertNotLastActiveAdmin($user, $actor);

        $user->forceFill([
            'status' => UserStatus::Suspended,
            'suspension_reason' => $reason,
        ])->save();

        activity()->performedOn($user)->causedBy($actor)->log('user_suspended');

        return $user->fresh();
    }

    public function close(User $user, User $actor): void
    {
        UserAdministrationGuard::assertNotLastActiveAdmin($user, $actor);

        if ($user->loans()->whereNull('returned_at')->exists()) {
            throw ValidationException::withMessages([
                'user' => __('readers.still_loaning'),
            ]);
        }

        $user->delete();
        activity()->performedOn($user)->causedBy($actor)->log('user_closed');
    }
}
