<?php

namespace Database\Seeders;

use App\Enums\LoanType;
use App\Models\LoanPolicy;
use Illuminate\Database\Seeder;

class LoanPolicySeeder extends Seeder
{
    public function run(): void
    {
        $policies = [
            [
                'loan_type' => LoanType::General,
                'default_hours' => 240,
                'min_hours' => 168,
                'max_hours' => 360,
                'renewals_allowed' => 2,
            ],
            [
                'loan_type' => LoanType::Reference,
                'default_hours' => 36,
                'min_hours' => 24,
                'max_hours' => 48,
                'renewals_allowed' => 0,
            ],
            [
                'loan_type' => LoanType::Periodical,
                'default_hours' => 96,
                'min_hours' => 24,
                'max_hours' => 168,
                'renewals_allowed' => 1,
            ],
        ];

        foreach ($policies as $policy) {
            LoanPolicy::updateOrCreate(
                ['loan_type' => $policy['loan_type']],
                [
                    'default_hours' => $policy['default_hours'],
                    'min_hours' => $policy['min_hours'],
                    'max_hours' => $policy['max_hours'],
                    'renewals_allowed' => $policy['renewals_allowed'],
                    'special_material_factor' => 0.50,
                    'grace_hours' => 24,
                    'daily_fine_amount' => 0,
                    'max_active_loans_per_user' => 5,
                    'is_active' => true,
                ],
            );
        }
    }
}
