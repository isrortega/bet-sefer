<?php

use App\Enums\CopyStatus;
use App\Exceptions\InvalidCopyTransition;
use App\Models\Copy;
use App\Models\CopyStatusTransition;
use App\Models\Location;
use App\Models\User;
use App\Services\Circulation\CopyStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function roleWithTransition(string $roleName): Role
{
    $role = Role::findOrCreate($roleName, 'web');
    $role->givePermissionTo(Permission::findOrCreate('copies.transition', 'web'));

    return $role;
}

function staffUser(string $roleName): User
{
    $user = User::factory()->active()->create();
    $user->assignRole(roleWithTransition($roleName));

    return $user;
}

function copyWithStatus(CopyStatus $status): Copy
{
    return Copy::factory()->create(['status' => $status, 'status_changed_at' => now()]);
}

test('every librarian transition succeeds and records a transition row', function () {
    $user = staffUser('librarian');
    $machine = new CopyStateMachine;

    $matrix = [
        [CopyStatus::Available, CopyStatus::OnLoan],
        [CopyStatus::OnLoan, CopyStatus::AtReception],
        [CopyStatus::AtReception, CopyStatus::InTransit],
        [CopyStatus::InTransit, CopyStatus::Available],
        [CopyStatus::Available, CopyStatus::InRepair],
        [CopyStatus::InRepair, CopyStatus::Available],
        [CopyStatus::OnLoan, CopyStatus::Lost],
    ];

    foreach ($matrix as [$from, $to]) {
        $copy = copyWithStatus($from);
        $machine->transition($copy, $to, $user);

        expect($copy->status)->toBe($to)
            ->and(CopyStatusTransition::where('copy_id', $copy->id)->latest('id')->first()?->to_status)->toBe($to->value);
    }
});

test('a disallowed transition throws for a librarian', function () {
    $user = staffUser('librarian');
    $copy = copyWithStatus(CopyStatus::Available);

    expect(fn () => (new CopyStateMachine)->transition($copy, CopyStatus::AtReception, $user))
        ->toThrow(InvalidCopyTransition::class);
});

test('a shelver cannot run on_loan to at_reception', function () {
    $user = staffUser('shelver');
    $copy = copyWithStatus(CopyStatus::OnLoan);

    expect(fn () => (new CopyStateMachine)->transition($copy, CopyStatus::AtReception, $user))
        ->toThrow(InvalidCopyTransition::class);
});

test('a shelver can advance reception to in transit to available', function () {
    $user = staffUser('shelver');
    $machine = new CopyStateMachine;

    $reception = copyWithStatus(CopyStatus::AtReception);
    $machine->transition($reception, CopyStatus::InTransit, $user);
    expect($reception->status)->toBe(CopyStatus::InTransit);

    $machine->transition($reception, CopyStatus::Available, $user, ['to_location_id' => $reception->location_id]);
    expect($reception->status)->toBe(CopyStatus::Available);
});

test('in_transit to available records both locations', function () {
    $user = staffUser('librarian');
    $oldLocation = Location::factory()->create();
    $newLocation = Location::factory()->create();
    $copy = Copy::factory()->create([
        'status' => CopyStatus::InTransit,
        'location_id' => $oldLocation->id,
    ]);

    (new CopyStateMachine)->transition($copy, CopyStatus::Available, $user, [
        'to_location_id' => $newLocation->id,
    ]);

    $row = CopyStatusTransition::where('copy_id', $copy->id)->latest('id')->first();

    expect($row?->from_location_id)->toBe($oldLocation->id)
        ->and($row?->to_location_id)->toBe($newLocation->id)
        ->and($copy->location_id)->toBe($newLocation->id);
});

test('only an administrator may recover a lost copy', function () {
    $admin = staffUser('administrator');
    $librarian = staffUser('librarian');
    $copy = copyWithStatus(CopyStatus::Lost);

    expect(fn () => (new CopyStateMachine)->transition($copy, CopyStatus::Available, $librarian))
        ->toThrow(InvalidCopyTransition::class);

    (new CopyStateMachine)->transition($copy, CopyStatus::Available, $admin);

    expect($copy->status)->toBe(CopyStatus::Available);
});

test('a user without copies.transition cannot move any copy', function () {
    $user = User::factory()->active()->create();
    $copy = copyWithStatus(CopyStatus::Available);

    expect(fn () => (new CopyStateMachine)->transition($copy, CopyStatus::OnLoan, $user))
        ->toThrow(InvalidCopyTransition::class);
});
