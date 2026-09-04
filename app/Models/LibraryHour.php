<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $weekday
 * @property string|null $opens_at
 * @property string|null $closes_at
 * @property bool $is_closed
 */
class LibraryHour extends Model
{
    public $timestamps = false;

    protected $fillable = ['weekday', 'opens_at', 'closes_at', 'is_closed'];

    protected $casts = ['is_closed' => 'boolean'];
}
