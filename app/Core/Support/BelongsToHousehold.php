<?php

declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Models\Household;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Tenant scope and household_id auto-fill for records that belong to one household.
 *
 * @mixin Model
 */
trait BelongsToHousehold
{
    protected static function bootBelongsToHousehold(): void
    {
        static::addGlobalScope(new HouseholdScope());

        static::creating(static function (Model $model): void {
            $householdId = $model->getAttribute('household_id');
            $currentId = app(HouseholdContext::class)->current()->getKey();

            if ($householdId === null) {
                $model->setAttribute('household_id', $currentId);

                return;
            }

            if ((string) $householdId !== (string) $currentId) {
                throw new LogicException('A household record cannot be created for another household.');
            }
        });
    }

    /**
     * @return BelongsTo<Household, $this>
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
