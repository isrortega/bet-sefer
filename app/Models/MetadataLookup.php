<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $isbn_13
 * @property string $provider
 * @property string $status
 * @property array<string, mixed>|null $payload
 * @property Carbon $fetched_at
 * @property Carbon|null $expires_at
 */
class MetadataLookup extends Model
{
    public $timestamps = false;

    protected $fillable = ['isbn_13', 'provider', 'status', 'payload', 'fetched_at', 'expires_at'];

    protected $casts = [
        'payload' => 'array',
        'fetched_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
