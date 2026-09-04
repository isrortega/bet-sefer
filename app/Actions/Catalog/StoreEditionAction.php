<?php

namespace App\Actions\Catalog;

use App\Enums\LoanType;
use App\Models\Author;
use App\Models\Edition;
use App\Models\Publisher;
use App\Models\Tag;
use Illuminate\Support\Str;

/**
 * Creates an edition and its free-text authors / publisher / tags.
 *
 * @return array{
 *     data: Edition,
 *     created_authors: array<int, string>,
 * }
 */
final class StoreEditionAction
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(array $input): Edition
    {
        $authors = $this->names($input['authors'] ?? '');
        $tags = $this->names($input['tags'] ?? '');

        $edition = Edition::create([
            'ulid' => (string) Str::ulid(),
            'isbn_13' => $this->cleanIsbn($input['isbn_13'] ?? null),
            'title' => $input['title'],
            'subtitle' => $input['subtitle'] ?? null,
            'edition_statement' => $input['edition_statement'] ?? null,
            'publisher_id' => $this->publisher($input['publisher'] ?? '')?->id,
            'category_id' => $input['category_id'] ?? null,
            'published_year' => ! empty($input['published_year']) ? (int) $input['published_year'] : null,
            'language' => $input['language'] ?? 'en',
            'page_count' => ! empty($input['page_count']) ? (int) $input['page_count'] : null,
            'format' => $input['format'] ?? 'paperback',
            'summary' => $input['summary'] ?? null,
            'internal_notes' => $input['internal_notes'] ?? null,
            'loan_type' => $input['loan_type'] ?? LoanType::General,
            'special_material' => (bool) ($input['special_material'] ?? false),
            'loan_restricted_default' => (bool) ($input['loan_restricted_default'] ?? false),
            'metadata_source' => 'manual',
            'created_by_id' => $input['actor'] ?? null,
        ]);

        foreach ($authors as $position => $name) {
            $edition->authors()->attach($this->author($name)->id, ['role' => 'author', 'position' => $position]);
        }

        foreach ($tags as $name) {
            $edition->tags()->attach($this->tag($name)->id);
        }

        return $edition;
    }

    /**
     * @return array<int, string>
     */
    public function names(string $raw): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== ''));
    }

    public function cleanIsbn(?string $isbn): ?string
    {
        if ($isbn === null || trim($isbn) === '') {
            return null;
        }

        return preg_replace('/[^0-9Xx]/', '', $isbn);
    }

    public function publisher(string $name): ?Publisher
    {
        $name = trim($name);

        return $name !== ''
            ? Publisher::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])
            : null;
    }

    private function author(string $name): Author
    {
        $slug = Str::slug($name);

        return Author::firstOrCreate(['slug' => $slug], ['ulid' => (string) Str::ulid(), 'name' => $name]);
    }

    private function tag(string $name): Tag
    {
        $slug = Str::slug($name);

        return Tag::firstOrCreate(['slug' => $slug], ['name' => $name, 'source' => 'manual']);
    }
}
