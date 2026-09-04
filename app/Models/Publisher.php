<?php

namespace App\Models;

use Database\Factories\PublisherFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @use HasFactory<PublisherFactory>
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 */
class Publisher extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    /** @return HasMany<Edition, $this> */
    public function editions(): HasMany
    {
        return $this->hasMany(Edition::class);
    }
}
