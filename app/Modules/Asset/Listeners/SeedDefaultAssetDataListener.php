<?php

declare(strict_types=1);

namespace App\Modules\Asset\Listeners;

use App\Core\Events\HouseholdCreated;
use App\Modules\Asset\Actions\SeedDefaultAssetData;

final class SeedDefaultAssetDataListener
{
    public function __construct(
        private readonly SeedDefaultAssetData $seeder,
    ) {
    }

    public function handle(HouseholdCreated $event): void
    {
        if (! config('modules.asset', false)) {
            return;
        }

        $this->seeder->handle($event->household, $event->owner);
    }
}
