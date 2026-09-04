<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\CrockfordCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => UserStatus::PendingIdentity,
            'member_code' => $this->uniqueMemberCode(),
            'locale' => 'en',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => UserStatus::Active,
            'identity_verified_at' => now(),
        ]);
    }

    public function pendingIdentity(): static
    {
        return $this->state(fn (): array => ['status' => UserStatus::PendingIdentity]);
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => [
            'status' => UserStatus::Suspended,
            'suspension_reason' => 'administrative',
        ]);
    }

    private function uniqueMemberCode(): string
    {
        do {
            $code = CrockfordCode::generate();
        } while (User::withTrashed()->where('member_code', $code)->exists());

        return $code;
    }
}
