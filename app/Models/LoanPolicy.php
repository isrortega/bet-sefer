<?php

namespace App\Models;

use App\Enums\LoanType;
use Database\Factories\LoanPolicyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @use HasFactory<LoanPolicyFactory>
 *
 * @property int $id
 * @property LoanType|string $loan_type
 * @property int $default_hours
 * @property int $min_hours
 * @property int $max_hours
 * @property int $renewals_allowed
 * @property float $special_material_factor
 * @property int $grace_hours
 * @property int $max_active_loans_per_user
 * @property bool $is_active
 */
class LoanPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_type',
        'default_hours',
        'min_hours',
        'max_hours',
        'renewals_allowed',
        'special_material_factor',
        'grace_hours',
        'daily_fine_amount',
        'max_active_loans_per_user',
        'is_active',
    ];

    protected $casts = [
        'loan_type' => LoanType::class,
        'special_material_factor' => 'float',
        'is_active' => 'boolean',
    ];
}
