<?php

namespace App\Support;

use App\Models\Venue;
use Carbon\CarbonInterface;

class VenueAvailability
{
    public static function isOpen(Venue $venue, ?CarbonInterface $at = null): bool
    {
        $now = ($at ?: now())->copy()->setTimezone($venue->timezone ?: 'America/Sao_Paulo');
        if ($venue->manual_open_state && $venue->manual_open_until?->isFuture()) {
            return $venue->manual_open_state === 'open';
        }
        $schedules = $venue->schedules()->where('active', true)->get();
        if ($schedules->isEmpty()) return true;

        foreach ($schedules as $schedule) {
            $opensAt = substr((string) $schedule->opens_at, 0, 5);
            $closesAt = substr((string) $schedule->closes_at, 0, 5);
            $overnight = $closesAt <= $opensAt;
            $open = $now->copy()->startOfDay()->addSeconds(self::seconds($opensAt));
            $close = $now->copy()->startOfDay()->addSeconds(self::seconds($closesAt));
            if ($overnight) $close = $close->addDay();
            if ((int) $schedule->day_of_week === (int) $now->dayOfWeek && $now->betweenIncluded($open, $close)) return true;
            if ($overnight && ((int) $schedule->day_of_week + 1) % 7 === (int) $now->dayOfWeek) {
                $previousOpen = $open->subDay();
                $previousClose = $close->subDay();
                if ($now->betweenIncluded($previousOpen, $previousClose)) return true;
            }
        }
        return false;
    }

    private static function seconds(string $time): int
    {
        [$hour, $minute, $second] = array_pad(array_map('intval', explode(':', $time)), 3, 0);
        return $hour * 3600 + $minute * 60 + $second;
    }
}
