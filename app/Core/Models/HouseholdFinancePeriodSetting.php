<?php

declare(strict_types=1);

namespace App\Core\Models;

use App\Core\Support\BelongsToHousehold;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $start_day
 * @property \Carbon\CarbonImmutable $effective_on
 */
class HouseholdFinancePeriodSetting extends Model
{
    use BelongsToHousehold;
    use HasUlids;

    protected $fillable = ['start_day', 'effective_on'];

    protected function casts(): array
    {
        return ['start_day' => 'integer', 'effective_on' => 'immutable_date'];
    }
}
