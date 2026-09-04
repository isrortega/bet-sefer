<?php

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\CrockfordCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

final class CreateUserAction
{
    public const ROLES = ['administrator', 'librarian', 'shelver', 'reader'];

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     role: string,
     *     document_type?: string|null,
     *     document_number?: string|null,
     * }  $data
     */
    public function handle(array $data, User $actor): User
    {
        $user = User::create([
            'ulid' => (string) Str::ulid(),
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'status' => UserStatus::Active,
            'member_code' => CrockfordCode::generate(),
            'document_type' => $data['document_type'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'locale' => app()->getLocale(),
        ]);

        Role::findOrCreate($data['role'], 'web');
        $user->syncRoles([$data['role']]);

        activity()->performedOn($user)->causedBy($actor)->log('user_created_manually');

        return $user;
    }
}
