<?php

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function roleWithName(string $name, array $permissions = []): Role
{
    $role = Role::findOrCreate($name, 'web');
    if ($permissions !== []) {
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
    }

    return $role;
}

function userWithRole(string $role): User
{
    $permissions = match ($role) {
        'librarian' => ['users.verify_identity', 'copies.transition'],
        'administrator' => ['users.manage', 'users.verify_identity', 'copies.transition'],
        'shelver' => ['copies.transition'],
        default => [],
    };
    roleWithName($role, $permissions);

    $user = User::factory()->active()->create();
    $user->syncRoles([$role]);

    return $user;
}

test('a librarian can list readers and verify an identity', function () {
    $librarian = userWithRole('librarian');
    $reader = User::factory()->pendingIdentity()->create();

    $this->actingAs($librarian)
        ->get('/staff/readers')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Staff/Readers')
            ->has('readers.data', 2));

    $this->actingAs($librarian)
        ->post("/staff/readers/{$reader->ulid}/verify", [
            'document_type' => 'CC',
            'document_number' => '100200300',
        ])->assertSessionHasNoErrors();

    $reader->refresh();
    expect($reader->status)->toBe(UserStatus::Active)
        ->and($reader->identity_verified_by_id)->toBe($librarian->id);
});

test('identity verification is refused for roles without the permission', function () {
    $shelver = userWithRole('shelver');
    $reader = User::factory()->pendingIdentity()->create();

    $this->actingAs($shelver)
        ->post("/staff/readers/{$reader->ulid}/verify", [
            'document_type' => 'CC',
            'document_number' => '100200300',
        ])->assertForbidden();
});

test('a document already linked to another account produces a clear conflict', function () {
    $librarian = userWithRole('librarian');

    $existing = User::factory()->active()->create([
        'document_type' => 'CC',
        'document_number' => '999888777',
        'document_hash' => hash_hmac('sha256', '999888777', config('app.key')),
    ]);

    $reader = User::factory()->pendingIdentity()->create();

    $this->actingAs($librarian)
        ->post("/staff/readers/{$reader->ulid}/verify", [
            'document_type' => 'CC',
            'document_number' => '999888777',
        ])->assertSessionHasErrors(['document_number']);

    expect($reader->fresh()->status)->not->toBe(UserStatus::Active);
});

test('only an administrator can reopen a closed account', function () {
    $admin = userWithRole('administrator');
    $librarian = userWithRole('librarian');
    $closed = User::factory()->create(['deleted_at' => now()]);

    $this->actingAs($librarian)->post("/staff/readers/{$closed->ulid}/restore")->assertForbidden();

    $this->actingAs($admin)->post("/staff/readers/{$closed->ulid}/restore");

    expect($closed->fresh()->deleted_at)->toBeNull()
        ->and($closed->fresh()->status)->toBe(UserStatus::PendingIdentity);
});

test('reader management list is hidden from readers', function () {
    $reader = userWithRole('reader');

    $this->actingAs($reader)->get('/staff/readers')->assertForbidden();
});
