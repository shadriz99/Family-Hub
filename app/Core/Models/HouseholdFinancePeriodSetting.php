<?php

declare(strict_types=1);

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseholdFinancePeriodSetting extends Model
{
    use HasUlids;

    protected $fillable = ['start_day', 'effective_on'];

    protected function casts(): array
    {
        return ['start_day' => 'integer', 'effective_on' => 'immutable_date'];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
