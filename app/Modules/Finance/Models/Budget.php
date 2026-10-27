<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Models\Household;
use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $household_id
 * @property string $category_id
 * @property string $period
 * @property int $amount
 * @property int $percentage
 * @property CarbonImmutable|null $alert_80_sent_at
 * @property CarbonImmutable|null $alert_100_sent_at
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Household $household
 * @property-read Category $category
 * @property-read User|null $creator
 */
class Budget extends Model
{
    use BelongsToHousehold;
    use HasUlids;

    protected $table = 'fin_budgets';

    protected $fillable = [
        'household_id',
        'category_id',
        'period',
        'amount',
        'percentage',
        'alert_80_sent_at',
        'alert_100_sent_at',
        'created_by',
    ];

    protected $attributes = [
        'amount' => 0,
        'percentage' => 0,
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'percentage' => 'integer',
            'alert_80_sent_at' => 'immutable_datetime',
            'alert_100_sent_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
