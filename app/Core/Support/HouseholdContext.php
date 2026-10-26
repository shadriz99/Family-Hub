<?php

declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Models\Household;
use LogicException;

/**
 * Request/job scoped household boundary. Must be set explicitly before querying
 * tenant-owned records; a missing context fails closed instead of leaking data.
 */
final class HouseholdContext
{
    private ?Household $household = null;

    public function use(Household $household): void
    {
        $this->household = $household;
    }

    public function forget(): void
    {
        $this->household = null;
    }

    public function current(): Household
    {
        return $this->household ?? throw new LogicException('No household context is active.');
    }
}
