<?php

declare(strict_types=1);

namespace App\Modules\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TransactionUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $transactionId,
        public ?string $oldAccountId = null,
        public ?string $oldToAccountId = null,
    ) {
    }
}
