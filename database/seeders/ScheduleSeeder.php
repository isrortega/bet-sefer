<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\LibraryHour;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHours();
        $this->seedHolidays(now()->year);
    }

    private function seedHours(): void
    {
        $rows = [
            [0, '08:00', '18:00'], // Monday
            [1, '08:00', '18:00'], // Tuesday
            [2, '08:00', '18:00'], // Wednesday
            [3, '08:00', '18:00'], // Thursday
            [4, '08:00', '18:00'], // Friday
            [5, '09:00', '13:00'], // Saturday
            [6, null, null, true], // Sunday
        ];

        LibraryHour::query()->delete();

        foreach ($rows as $row) {
            LibraryHour::create([
                'weekday' => $row[0],
                'opens_at' => $row[1],
                'closes_at' => $row[2],
                'is_closed' => $row[3] ?? false,
            ]);
        }
    }

    private function seedHolidays(int $year): void
    {
        Holiday::query()->delete();

        $easter = $this->easterSunday($year);
        $fixed = [
            [CarbonImmutable::create($year, 1, 1), 'New Year', true],
            [CarbonImmutable::create($year, 5, 1), 'Labour Day', true],
            [CarbonImmutable::create($year, 7, 20), 'Independence Day', true],
            [CarbonImmutable::create($year, 8, 7), 'Battle of Boyacá', true],
            [CarbonImmutable::create($year, 12, 8), 'Immaculate Conception', true],
            [CarbonImmutable::create($year, 12, 25), 'Christmas Day', true],
        ];

        $movable = [
            $this->nextMonday(CarbonImmutable::create($year, 1, 6)),   // Epiphany
            $this->nextMonday(CarbonImmutable::create($year, 3, 19)),  // Saint Joseph
            $this->nextMonday(CarbonImmutable::create($year, 6, 29)),  // Saint Peter & Paul
            $this->nextMonday(CarbonImmutable::create($year, 8, 15)),  // Assumption
            $this->nextMonday(CarbonImmutable::create($year, 10, 12)), // Columbus Day
            $this->nextMonday(CarbonImmutable::create($year, 11, 1)),  // All Saints
            $this->nextMonday(CarbonImmutable::create($year, 11, 11)), // Cartagena
            $easter->subDays(3),   // Maundy Thursday
            $easter->subDays(2),   // Good Friday
            $easter->addDays(43),  // Ascension
            $easter->addDays(64),  // Corpus Christi
            $easter->addDays(71),  // Sacred Heart
        ];

        $names = ['Epiphany', 'Saint Joseph', 'Saint Peter and Paul', 'Assumption', 'Columbus Day', 'All Saints', 'Independence of Cartagena', 'Maundy Thursday', 'Good Friday', 'Ascension', 'Corpus Christi', 'Sacred Heart'];

        foreach ($fixed as [$date, $name, $recurring]) {
            Holiday::create(['date' => $date->toDateString(), 'name' => $name, 'is_recurring' => $recurring]);
        }

        foreach ($movable as $i => $date) {
            Holiday::create(['date' => $date->toDateString(), 'name' => $names[$i], 'is_recurring' => false]);
        }
    }

    private function nextMonday(CarbonImmutable $date): CarbonImmutable
    {
        return $date->isMonday() ? $date : $date->next(CarbonImmutable::MONDAY);
    }

    private function easterSunday(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day);
    }
}
