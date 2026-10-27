<?php

declare(strict_types=1);

namespace App\Modules\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TransactionDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $transactionId,
        public string $accountId,
        public ?string $toAccountId = null,
        public ?string $plannedItemId = null,
        public ?string $categoryId = null,
    ) {
    }
}
