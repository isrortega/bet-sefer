<?php

use App\Models\Copy;
use App\Models\Edition;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function catRole(string $name, array $permissions): void
{
    $role = Role::findOrCreate($name, 'web');
    foreach ($permissions as $permission) {
        $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
}

function catUser(string $role, array $permissions): User
{
    catRole($role, $permissions);
    $user = User::factory()->active()->create();
    $user->syncRoles([$role]);

    return $user;
}

function librarianEditor(): User
{
    return catUser('librarian', [
        'catalog.view', 'editions.create', 'editions.update', 'copies.create', 'copies.update',
    ]);
}

function adminEditor(): User
{
    return catUser('administrator', [
        'catalog.view', 'editions.create', 'editions.update', 'editions.delete',
        'copies.create', 'copies.update', 'copies.delete',
    ]);
}

function editionPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'A New Book',
        'isbn_13' => '9783161484100',
        'authors' => 'Jane Doe, John Roe',
        'publisher' => 'Example Press',
        'tags' => 'novel, classic',
        'language' => 'en',
        'format' => 'paperback',
        'loan_type' => 'general',
        'published_year' => 2024,
        'page_count' => 300,
    ], $overrides);
}

test('staff without edition permissions cannot open the staff catalogue', function () {
    foreach (['reader', 'shelver'] as $role) {
        $user = catUser($role, []);

        $this->actingAs($user)->get('/staff/catalog')->assertForbidden();
        $this->actingAs($user)->get('/staff/catalog/create')->assertForbidden();
    }
});

test('a librarian can create an edition with authors, publisher and tags', function () {
    $librarian = librarianEditor();

    $this->actingAs($librarian)->post('/staff/catalog', editionPayload())->assertSessionHasNoErrors();

    $edition = Edition::where('isbn_13', '9783161484100')->firstOrFail();
    expect($edition->authors()->count())->toBe(2)
        ->and($edition->tags()->count())->toBe(2)
        ->and($edition->publisher?->name)->toBe('Example Press');
});

test('a duplicate active ISBN is refused', function () {
    $librarian = librarianEditor();
    Edition::factory()->create(['isbn_13' => '9783161484100']);

    $this->actingAs($librarian)->post('/staff/catalog', editionPayload())->assertSessionHasErrors(['isbn_13']);
});

test('a librarian can add and update copies but not delete the edition', function () {
    $librarian = librarianEditor();
    $edition = Edition::factory()->create();

    $this->actingAs($librarian)->post("/staff/catalog/{$edition->ulid}/copies", [])->assertSessionHasNoErrors();
    expect($edition->copies()->count())->toBe(1);

    $copy = $edition->copies()->first();
    $this->actingAs($librarian)->post("/staff/copies/{$copy->id}/update", ['condition' => 'fair'])->assertSessionHasNoErrors();
    expect($copy->fresh()->condition)->toBe('fair');

    $this->actingAs($librarian)->delete("/staff/catalog/{$edition->ulid}")->assertForbidden();
});

test('an administrator can delete: soft when history exists, hard when it does not', function () {
    $admin = adminEditor();

    $historic = Edition::factory()->create();
    $copy = Copy::factory()->create(['edition_id' => $historic->id]);
    Loan::factory()->returned()->create(['copy_id' => $copy->id, 'user_id' => catUser('reader', [])->id]);

    $this->actingAs($admin)->delete("/staff/catalog/{$historic->ulid}")->assertSessionHasNoErrors();
    expect($historic->fresh()->deleted_at)->not->toBeNull();

    $clean = Edition::factory()->create();
    $this->actingAs($admin)->delete("/staff/catalog/{$clean->ulid}")->assertSessionHasNoErrors();
    expect(Edition::find($clean->id))->toBeNull();
});

test('deletion is refused while any copy is on loan', function () {
    $admin = adminEditor();
    $edition = Edition::factory()->create();
    Copy::factory()->onLoan()->create(['edition_id' => $edition->id]);

    $this->actingAs($admin)->delete("/staff/catalog/{$edition->ulid}")->assertSessionHasErrors(['edition']);
    expect($edition->fresh()->deleted_at)->toBeNull();
});

test('a copy on loan cannot be deleted, a free copy can', function () {
    $admin = adminEditor();
    $edition = Edition::factory()->create();
    $loaned = Copy::factory()->onLoan()->create(['edition_id' => $edition->id]);
    $free = Copy::factory()->create(['edition_id' => $edition->id]);

    $this->actingAs($admin)->delete("/staff/copies/{$loaned->id}")->assertSessionHasErrors();
    expect(Copy::find($loaned->id))->not->toBeNull();

    $this->actingAs($admin)->delete("/staff/copies/{$free->id}")->assertSessionHasNoErrors();
    expect(Copy::find($free->id))->toBeNull();
});

test('the catalogue list renders for editors', function () {
    $user = librarianEditor();
    Edition::factory()->create(['title' => 'Visible Book']);

    $this->actingAs($user)->get('/staff/catalog')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->component('Staff/Catalog'));
});
