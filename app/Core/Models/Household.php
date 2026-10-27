<?php

declare(strict_types=1);

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $name
 * @property string $timezone
 * @property int $finance_period_start_day
 * @property bool $finance_integration_enabled
 * @property \Carbon\CarbonImmutable|null $onboarded_at
 */
class Household extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'timezone',
        'finance_period_start_day',
        'finance_integration_enabled',
        'onboarded_at',
    ];

    protected $attributes = [
        'timezone' => 'Asia/Jakarta',
        'finance_period_start_day' => 1,
        'finance_integration_enabled' => true,
    ];

    protected function casts(): array
    {
        return [
            'finance_period_start_day' => 'integer',
            'finance_integration_enabled' => 'boolean',
            'onboarded_at' => 'immutable_datetime',
        ];
    }

    public function isOnboarded(): bool
    {
        return $this->onboarded_at !== null;
    }

    /** @return HasMany<HouseholdMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(HouseholdMember::class);
    }

    /** @return HasMany<HouseholdFinancePeriodSetting, $this> */
    public function financePeriodSettings(): HasMany
    {
        return $this->hasMany(HouseholdFinancePeriodSetting::class)->orderBy('effective_on');
    }

    /** @return HasMany<HouseholdInvitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(HouseholdInvitation::class);
    }

    /** @return HasMany<\App\Modules\Finance\Models\Account, $this> */
    public function accounts(): HasMany
    {
        return $this->hasMany(\App\Modules\Finance\Models\Account::class);
    }

    /** @return HasMany<\App\Modules\Finance\Models\Category, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(\App\Modules\Finance\Models\Category::class);
    }

    /** @return HasMany<\App\Modules\Finance\Models\Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(\App\Modules\Finance\Models\Transaction::class);
    }

    /** @return HasMany<\App\Modules\Finance\Models\Budget, $this> */
    public function budgets(): HasMany
    {
        return $this->hasMany(\App\Modules\Finance\Models\Budget::class);
    }

    /** @return HasMany<\App\Modules\Finance\Models\AllocationPlan, $this> */
    public function allocationPlans(): HasMany
    {
        return $this->hasMany(\App\Modules\Finance\Models\AllocationPlan::class);
    }

    /** @return HasMany<\App\Modules\Finance\Models\RecurringBill, $this> */
    public function recurringBills(): HasMany
    {
        return $this->hasMany(\App\Modules\Finance\Models\RecurringBill::class);
    }

    /** @return HasMany<\App\Modules\Finance\Models\Loan, $this> */
    public function loans(): HasMany
    {
        return $this->hasMany(\App\Modules\Finance\Models\Loan::class);
    }

    /** @return HasMany<\App\Modules\Finance\Models\PlannedItem, $this> */
    public function plannedItems(): HasMany
    {
        return $this->hasMany(\App\Modules\Finance\Models\PlannedItem::class);
    }
}
