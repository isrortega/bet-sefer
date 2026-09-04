<?php

namespace App\Services\Metadata;

/**
 * Allow-listed bibliographic fields returned by external providers. Used only
 * on the anonymous surface — never persisted into the catalogue.
 */
final class BookMetadata
{
    /**
     * @param  list<string>  $authors
     */
    public function __construct(
        public readonly string $title,
        public readonly array $authors = [],
        public readonly ?string $subtitle = null,
        public readonly ?string $publisher = null,
        public readonly ?int $publishedYear = null,
        public readonly ?string $language = null,
        public readonly ?int $pageCount = null,
        public readonly ?string $summary = null,
        public readonly ?string $coverUrl = null,
    ) {}
}
