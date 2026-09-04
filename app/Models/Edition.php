<?php

namespace App\Models;

use App\Enums\LoanType;
use Database\Factories\EditionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @use HasFactory<EditionFactory>
 *
 * @property int $id
 * @property string $ulid
 * @property string|null $isbn_13
 * @property string|null $isbn_10
 * @property string $title
 * @property string|null $subtitle
 * @property int|null $publisher_id
 * @property int|null $category_id
 * @property int|null $published_year
 * @property string $language
 * @property int|null $page_count
 * @property string $format
 * @property LoanType|string $loan_type
 * @property bool $special_material
 * @property bool $loan_restricted_default
 * @property string|null $cover_path
 * @property string $metadata_source
 * @property Carbon|null $deleted_at
 */
class Edition extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'isbn_13',
        'isbn_10',
        'title',
        'subtitle',
        'edition_statement',
        'publisher_id',
        'category_id',
        'published_year',
        'language',
        'page_count',
        'format',
        'height_mm',
        'width_mm',
        'depth_mm',
        'summary',
        'cover_path',
        'cover_source',
        'loan_type',
        'special_material',
        'loan_restricted_default',
        'internal_notes',
        'metadata_source',
        'ai_classified_at',
        'ai_model',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'special_material' => 'boolean',
        'loan_restricted_default' => 'boolean',
        'published_year' => 'integer',
        'page_count' => 'integer',
        'loan_type' => LoanType::class,
        'ai_classified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /** @return BelongsTo<Publisher, $this> */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsToMany<Author, $this> */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'edition_author')
            ->withPivot('role', 'position')
            ->orderBy('edition_author.position');
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'edition_tag')->orderBy('tags.name');
    }

    /** @return HasMany<Copy, $this> */
    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class);
    }

    public function authorNames(): string
    {
        return $this->authors->map(fn (Author $author) => $author->name)->implode(', ');
    }

    public function hasAvailableCopies(): bool
    {
        return $this->copies()->where('status', 'available')->exists();
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
