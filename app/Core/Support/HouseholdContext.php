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

    public function currentOrNull(): ?Household
    {
        return $this->household;
    }

    public function current(): Household
    {
        return $this->household ?? throw new LogicException('No household context is active.');
    }

    /**
     * Run a callback within the context of the given household, restoring the previous context afterwards.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function runInContext(Household $household, callable $callback): mixed
    {
        $previous = $this->household;
        $this->use($household);

        try {
            return $callback();
        } finally {
            if ($previous !== null) {
                $this->use($previous);
            } else {
                $this->forget();
            }
        }
    }
}
