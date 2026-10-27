<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use Carbon\CarbonImmutable;

final readonly class PlannedExpenseData
{
    public function __construct(
        public string $name,
        public string $categoryId,
        public ?string $accountId,
        public int $amount,
        public CarbonImmutable $dueDate,
        public string $sourceType,
        public string $sourceId,
        public ?string $allocation = null,
    ) {
    }
}
