<?php

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Mail\VerifyAccountEmail;
use App\Models\User;
use App\Support\CrockfordCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

final class RegisterReaderAction
{
    /**
     * @param  array{name: string, email: string, password: string, locale?: string}  $data
     */
    public function handle(array $data): User
    {
        $user = User::create([
            'ulid' => (string) Str::ulid(),
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => Hash::make($data['password']),
            'status' => UserStatus::PendingEmail,
            'member_code' => CrockfordCode::generate(),
            'locale' => $data['locale'] ?? app()->getLocale(),
        ]);

        Role::findOrCreate('reader', 'web');
        $user->syncRoles(['reader']);

        Mail::to($user)->queue(new VerifyAccountEmail($user));

        return $user;
    }
}
