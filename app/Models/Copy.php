<?php

namespace App\Models;

use App\Enums\CopyStatus;
use Database\Factories\CopyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @use HasFactory<CopyFactory>
 *
 * @property int $id
 * @property string $ulid
 * @property string $code
 * @property int $edition_id
 * @property int|null $location_id
 * @property CopyStatus|string $status
 * @property string $condition
 * @property bool|null $loan_restricted
 * @property string|null $cover_path
 * @property Carbon|null $status_changed_at
 * @property Carbon|null $deleted_at
 */
class Copy extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'code',
        'edition_id',
        'location_id',
        'status',
        'condition',
        'loan_restricted',
        'acquisition_date',
        'acquisition_cost',
        'internal_notes',
        'status_changed_at',
    ];

    protected $casts = [
        'status' => CopyStatus::class,
        'loan_restricted' => 'boolean',
        'acquisition_date' => 'date',
        'status_changed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return HasMany<Loan, $this> */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /** @return HasMany<CopyStatusTransition, $this> */
    public function statusTransitions(): HasMany
    {
        return $this->hasMany(CopyStatusTransition::class);
    }

    public function activeLoan(): ?Loan
    {
        return $this->loans()->whereNull('returned_at')->latest('id')->first();
    }

    /** Effective restriction: copies.loan_restricted ?? editions.loan_restricted_default. */
    public function isLoanRestricted(): bool
    {
        if ($this->loan_restricted !== null) {
            return $this->loan_restricted;
        }

        return (bool) ($this->edition->loan_restricted_default ?? false);
    }

    public function isAvailable(): bool
    {
        return $this->status === CopyStatus::Available;
    }
}
