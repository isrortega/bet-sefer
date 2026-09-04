<?php

use App\Enums\LoanType;
use App\Exceptions\LoanTermsOutOfRange;
use App\Models\Copy;
use App\Models\Edition;
use App\Models\LibraryHour;
use App\Models\LoanPolicy;
use App\Services\Circulation\LoanPolicyResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedPolicy(LoanType $type, int $default, int $min, int $max, int $renewals = 0, float $factor = 0.50): LoanPolicy
{
    return LoanPolicy::factory()->create([
        'loan_type' => $type,
        'default_hours' => $default,
        'min_hours' => $min,
        'max_hours' => $max,
        'renewals_allowed' => $renewals,
        'special_material_factor' => $factor,
    ]);
}

function copyFor(LoanType $type, bool $special = false): Copy
{
    $edition = Edition::factory()->create(['loan_type' => $type, 'special_material' => $special]);

    return Copy::factory()->create(['edition_id' => $edition->id]);
}

beforeEach(function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-09 10:00:00', 'America/Bogota'));
    foreach ([0, 1, 2, 3, 4] as $w) {
        LibraryHour::create(['weekday' => $w, 'opens_at' => '08:00', 'closes_at' => '18:00', 'is_closed' => false]);
    }
    LibraryHour::create(['weekday' => 5, 'opens_at' => '09:00', 'closes_at' => '13:00', 'is_closed' => false]);
    LibraryHour::create(['weekday' => 6, 'is_closed' => true]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('general loan resolves default, min and max hours', function () {
    $policy = seedPolicy(LoanType::General, 240, 168, 360, 2);
    $copy = copyFor(LoanType::General);
    $resolver = new LoanPolicyResolver;

    $default = $resolver->resolve($copy);
    expect($default->hours)->toBe(240);

    $min = $resolver->resolve($copy, 168);
    expect($min->hours)->toBe(168);

    $max = $resolver->resolve($copy, 360);
    expect($max->hours)->toBe(360)
        ->and($max->policySnapshot['hours'])->toBe(360)
        ->and($max->policySnapshot['renewals_allowed'])->toBe(2);
});

test('reference and periodical loans resolve their own ranges', function () {
    seedPolicy(LoanType::Reference, 36, 24, 48, 0);
    seedPolicy(LoanType::Periodical, 96, 24, 168, 1);

    $ref = (new LoanPolicyResolver)->resolve(copyFor(LoanType::Reference));
    $per = (new LoanPolicyResolver)->resolve(copyFor(LoanType::Periodical));

    expect($ref->hours)->toBe(36)
        ->and($ref->policySnapshot['loan_type'])->toBe('reference')
        ->and($per->hours)->toBe(96)
        ->and($per->policySnapshot['renewals_allowed'])->toBe(1);
});

test('special material halves the range and rounds down', function () {
    seedPolicy(LoanType::General, 240, 168, 360);
    $copy = copyFor(LoanType::General, true);

    $terms = (new LoanPolicyResolver)->resolve($copy);

    expect($terms->hours)->toBe(120)
        ->and($terms->policySnapshot['min_hours'])->toBe(84)
        ->and($terms->policySnapshot['max_hours'])->toBe(180);
});

test('special material floors at one hour', function () {
    seedPolicy(LoanType::General, 240, 10, 360, 0, 0.01);
    $copy = copyFor(LoanType::General, true);

    $min = (new LoanPolicyResolver)->resolve($copy, 1);

    expect($min->hours)->toBe(1)
        ->and($min->policySnapshot['min_hours'])->toBe(1);
});

test('a requested duration outside the range throws', function () {
    seedPolicy(LoanType::General, 240, 168, 360);

    expect(fn () => (new LoanPolicyResolver)->resolve(copyFor(LoanType::General), 100))
        ->toThrow(LoanTermsOutOfRange::class);
});

test('due date is pushed to the next opening moment', function () {
    seedPolicy(LoanType::General, 240, 168, 360);
    $copy = copyFor(LoanType::General);

    $terms = (new LoanPolicyResolver)->resolve($copy, 168);

    expect($terms->dueAt->greaterThanOrEqualTo(
        CarbonImmutable::parse('2026-09-16 08:00:00', 'America/Bogota')
    ))->toBeTrue();
});
