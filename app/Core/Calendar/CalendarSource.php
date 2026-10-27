<?php

declare(strict_types=1);

namespace App\Core\Calendar;

use App\Core\Models\Household;
use Carbon\CarbonImmutable;

interface CalendarSource
{
    public function key(): string;

    public function label(): string;

    /**
     * @return iterable<CalendarItem>
     */
    public function items(Household $h, CarbonImmutable $from, CarbonImmutable $to): iterable;
}
