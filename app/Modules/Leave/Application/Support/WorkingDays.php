<?php

namespace App\Modules\Leave\Application\Support;

use Carbon\CarbonImmutable;

final class WorkingDays
{
    /**
     * Weekdays between two dates, inclusive. Weekends are excluded because the
     * MVP has no per-employee weekly schedule yet.
     */
    public static function between(CarbonImmutable $start, CarbonImmutable $end): float
    {
        $count = 0;

        for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
            if ($day->isWeekday()) {
                $count++;
            }
        }

        return (float) $count;
    }
}
