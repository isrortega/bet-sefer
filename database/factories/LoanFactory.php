<?php

namespace Database\Factories;

use App\Models\Copy;
use App\Models\Loan;
use App\Models\User;
use App\Support\CrockfordCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        $copy = Copy::factory()->create();
        $user = User::factory()->active()->create();

        return [
            'ulid' => (string) Str::ulid(),
            'code' => CrockfordCode::withPrefix('LN'),
            'copy_id' => $copy->id,
            'user_id' => $user->id,
            'checked_out_by_id' => User::factory()->active()->create(),
            'checked_out_at' => now()->subDays(3),
            'due_at' => now()->addDays(7),
            'renewals_count' => 0,
            'policy_snapshot' => [
                'loan_type' => 'general',
                'hours' => 240,
                'renewals_allowed' => 2,
            ],
        ];
    }

    public function returned(): static
    {
        return $this->state(fn (): array => [
            'checked_in_by_id' => User::factory()->active()->create()->id,
            'returned_at' => now()->subDay(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'checked_out_at' => now()->subDays(30),
            'due_at' => now()->subDays(5),
        ]);
    }
}
