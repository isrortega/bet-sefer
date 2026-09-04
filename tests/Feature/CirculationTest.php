<?php

use App\Enums\CopyStatus;
use App\Models\Copy;
use App\Models\Loan;
use App\Models\LoanPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    LoanPolicy::factory()->create();
});

function circulationRole(string $name, array $permissions): Role
{
    $role = Role::findOrCreate($name, 'web');
    foreach ($permissions as $permission) {
        $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $role;
}

function librarian(): User
{
    $librarian = User::factory()->active()->create();
    $librarian->syncRoles(circulationRole('librarian', [
        'loans.create', 'loans.return', 'loans.renew', 'loans.view_any', 'copies.transition',
    ]));

    return $librarian;
}

function activeReader(): User
{
    $reader = User::factory()->active()->create();
    $reader->syncRoles(Role::findOrCreate('reader', 'web'));

    return $reader;
}

function availableCopy(): Copy
{
    return Copy::factory()->create(['status' => CopyStatus::Available]);
}

test('checkout happy path: loan is created, copy goes on loan, due in the future', function () {
    $librarian = librarian();
    $reader = activeReader();
    $copy = availableCopy();

    $this->actingAs($librarian)
        ->post('/staff/desk/checkout', ['member' => $reader->member_code, 'code' => $copy->code])
        ->assertSessionHasNoErrors();

    $loan = Loan::where('copy_id', $copy->id)->whereNull('returned_at')->first();
    expect($loan)->not->toBeNull()
        ->and($copy->fresh()->status)->toBe(CopyStatus::OnLoan)
        ->and($loan->user_id)->toBe($reader->id)
        ->and($loan->checked_out_by_id)->toBe($librarian->id)
        ->and($loan->due_at->isFuture())->toBeTrue()
        ->and($loan->policy_snapshot['loan_type'])->toBe('general');
});

test('checkout is refused for a reader whose identity is pending', function () {
    $librarian = librarian();
    $reader = User::factory()->pendingIdentity()->create();
    $reader->syncRoles(Role::findOrCreate('reader', 'web'));
    $copy = availableCopy();

    $this->actingAs($librarian)
        ->post('/staff/desk/checkout', ['member' => $reader->member_code, 'code' => $copy->code])
        ->assertSessionHasErrors(['code']);

    expect($copy->fresh()->status)->toBe(CopyStatus::Available);
});

test('checkout is refused for a restricted copy and for a copy on loan', function () {
    $librarian = librarian();
    $reader = activeReader();

    $restricted = Copy::factory()->restricted()->create();
    $this->actingAs($librarian)
        ->post('/staff/desk/checkout', ['member' => $reader->member_code, 'code' => $restricted->code])
        ->assertSessionHasErrors(['code']);

    $onLoan = Copy::factory()->onLoan()->create();
    $this->actingAs($librarian)
        ->post('/staff/desk/checkout', ['member' => $reader->member_code, 'code' => $onLoan->code])
        ->assertSessionHasErrors(['code']);
});

test('a reader with an overdue loan cannot check out', function () {
    $librarian = librarian();
    $reader = activeReader();
    $copy = availableCopy();

    Loan::factory()->overdue()->create(['user_id' => $reader->id, 'copy_id' => availableCopy()->id]);

    $this->actingAs($librarian)
        ->post('/staff/desk/checkout', ['member' => $reader->member_code, 'code' => $copy->code])
        ->assertSessionHasErrors(['code']);
});

test('checking in moves the copy to at_reception', function () {
    $librarian = librarian();
    $reader = activeReader();
    $copy = availableCopy();

    $loan = Loan::factory()->create(['user_id' => $reader->id, 'copy_id' => $copy->id, 'checked_out_by_id' => $librarian->id]);
    $copy->forceFill(['status' => CopyStatus::OnLoan])->save();

    $this->actingAs($librarian)->post('/staff/desk/checkin', ['code' => $copy->code]);

    expect($copy->fresh()->status)->toBe(CopyStatus::AtReception)
        ->and($loan->fresh()->returned_at)->not->toBeNull();
});

test('a loan can be renewed once and then no more', function () {
    $librarian = librarian();
    $reader = activeReader();
    $copy = availableCopy();

    $loan = Loan::factory()->create([
        'user_id' => $reader->id,
        'copy_id' => $copy->id,
        'checked_out_by_id' => $librarian->id,
        'due_at' => now()->addDays(2),
        'renewals_count' => 0,
        'policy_snapshot' => ['default_hours' => 240, 'renewals_allowed' => 1],
    ]);

    $this->actingAs($librarian)->post('/staff/desk/renew', ['code' => $loan->code])
        ->assertSessionHasNoErrors();
    expect($loan->fresh()->renewals_count)->toBe(1);

    $this->actingAs($librarian)->post('/staff/desk/renew', ['code' => $loan->code])
        ->assertSessionHasErrors(['code']);
});

test('a shelver cannot check out but can advance the shelving queue', function () {
    $shelver = User::factory()->active()->create();
    $shelver->syncRoles(circulationRole('shelver', ['copies.transition']));
    $reader = activeReader();
    $copy = availableCopy();

    $this->actingAs($shelver)->post('/staff/desk/checkout', ['member' => $reader->member_code, 'code' => $copy->code])
        ->assertForbidden();

    $copy->forceFill(['status' => CopyStatus::AtReception])->save();
    $this->actingAs($shelver)->post('/staff/shelving/advance', ['code' => $copy->code]);
    expect($copy->fresh()->status)->toBe(CopyStatus::InTransit);

    $this->actingAs($shelver)->post('/staff/shelving/advance', ['code' => $copy->code]);
    expect($copy->fresh()->status)->toBe(CopyStatus::Available);
});

test('a reader cannot reach the front desk', function () {
    $reader = activeReader();

    $this->actingAs($reader)->get('/staff/desk')->assertForbidden();
    $this->actingAs($reader)->get('/staff/shelving')->assertForbidden();
});

test('a second checkout attempt of the same copy is refused', function () {
    $librarian = librarian();
    $reader = activeReader();
    $copy = availableCopy();

    $this->actingAs($librarian)
        ->post('/staff/desk/checkout', ['member' => $reader->member_code, 'code' => $copy->code])
        ->assertSessionHasNoErrors();

    $this->actingAs($librarian)
        ->post('/staff/desk/checkout', ['member' => $reader->member_code, 'code' => $copy->code])
        ->assertSessionHasErrors(['code']);

    expect(Loan::where('copy_id', $copy->id)->count())->toBe(1);
});
