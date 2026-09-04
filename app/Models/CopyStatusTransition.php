<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $copy_id
 * @property string|null $from_status
 * @property string $to_status
 * @property int|null $user_id
 * @property int|null $loan_id
 * @property int|null $from_location_id
 * @property int|null $to_location_id
 * @property string|null $note
 */
class CopyStatusTransition extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'copy_id',
        'from_status',
        'to_status',
        'user_id',
        'loan_id',
        'from_location_id',
        'to_location_id',
        'note',
        'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    /** @return BelongsTo<Copy, $this> */
    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class);
    }

    /** @return BelongsTo<Loan, $this> */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
