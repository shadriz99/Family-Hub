<?php

declare(strict_types=1);

namespace App\Core\Calendar;

use Carbon\CarbonImmutable;

final readonly class CalendarItem
{
    public function __construct(
        public string $uid,
        public string $sourceKey,
        public string $title,
        public CarbonImmutable $start,
        public ?CarbonImmutable $end,
        public bool $allDay,
        public string $color,
        public string $url,
        public ?string $status = null,
        public ?string $description = null,
        public ?string $memberId = null,
    ) {
    }
}
