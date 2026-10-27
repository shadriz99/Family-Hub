<?php

declare(strict_types=1);

use App\Modules\Finance\Providers\FinanceServiceProvider;
use App\Modules\Organizer\Providers\OrganizerServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    FinanceServiceProvider::class,
    OrganizerServiceProvider::class,
];
