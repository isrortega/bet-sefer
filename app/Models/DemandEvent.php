<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $type
 * @property int|null $edition_id
 * @property string|null $isbn
 * @property string|null $query_text
 * @property int|null $user_id
 * @property string|null $ip_hash
 */
class DemandEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['type', 'edition_id', 'isbn', 'query_text', 'user_id', 'ip_hash', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
