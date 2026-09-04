<?php

use App\Models\Author;
use App\Models\Edition;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the catalogue page is public and lists editions', function () {
    $edition = Edition::factory()->withIsbn()->create(['title' => 'The Silent Patient Test']);
    $author = Author::factory()->create(['name' => 'Alex Michaelides']);
    $edition->authors()->attach($author->id, ['role' => 'author', 'position' => 0]);

    $this->get('/catalog?q=patient')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Catalog/Index')->has('items.data'));
});
