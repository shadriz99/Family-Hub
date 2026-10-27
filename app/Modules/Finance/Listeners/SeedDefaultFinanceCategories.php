<?php

declare(strict_types=1);

namespace App\Modules\Finance\Listeners;

use App\Core\Events\HouseholdCreated;
use App\Modules\Finance\Actions\SeedDefaultCategories;

class SeedDefaultFinanceCategories
{
    public function __construct(
        private readonly SeedDefaultCategories $seedDefaultCategories,
    ) {
    }

    public function handle(HouseholdCreated $event): void
    {
        if (! config('modules.finance', false)) {
            return;
        }

        $this->seedDefaultCategories->handle($event->household, $event->owner);
    }
}
