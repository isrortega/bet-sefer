<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @use HasFactory<CategoryFactory>
 *
 * @property int $id
 * @property string $ulid
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string $path
 * @property int $depth
 * @property string|null $description
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['ulid', 'parent_id', 'name', 'slug', 'path', 'depth', 'description'];

    /** @return BelongsTo<Category, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Category, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function humanPath(): string
    {
        return implode(' / ', collect(explode('/', trim($this->path, '/')))
            ->map(fn (string $id) => (string) self::find((int) $id)?->name)
            ->filter()
            ->all());
    }
}
