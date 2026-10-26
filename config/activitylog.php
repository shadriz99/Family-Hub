<?php

declare(strict_types=1);

return [
    'activity_model' => App\Core\Models\Activity::class,
    // Audit writes must share the business transaction, not run after the response.
    'buffer' => ['enabled' => false],
];
