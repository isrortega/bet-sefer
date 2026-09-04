<?php

namespace App\Services\Metadata;

use App\Models\MetadataLookup;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Looks up an ISBN on the public surface through the free metadata providers.
 * Read-only for the catalogue: nothing is created, only cached for later calls.
 */
final class PublicIsbnLookupService
{
    public const CACHE_TTL_DAYS = 90;

    public function __construct(
        private readonly OpenLibraryProvider $openLibrary = new OpenLibraryProvider,
        private readonly GoogleBooksProvider $googleBooks = new GoogleBooksProvider,
    ) {}

    public function lookup(string $isbn13): ?BookMetadata
    {
        if (! preg_match('/^(?:\d{9}X|\d{13})$/', $isbn13)) {
            return null;
        }

        $cached = Cache::get("isbn:{$isbn13}");
        if (is_array($cached)) {
            return $this->fromArray($cached);
        }

        $results = [];
        $results[] = $this->resolved($this->openLibrary, $isbn13);
        $results[] = $this->resolved($this->googleBooks, $isbn13);

        $meta = $this->merge(array_values(array_filter($results, fn ($m) => $m instanceof BookMetadata)));

        if ($meta !== null) {
            Cache::put("isbn:{$isbn13}", $this->toArray($meta), now()->addDays(30));
        }

        return $meta;
    }

    private function resolved(MetadataProvider $provider, string $isbn13): ?BookMetadata
    {
        $existing = MetadataLookup::where('isbn_13', $isbn13)
            ->where('provider', $provider->name())
            ->where('expires_at', '>', now())
            ->first();

        if ($existing !== null && $existing->status === 'hit' && is_array($existing->payload)) {
            return $this->fromArray($existing->payload);
        }

        try {
            $meta = $provider->fetch($isbn13);
        } catch (Throwable) {
            $this->record($provider, $isbn13, 'error', null);

            return null;
        }

        if ($meta === null) {
            $this->record($provider, $isbn13, 'miss', null);

            return null;
        }

        $this->record($provider, $isbn13, 'hit', $this->toArray($meta));

        return $meta;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function record(MetadataProvider $provider, string $isbn13, string $status, ?array $payload): void
    {
        MetadataLookup::updateOrCreate(
            ['isbn_13' => $isbn13, 'provider' => $provider->name()],
            [
                'status' => $status,
                'payload' => $payload,
                'fetched_at' => now(),
                'expires_at' => now()->addDays(self::CACHE_TTL_DAYS),
            ],
        );
    }

    /**
     * @param  list<BookMetadata>  $metas
     */
    private function merge(array $metas): ?BookMetadata
    {
        if ($metas === []) {
            return null;
        }

        $base = null;
        $backup = null;

        foreach ($metas as $meta) {
            $backup ??= $meta;
            if ($meta->title !== '' && $base === null) {
                $base = $meta;
            }
        }

        $base ??= $backup;

        return new BookMetadata(
            title: $base->title,
            authors: $base->authors,
            subtitle: $base->subtitle,
            publisher: $base->publisher,
            publishedYear: $base->publishedYear,
            language: $base->language,
            pageCount: $base->pageCount,
            summary: $base->summary ?? $backup->summary,
            coverUrl: $base->coverUrl ?? $backup->coverUrl,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(BookMetadata $meta): array
    {
        return [
            'title' => $meta->title,
            'authors' => $meta->authors,
            'subtitle' => $meta->subtitle,
            'publisher' => $meta->publisher,
            'published_year' => $meta->publishedYear,
            'language' => $meta->language,
            'page_count' => $meta->pageCount,
            'summary' => $meta->summary,
            'cover_url' => $meta->coverUrl,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fromArray(array $data): BookMetadata
    {
        return new BookMetadata(
            title: (string) ($data['title'] ?? ''),
            authors: array_map('strval', $data['authors'] ?? []),
            subtitle: isset($data['subtitle']) ? (string) $data['subtitle'] : null,
            publisher: isset($data['publisher']) ? (string) $data['publisher'] : null,
            publishedYear: isset($data['published_year']) ? (int) $data['published_year'] : null,
            language: isset($data['language']) ? (string) $data['language'] : null,
            pageCount: isset($data['page_count']) ? (int) $data['page_count'] : null,
            summary: isset($data['summary']) ? (string) $data['summary'] : null,
            coverUrl: isset($data['cover_url']) ? (string) $data['cover_url'] : null,
        );
    }
}
