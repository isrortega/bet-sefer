<?php

namespace Database\Factories;

use App\Enums\CopyStatus;
use App\Models\Copy;
use App\Models\Edition;
use App\Support\CrockfordCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Copy>
 */
class CopyFactory extends Factory
{
    protected $model = Copy::class;

    public function definition(): array
    {
        return [
            'ulid' => (string) Str::ulid(),
            'code' => CrockfordCode::withPrefix('BS'),
            'edition_id' => Edition::factory(),
            'status' => CopyStatus::Available,
            'condition' => 'good',
            'status_changed_at' => now(),
        ];
    }

    public function onLoan(): static
    {
        return $this->state(fn (): array => ['status' => CopyStatus::OnLoan]);
    }

    public function atReception(): static
    {
        return $this->state(fn (): array => ['status' => CopyStatus::AtReception]);
    }

    public function inTransit(): static
    {
        return $this->state(fn (): array => ['status' => CopyStatus::InTransit]);
    }

    public function inRepair(): static
    {
        return $this->state(fn (): array => ['status' => CopyStatus::InRepair]);
    }

    public function lost(): static
    {
        return $this->state(fn (): array => ['status' => CopyStatus::Lost]);
    }

    public function restricted(): static
    {
        return $this->state(fn (): array => ['loan_restricted' => true]);
    }
}
