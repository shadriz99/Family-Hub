<?php

declare(strict_types=1);

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $timezone
 * @property int $finance_period_start_day
 * @property bool $finance_integration_enabled
 */
class Household extends Model
{
    use HasUlids;
    use SoftDeletes;

    protected $fillable = ['name', 'timezone', 'finance_period_start_day', 'finance_integration_enabled'];

    protected $attributes = [
        'timezone' => 'Asia/Jakarta',
        'finance_period_start_day' => 1,
        'finance_integration_enabled' => true,
    ];

    protected function casts(): array
    {
        return ['finance_period_start_day' => 'integer', 'finance_integration_enabled' => 'boolean'];
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
}
