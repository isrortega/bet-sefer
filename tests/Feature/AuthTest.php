<?php

use App\Enums\UserStatus;
use App\Mail\VerifyAccountEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function passwordUser(array $overrides = []): User
{
    Role::findOrCreate('reader', 'web');

    $user = User::factory()->active()->create($overrides);
    $user->password = bcrypt('correct-password-123');
    $user->save();

    return $user;
}

test('the login page is public', function () {
    $this->get('/login')->assertOk();
});

test('a reader can sign in with email and password', function () {
    $user = passwordUser();
    $user->syncRoles(['reader']);

    $this->post('/login', ['email' => $user->email, 'password' => 'correct-password-123'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

test('registration creates a pending_email account and queues verification', function () {
    Mail::fake();

    $this->post('/register', [
        'name' => 'New Reader',
        'email' => 'new@reader.test',
        'password' => 'another-secret-123',
        'password_confirmation' => 'another-secret-123',
    ])->assertRedirect(route('login'));

    $user = User::where('email', 'new@reader.test')->firstOrFail();
    expect($user->status)->toBe(UserStatus::PendingEmail)
        ->and(strlen($user->member_code))->toBe(8)
        ->and($user->hasRole('reader'))->toBeTrue();

    Mail::assertQueued(VerifyAccountEmail::class);
});

test('registration explains that a closed account must be reopened by an admin', function () {
    User::factory()->create(['email' => 'closed@reader.test', 'deleted_at' => now()]);

    $this->post('/register', [
        'name' => 'Someone',
        'email' => 'closed@reader.test',
        'password' => 'another-secret-123',
        'password_confirmation' => 'another-secret-123',
    ])->assertSessionHasErrors(['email']);
});

test('email verification moves the reader to pending_identity', function () {
    Role::findOrCreate('reader', 'web');
    $user = User::factory()->create(['status' => UserStatus::PendingEmail, 'email_verified_at' => null]);
    $user->syncRoles(['reader']);

    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'user' => $user->ulid,
        'hash' => sha1($user->email),
    ]);

    $this->get($url)->assertRedirect();

    expect($user->fresh()->status)->toBe(UserStatus::PendingIdentity);
});

test('a reader cannot reach any staff route', function () {
    Role::findOrCreate('reader', 'web');
    $user = User::factory()->active()->create();
    $user->syncRoles(['reader']);

    $this->actingAs($user)->get('/staff/readers')->assertForbidden();
});

test('an anonymous visitor cannot open the account area', function () {
    $this->get('/account')->assertRedirect(route('login'));
});

test('a reader only sees their own loans on their dashboard', function () {
    $reader = passwordUser();
    $reader->syncRoles(['reader']);

    $this->actingAs($reader)->get('/account')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Account/Dashboard'));
});
