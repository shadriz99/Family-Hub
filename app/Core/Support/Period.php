<?php

declare(strict_types=1);

namespace App\Core\Support;

use Carbon\CarbonImmutable;

final readonly class Period
{
    public string $key;

    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {
        $this->key = $start->toDateString();
    }
}
