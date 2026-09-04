<?php

namespace App\Services\Metadata;

use Illuminate\Support\Facades\Http;

class OpenLibraryProvider implements MetadataProvider
{
    public function name(): string
    {
        return 'open_library';
    }

    public function fetch(string $isbn13): ?BookMetadata
    {
        $response = Http::timeout(config('services.metadata.open_library_timeout', 3))
            ->acceptJson()
            ->get('https://openlibrary.org/api/books', [
                'bibkeys' => "ISBN:{$isbn13}",
                'format' => 'json',
                'jscmd' => 'data',
            ]);

        if (! $response->successful()) {
            return null;
        }

        $record = $response->json("ISBN:{$isbn13}");

        if (! is_array($record)) {
            return null;
        }

        $authors = [];
        foreach ($record['authors'] ?? [] as $author) {
            if (isset($author['name'])) {
                $authors[] = (string) $author['name'];
            }
        }

        $publisher = null;
        foreach ($record['publishers'] ?? [] as $p) {
            if (isset($p['name'])) {
                $publisher = (string) $p['name'];
                break;
            }
        }

        $cover = $record['cover']['medium'] ?? $record['cover']['large'] ?? null;

        return new BookMetadata(
            title: (string) ($record['title'] ?? ''),
            authors: $authors,
            publisher: $publisher,
            publishedYear: $this->year($record['publish_date'] ?? null),
            pageCount: isset($record['number_of_pages']) ? (int) $record['number_of_pages'] : null,
            coverUrl: is_string($cover) ? $this->secureUrl($cover) : null,
        );
    }

    private function year(mixed $value): ?int
    {
        if (is_string($value) && preg_match('/(19|20)\d{2}/', $value, $m)) {
            return (int) $m[0];
        }

        return null;
    }

    private function secureUrl(string $url): ?string
    {
        if (! str_starts_with($url, 'https://')) {
            return null;
        }

        return $url;
    }
}
