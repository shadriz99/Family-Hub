<?php

declare(strict_types=1);

namespace App\Core\Alerts;

use App\Core\Models\Household;

interface AlertSource
{
    /**
     * @return iterable<Alert>
     */
    public function alerts(Household $h): iterable;
}
