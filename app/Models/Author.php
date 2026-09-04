<?php

namespace App\Models;

use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @use HasFactory<AuthorFactory>
 *
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property string $slug
 * @property string|null $sort_name
 */
class Author extends Model
{
    use HasFactory;

    protected $fillable = ['ulid', 'name', 'slug', 'sort_name', 'birth_year', 'death_year', 'bio', 'external_ids'];

    protected $casts = ['external_ids' => 'array'];

    /** @return BelongsToMany<Edition, $this> */
    public function editions(): BelongsToMany
    {
        return $this->belongsToMany(Edition::class, 'edition_author')
            ->withPivot('role', 'position');
    }
}
