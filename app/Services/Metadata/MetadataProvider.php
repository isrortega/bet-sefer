<?php

namespace App\Services\Metadata;

interface MetadataProvider
{
    public function name(): string;

    public function fetch(string $isbn13): ?BookMetadata;
}
