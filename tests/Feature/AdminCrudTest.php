<?php

use App\Enums\UserStatus;
use App\Models\Category;
use App\Models\LoanPolicy;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function crudRole(string $name, array $permissions): void
{
    $role = Role::findOrCreate($name, 'web');
    foreach ($permissions as $permission) {
        $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
}

function crudUser(string $role, array $permissions): User
{
    crudRole($role, $permissions);
    $user = User::factory()->active()->create();
    $user->syncRoles([$role]);

    return $user;
}

function adminUser(): User
{
    return crudUser('administrator', [
        'users.manage', 'taxonomy.manage', 'policies.manage', 'roles.manage',
    ]);
}

$adminPerms = ['users.manage', 'taxonomy.manage', 'policies.manage', 'roles.manage'];

test('staff without management permissions get 403 on every admin area', function () {
    foreach (['librarian', 'shelver', 'reader'] as $role) {
        $user = crudUser($role, $role === 'librarian' ? ['loans.create'] : []);

        $this->actingAs($user)->get('/staff/users')->assertForbidden();
        $this->actingAs($user)->get('/staff/roles')->assertForbidden();
        $this->actingAs($user)->get('/staff/categories')->assertForbidden();
        $this->actingAs($user)->get('/staff/locations')->assertForbidden();
        $this->actingAs($user)->get('/staff/policies')->assertForbidden();
    }
});

test('admin can create a user manually as active', function () {
    $admin = adminUser();

    $this->actingAs($admin)->post('/staff/users', [
        'name' => 'New Staff',
        'email' => 'staff@betsefer.local',
        'password' => 'secret-pass-123',
        'role' => 'librarian',
    ])->assertSessionHasNoErrors();

    $user = User::where('email', 'staff@betsefer.local')->firstOrFail();
    expect($user->status)->toBe(UserStatus::Active)
        ->and($user->hasRole('librarian'))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
});

test('admin can suspend, activate, close and reopen a user', function () {
    $admin = adminUser();
    $target = User::factory()->active()->create();
    $target->syncRoles(Role::findOrCreate('reader', 'web'));

    $this->actingAs($admin)->post("/staff/users/{$target->ulid}/suspend", ['reason' => 'abuse'])->assertSessionHasNoErrors();
    expect($target->fresh()->status)->toBe(UserStatus::Suspended);

    $this->actingAs($admin)->post("/staff/users/{$target->ulid}/activate")->assertSessionHasNoErrors();
    expect($target->fresh()->status)->toBe(UserStatus::Active);

    $this->actingAs($admin)->post("/staff/users/{$target->ulid}/close")->assertSessionHasNoErrors();
    expect($target->fresh()->deleted_at)->not->toBeNull();

    $this->actingAs($admin)->post("/staff/users/{$target->ulid}/restore")->assertSessionHasNoErrors();
    expect($target->fresh()->deleted_at)->toBeNull()
        ->and($target->fresh()->status)->toBe(UserStatus::PendingIdentity);
});

test('the last active administrator cannot demote or close themselves', function () {
    $admin = adminUser();

    $this->actingAs($admin)->post("/staff/users/{$admin->ulid}/update", ['role' => 'reader'])
        ->assertSessionHasErrors(['role']);
    $this->actingAs($admin)->post("/staff/users/{$admin->ulid}/close")->assertSessionHasErrors();

    expect($admin->fresh()->hasRole('administrator'))->toBeTrue()
        ->and($admin->fresh()->deleted_at)->toBeNull();
});

test('category and location trees support create, move and leaf delete', function () {
    $admin = adminUser();

    $this->actingAs($admin)->post('/staff/categories', ['name' => 'Poetry'])->assertSessionHasNoErrors();
    $poetry = Category::where('slug', 'poetry')->firstOrFail();
    expect($poetry->depth)->toBe(0);

    $this->actingAs($admin)->post('/staff/categories', ['name' => 'Haiku', 'parent_id' => $poetry->id])->assertSessionHasNoErrors();
    $haiku = Category::where('slug', 'haiku')->firstOrFail();
    expect(str_contains($haiku->path, "/{$poetry->id}/"))->toBeTrue();

    // moving Haiku under a new root recomputes the subtree path
    $this->actingAs($admin)->post('/staff/categories', ['name' => 'Anthology'])->assertSessionHasNoErrors();
    $anthology = Category::where('slug', 'anthology')->firstOrFail();

    $this->actingAs($admin)->post("/staff/categories/{$haiku->id}/update", [
        'name' => 'Haiku', 'parent_id' => $anthology->id,
    ])->assertSessionHasNoErrors();

    expect(str_contains($haiku->fresh()->path, "/{$anthology->id}/"))->toBeTrue();

    // cannot delete a parent with children: the node must still exist
    $this->actingAs($admin)->delete("/staff/categories/{$anthology->id}");
    expect(Category::find($anthology->id))->not->toBeNull();
    // leaf delete works
    $this->actingAs($admin)->delete("/staff/categories/{$haiku->id}")->assertSessionHasNoErrors();
    expect(Category::find($haiku->id))->toBeNull();

    // locations same pattern
    $this->actingAs($admin)->post('/staff/locations', ['name' => 'New floor', 'code' => 'NF1', 'type' => 'floor'])->assertSessionHasNoErrors();
    $floor = Location::where('code', 'NF1')->firstOrFail();
    $this->actingAs($admin)->delete("/staff/locations/{$floor->id}")->assertSessionHasNoErrors();
    expect(Location::find($floor->id))->toBeNull();
});

test('admin can edit a loan policy and invalid ranges are refused', function () {
    $admin = adminUser();
    $policy = LoanPolicy::factory()->create(['default_hours' => 240, 'min_hours' => 168, 'max_hours' => 360]);

    $this->actingAs($admin)->post("/staff/policies/{$policy->id}", [
        'default_hours' => 260,
        'min_hours' => 200,
        'max_hours' => 400,
        'renewals_allowed' => 3,
        'special_material_factor' => 0.5,
        'grace_hours' => 24,
        'max_active_loans_per_user' => 6,
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    expect($policy->fresh()->default_hours)->toBe(260);

    $this->actingAs($admin)->post("/staff/policies/{$policy->id}", [
        'default_hours' => 500,
        'min_hours' => 600,
        'max_hours' => 700,
        'renewals_allowed' => 1,
        'special_material_factor' => 0.5,
        'grace_hours' => 24,
        'max_active_loans_per_user' => 5,
    ])->assertSessionHas('error');
});

test('admin management screens render', function () {
    $admin = adminUser();

    $this->actingAs($admin)->get('/staff/users')->assertOk()->assertInertia(fn ($p) => $p->component('Staff/Users'));
    $this->actingAs($admin)->get('/staff/roles')->assertOk()->assertInertia(fn ($p) => $p->component('Staff/Roles'));
    $this->actingAs($admin)->get('/staff/categories')->assertOk()->assertInertia(fn ($p) => $p->component('Staff/Categories'));
    $this->actingAs($admin)->get('/staff/locations')->assertOk()->assertInertia(fn ($p) => $p->component('Staff/Locations'));
    $this->actingAs($admin)->get('/staff/policies')->assertOk()->assertInertia(fn ($p) => $p->component('Staff/Policies'));
});
