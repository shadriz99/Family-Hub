<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use Carbon\CarbonImmutable;

final readonly class ExpenseData
{
    public function __construct(
        public string $accountId,
        public string $categoryId,
        public int $amount,
        public CarbonImmutable $occurredOn,
        public ?string $note,
        public string $sourceType,
        public string $sourceId,
        public ?string $plannedItemId = null,
        public ?string $allocation = null,
    ) {
    }
}
