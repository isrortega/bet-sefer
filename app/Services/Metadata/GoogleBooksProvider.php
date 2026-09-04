<?php

namespace App\Services\Metadata;

use Illuminate\Support\Facades\Http;

class GoogleBooksProvider implements MetadataProvider
{
    public function name(): string
    {
        return 'google_books';
    }

    public function enabled(): bool
    {
        return config('services.metadata.google_books_key') !== null
            && config('services.metadata.google_books_key') !== '';
    }

    public function fetch(string $isbn13): ?BookMetadata
    {
        if (! $this->enabled()) {
            return null;
        }

        $response = Http::timeout(config('services.metadata.google_books_timeout', 3))
            ->acceptJson()
            ->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => "isbn:{$isbn13}",
                'key' => config('services.metadata.google_books_key'),
            ]);

        if (! $response->successful()) {
            return null;
        }

        $info = $response->json('items.0.volumeInfo');

        if (! is_array($info) || empty($info['title'])) {
            return null;
        }

        return new BookMetadata(
            title: (string) $info['title'],
            authors: array_map('strval', $info['authors'] ?? []),
            subtitle: isset($info['subtitle']) ? (string) $info['subtitle'] : null,
            publisher: isset($info['publisher']) ? (string) $info['publisher'] : null,
            publishedYear: isset($info['publishedDate']) && preg_match('/^\d{4}/', (string) $info['publishedDate'], $m)
                ? (int) $m[0]
                : null,
            language: isset($info['language']) ? (string) $info['language'] : null,
            pageCount: isset($info['pageCount']) ? (int) $info['pageCount'] : null,
            summary: isset($info['description']) ? (string) $info['description'] : null,
            coverUrl: isset($info['imageLinks']['thumbnail']) ? (string) $info['imageLinks']['thumbnail'] : null,
        );
    }
}
