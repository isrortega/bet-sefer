<?php

namespace App\Actions\Catalog;

use App\Models\Author;
use App\Models\Edition;
use App\Models\Tag;
use Illuminate\Support\Str;

final class UpdateEditionAction
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function handle(Edition $edition, array $input): Edition
    {
        $fill = [
            'title' => $input['title'] ?? $edition->title,
            'subtitle' => array_key_exists('subtitle', $input) ? ($input['subtitle'] ?: null) : $edition->subtitle,
            'edition_statement' => array_key_exists('edition_statement', $input) ? ($input['edition_statement'] ?: null) : $edition->edition_statement,
            'category_id' => $input['category_id'] ?? $edition->category_id,
            'published_year' => ! empty($input['published_year']) ? (int) $input['published_year'] : null,
            'language' => $input['language'] ?? $edition->language,
            'page_count' => ! empty($input['page_count']) ? (int) $input['page_count'] : null,
            'format' => $input['format'] ?? $edition->format,
            'summary' => array_key_exists('summary', $input) ? ($input['summary'] ?: null) : $edition->summary,
            'internal_notes' => array_key_exists('internal_notes', $input) ? ($input['internal_notes'] ?: null) : $edition->internal_notes,
            'loan_type' => $input['loan_type'] ?? $edition->loan_type,
            'special_material' => (bool) ($input['special_material'] ?? $edition->special_material),
            'loan_restricted_default' => (bool) ($input['loan_restricted_default'] ?? $edition->loan_restricted_default),
        ];

        if (array_key_exists('publisher', $input) && trim((string) $input['publisher']) !== '') {
            $publisher = (new StoreEditionAction)->publisher((string) $input['publisher']);
            $fill['publisher_id'] = $publisher?->id;
        }

        $edition->fill($fill)->save();

        if (array_key_exists('authors', $input)) {
            $edition->authors()->detach();
            foreach ((new StoreEditionAction)->names((string) $input['authors']) as $position => $name) {
                $edition->authors()->attach($this->authorId($name), ['role' => 'author', 'position' => $position]);
            }
        }

        if (array_key_exists('tags', $input)) {
            $edition->tags()->detach();
            foreach ((new StoreEditionAction)->names((string) $input['tags']) as $name) {
                $edition->tags()->attach($this->tagId($name));
            }
        }

        return $edition->fresh();
    }

    private function authorId(string $name): int
    {
        $slug = Str::slug($name);
        $author = Author::where('slug', $slug)->first();

        if ($author === null) {
            $author = Author::create(['ulid' => (string) Str::ulid(), 'name' => $name, 'slug' => $slug]);
        }

        return $author->id;
    }

    private function tagId(string $name): int
    {
        $slug = Str::slug($name);

        return Tag::firstOrCreate(['slug' => $slug], ['name' => $name, 'source' => 'manual'])->id;
    }
}
