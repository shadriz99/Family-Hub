<?php

declare(strict_types=1);

namespace App\Modules\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class BudgetThresholdReached
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $budgetId,
        public int $threshold, // 80 or 100
    ) {
    }
}
