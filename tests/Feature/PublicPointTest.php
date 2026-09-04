<?php

use App\Enums\CopyStatus;
use App\Models\Copy;
use App\Models\DemandEvent;
use App\Models\Edition;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function editionWithCopy(array $copyState = []): Copy
{
    return Copy::factory()->create(['status' => CopyStatus::Available, ...$copyState]);
}

function editionWithIsbn(): Copy
{
    $edition = Edition::factory()->withIsbn()->create();

    return Copy::factory()->create(['edition_id' => $edition->id, 'status' => CopyStatus::Available]);
}

test('a public copy page shows only allow-listed fields', function () {
    $copy = editionWithCopy();

    $response = $this->get("/i/{$copy->code}")->assertOk();

    $response->assertHeader('X-Robots-Tag', 'noindex');
    $response->assertInertia(fn ($page) => $page->component('Public/Copy'));
});

test('the public surface never leaks reader identity or loan dates', function () {
    $reader = User::factory()->active()->create(['name' => 'Confidential-Person', 'email' => 'confidential@example.test']);
    $copy = editionWithIsbn();
    $edition = $copy->edition;

    Loan::factory()->create([
        'copy_id' => $copy->id,
        'user_id' => $reader->id,
        'checked_out_by_id' => User::factory()->active()->create()->id,
        'due_at' => now()->addDays(3),
    ]);
    $copy->forceFill(['status' => CopyStatus::OnLoan])->save();

    $body = $this->get("/lookup/{$edition->isbn_13}")->assertOk()->baseResponse->getContent();

    expect($body)->not->toContain('confidential@example.test')
        ->not->toContain('Confidential-Person')
        ->not->toContain($reader->member_code);
});

test('an unknown copy code returns 404', function () {
    $this->get('/i/BS-00000000')->assertNotFound();
});

test('isbn lookup renders the edition with availability counts', function () {
    $copy = editionWithIsbn();

    $this->get("/lookup/{$copy->edition->isbn_13}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/Edition'));
});

test('isbn not in catalogue offers a suggestion that records a demand event', function () {
    $isbn = '9783161484100';

    $this->get("/lookup/{$isbn}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/Lookup'));

    $this->post('/lookup/suggest', ['isbn' => $isbn])->assertSessionHasNoErrors();

    expect(DemandEvent::where('type', 'acquisition_suggestion')->where('isbn', $isbn)->exists())->toBeTrue();
});

test('a copy that is on loan shows no estimated availability for its edition when another copy is free', function () {
    $a = editionWithIsbn();
    Copy::factory()->create(['edition_id' => $a->edition_id, 'status' => CopyStatus::OnLoan]);

    $this->get("/lookup/{$a->edition->isbn_13}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Public/Edition'));
});
