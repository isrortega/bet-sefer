<?php

namespace Database\Factories;

use App\Enums\LoanType;
use App\Models\LoanPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanPolicy>
 */
class LoanPolicyFactory extends Factory
{
    protected $model = LoanPolicy::class;

    public function definition(): array
    {
        return [
            'loan_type' => LoanType::General,
            'default_hours' => 240,
            'min_hours' => 168,
            'max_hours' => 360,
            'renewals_allowed' => 2,
            'special_material_factor' => 0.50,
            'grace_hours' => 24,
            'daily_fine_amount' => 0,
            'max_active_loans_per_user' => 5,
            'is_active' => true,
        ];
    }
}
