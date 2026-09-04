<?php

use App\Models\DemandEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function demandRole(string $name, array $permissions): void
{
    $role = Role::findOrCreate($name, 'web');
    foreach ($permissions as $permission) {
        $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
}

function demandUser(string $role, array $permissions): User
{
    demandRole($role, $permissions);
    $user = User::factory()->active()->create();
    $user->syncRoles([$role]);

    return $user;
}

function suggestion(string $isbn = '9783161484100'): DemandEvent
{
    return DemandEvent::create([
        'type' => 'acquisition_suggestion',
        'isbn' => $isbn,
        'created_at' => now(),
    ]);
}

test('an administrator can list acquisition suggestions and mark one handled', function () {
    $admin = demandUser('administrator', ['demand.manage']);
    suggestion();

    $this->actingAs($admin)->get('/staff/demand')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Staff/Demand')
            ->has('suggestions.data', 1));

    $event = DemandEvent::firstOrFail();
    $this->actingAs($admin)->post("/staff/demand/{$event->id}/resolve")->assertSessionHasNoErrors();

    expect($event->fresh()->resolved_at)->not->toBeNull();
});

test('librarians cannot see or act on acquisition suggestions', function () {
    $librarian = demandUser('librarian', ['loans.create']);
    $event = suggestion();

    $this->actingAs($librarian)->get('/staff/demand')->assertForbidden();
    $this->actingAs($librarian)->post("/staff/demand/{$event->id}/resolve")->assertForbidden();
});

test('readers cannot reach the demand area', function () {
    $reader = demandUser('reader', []);

    $this->actingAs($reader)->get('/staff/demand')->assertForbidden();
});
