<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $date
 * @property string $name
 * @property bool $is_recurring
 */
class Holiday extends Model
{
    public $timestamps = false;

    protected $fillable = ['date', 'name', 'is_recurring'];

    protected $casts = ['date' => 'date', 'is_recurring' => 'boolean'];
}
