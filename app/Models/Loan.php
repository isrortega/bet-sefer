<?php

namespace App\Models;

use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @use HasFactory<LoanFactory>
 *
 * @property int $id
 * @property string $ulid
 * @property string $code
 * @property int $copy_id
 * @property int $user_id
 * @property int $checked_out_by_id
 * @property int|null $checked_in_by_id
 * @property Carbon $checked_out_at
 * @property Carbon $due_at
 * @property Carbon|null $returned_at
 * @property int $renewals_count
 * @property array<string, mixed> $policy_snapshot
 */
class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'ulid',
        'code',
        'copy_id',
        'user_id',
        'checked_out_by_id',
        'checked_in_by_id',
        'checked_out_at',
        'due_at',
        'returned_at',
        'renewals_count',
        'policy_snapshot',
        'fine_amount',
        'fine_status',
        'notes',
    ];

    protected $casts = [
        'checked_out_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
        'renewals_count' => 'integer',
        'policy_snapshot' => 'array',
    ];

    /** @return BelongsTo<Copy, $this> */
    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by_id');
    }

    public function isOverdue(?Carbon $now = null): bool
    {
        return $this->returned_at === null
            && $this->due_at->lt($now ?? now());
    }
}
