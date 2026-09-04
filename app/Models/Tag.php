<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @use HasFactory<TagFactory>
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $source
 */
class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'source'];

    /** @return BelongsToMany<Edition, $this> */
    public function editions(): BelongsToMany
    {
        return $this->belongsToMany(Edition::class, 'edition_tag');
    }
}
