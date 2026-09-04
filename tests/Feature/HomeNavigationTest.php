<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function navRole(string $name, array $permissions): void
{
    $role = Role::findOrCreate($name, 'web');
    foreach ($permissions as $permission) {
        $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
}

function navUser(string $role, array $permissions): User
{
    navRole($role, $permissions);
    $user = User::factory()->active()->create();
    $user->syncRoles([$role]);

    return $user;
}

test('home exposes front-desk capability to a librarian', function () {
    $librarian = navUser('librarian', ['loans.create', 'loans.return', 'loans.renew', 'copies.transition', 'users.verify_identity']);

    $this->actingAs($librarian)->get('/')->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('auth.user.capabilities.front_desk', true)
        ->where('auth.user.capabilities.shelving', true)
        ->where('auth.user.capabilities.readers', true));
});

test('home hides staff capabilities from a plain reader', function () {
    $reader = navUser('reader', []);

    $this->actingAs($reader)->get('/')->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('auth.user.capabilities.front_desk', false)
        ->where('auth.user.capabilities.shelving', false)
        ->where('auth.user.capabilities.readers', false));
});

test('a shelver only gets the shelving capability on home', function () {
    $shelver = navUser('shelver', ['copies.transition']);

    $this->actingAs($shelver)->get('/')->assertInertia(fn ($page) => $page
        ->component('Home')
        ->where('auth.user.capabilities.front_desk', false)
        ->where('auth.user.capabilities.shelving', true));
});

test('anonymous visitors have no auth payload', function () {
    $this->get('/')->assertInertia(fn ($page) => $page->component('Home')->where('auth.user', null));
});
