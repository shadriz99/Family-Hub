<?php

declare(strict_types=1);

use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;

function currentHousehold(): Household
{
    return app(HouseholdContext::class)->current();
}
