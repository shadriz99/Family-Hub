<?php

declare(strict_types=1);

namespace App\Core\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filters every tenant-owned query to the active household. The context is
 * required, so a forgotten job or command context fails closed.
 *
 * @implements Scope<Model>
 */
final class HouseholdScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where(
            $model->qualifyColumn('household_id'),
            app(HouseholdContext::class)->current()->getKey(),
        );
    }
}
