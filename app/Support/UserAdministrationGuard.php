<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Guards that keep the system administrable: an administrator can never remove
 * the last active administrator.
 */
final class UserAdministrationGuard
{
    public static function activeAdminCount(): int
    {
        return User::role('administrator')
            ->where('status', 'active')
            ->count();
    }

    public static function assertCanChangeRole(User $user, User $actor, string $targetRole): void
    {
        $isSelfDemotion = $user->is($actor) && $targetRole !== 'administrator';

        if ($isSelfDemotion && self::activeAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'role' => __('admin.last_admin'),
            ]);
        }
    }

    public static function assertNotLastActiveAdmin(User $user, User $actor): void
    {
        if ($user->is($actor) && $user->hasRole('administrator') && self::activeAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'user' => __('admin.last_admin'),
            ]);
        }
    }
}
