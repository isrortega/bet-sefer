<?php

namespace App\Services\Circulation;

use App\Models\Holiday;
use App\Models\LibraryHour;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Finds the next moment the library is open, so that due dates land on opening
 * hours instead of a 24h loan falling due while the library is shut.
 *
 * Weekday convention: 0 = Monday ... 6 = Sunday (matches library_hours).
 */
final class BusinessCalendar
{
    private const MAX_SCAN_DAYS = 14;

    public function nextOpeningMoment(CarbonImmutable $at): CarbonImmutable
    {
        $tz = config('app.timezone');
        $at = $at->setTimezone($tz);

        $holidays = Holiday::all();
        $hours = LibraryHour::all()->keyBy('weekday');

        for ($offset = 0; $offset < self::MAX_SCAN_DAYS; $offset++) {
            $day = $at->startOfDay()->addDays($offset);
            $weekday = $day->dayOfWeekIso - 1; // 0 = Monday

            if ($this->isHoliday($day, $holidays)) {
                continue;
            }

            $row = $hours[$weekday] ?? null;
            if ($row === null || $row->is_closed || $row->opens_at === null || $row->closes_at === null) {
                continue;
            }

            $opening = $this->dayAt($day, $row->opens_at);
            $closing = $this->dayAt($day, $row->closes_at);

            if ($offset === 0 && $at >= $opening && $at < $closing) {
                return $at; // already inside opening hours
            }

            if ($offset === 0 && $opening <= $at) {
                continue; // the library already opened and will close before $at
            }

            return $opening;
        }

        Log::warning('BusinessCalendar found no opening moment within 14 days; returning input unchanged.', ['at' => $at->toIso8601String()]);

        return $at;
    }

    /** @param iterable<Holiday> $holidays */
    private function isHoliday(CarbonImmutable $day, iterable $holidays): bool
    {
        $date = $day->format('Y-m-d');
        $monthDay = $day->format('m-d');

        foreach ($holidays as $holiday) {
            $h = CarbonImmutable::parse((string) $holiday->date);

            if ($holiday->is_recurring) {
                if ($h->format('m-d') === $monthDay) {
                    return true;
                }
            } elseif ($h->format('Y-m-d') === $date) {
                return true;
            }
        }

        return false;
    }

    private function dayAt(CarbonImmutable $day, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $day->setHour($hour)->setMinute($minute)->setSecond(0)->setMicrosecond(0);
    }
}
