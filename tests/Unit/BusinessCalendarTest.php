<?php

use App\Models\Holiday;
use App\Models\LibraryHour;
use App\Services\Circulation\BusinessCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedSchedule(): void
{
    config(['app.timezone' => 'America/Bogota']);

    foreach ([0, 1, 2, 3, 4] as $weekday) {
        LibraryHour::create(['weekday' => $weekday, 'opens_at' => '08:00', 'closes_at' => '18:00', 'is_closed' => false]);
    }
    LibraryHour::create(['weekday' => 5, 'opens_at' => '09:00', 'closes_at' => '13:00', 'is_closed' => false]);
    LibraryHour::create(['weekday' => 6, 'is_closed' => true]);
}

function bogota(string $datetime): CarbonImmutable
{
    return CarbonImmutable::parse($datetime, 'America/Bogota');
}

test('a moment inside opening hours is untouched', function () {
    seedSchedule();
    $at = bogota('2026-09-09 10:30:00'); // Wednesday

    expect((new BusinessCalendar)->nextOpeningMoment($at)->toDateTimeString())
        ->toBe('2026-09-09 10:30:00');
});

test('a 24h reference loan started Saturday afternoon lands on Monday opening', function () {
    seedSchedule();
    $sat = bogota('2026-09-12 17:00:00'); // Saturday, closes 13:00

    $due = (new BusinessCalendar)->nextOpeningMoment($sat->addHours(24));

    expect($due->format('Y-m-d H:i'))->toBe('2026-09-14 08:00'); // Monday opening
});

test('a fixed holiday pushes the due date to the next open day', function () {
    seedSchedule();
    Holiday::create(['date' => '2026-09-11', 'name' => 'Testing day', 'is_recurring' => false]);

    $thuEvening = bogota('2026-09-10 19:00:00'); // after close

    $due = (new BusinessCalendar)->nextOpeningMoment($thuEvening);

    expect($due->format('Y-m-d H:i'))->toBe('2026-09-12 09:00'); // Fri is the holiday, Sat opens 09:00
});

test('a recurring holiday matches in any year', function () {
    seedSchedule();
    Holiday::create(['date' => '2001-10-12', 'name' => 'Always off', 'is_recurring' => true]);

    $mon = bogota('2026-10-12 08:00:00');

    $due = (new BusinessCalendar)->nextOpeningMoment($mon);

    expect($due->format('Y-m-d'))->toBe('2026-10-13');
});

test('no open day within 14 days returns the input unchanged', function () {
    LibraryHour::create(['weekday' => 0, 'opens_at' => '08:00', 'closes_at' => '18:00', 'is_closed' => false]);
    LibraryHour::create(['weekday' => 1, 'opens_at' => '08:00', 'closes_at' => '18:00', 'is_closed' => false]);
    LibraryHour::create(['weekday' => 2, 'opens_at' => '08:00', 'closes_at' => '18:00', 'is_closed' => false]);
    LibraryHour::create(['weekday' => 3, 'opens_at' => '08:00', 'closes_at' => '18:00', 'is_closed' => false]);
    LibraryHour::create(['weekday' => 4, 'opens_at' => '08:00', 'closes_at' => '18:00', 'is_closed' => false]);

    foreach (range(0, 20) as $day) {
        Holiday::create(['date' => '2026-10-'.str_pad((string) (5 + $day), 2, '0', STR_PAD_LEFT), 'name' => 'Holiday', 'is_recurring' => false]);
    }

    $at = bogota('2026-10-05 10:00:00');

    expect((new BusinessCalendar)->nextOpeningMoment($at)->toDateTimeString())
        ->toBe('2026-10-05 10:00:00');
});
