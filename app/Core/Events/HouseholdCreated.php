<?php

declare(strict_types=1);

namespace App\Core\Events;

use App\Core\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HouseholdCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Household $household,
        public User $owner,
    ) {
    }
}
