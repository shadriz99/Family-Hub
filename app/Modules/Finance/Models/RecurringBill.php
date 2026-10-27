<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Models\Household;
use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Finance\Enums\Direction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $household_id
 * @property string $name
 * @property Direction $direction
 * @property string $category_id
 * @property string $account_id
 * @property int $amount
 * @property bool $is_variable_amount
 * @property string $rrule
 * @property CarbonImmutable $dtstart
 * @property CarbonImmutable|null $until
 * @property int $remind_days_before
 * @property bool $is_active
 * @property CarbonImmutable|null $generated_until
 * @property string|null $notes
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Household $household
 * @property-read Category $category
 * @property-read Account $account
 * @property-read User|null $creator
 */
class RecurringBill extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'fin_recurring_bills';

    protected $fillable = [
        'household_id',
        'name',
        'direction',
        'category_id',
        'account_id',
        'amount',
        'is_variable_amount',
        'rrule',
        'dtstart',
        'until',
        'remind_days_before',
        'is_active',
        'generated_until',
        'notes',
        'created_by',
    ];

    protected $attributes = [
        'is_variable_amount' => false,
        'remind_days_before' => 3,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'direction' => Direction::class,
            'amount' => 'integer',
            'is_variable_amount' => 'boolean',
            'dtstart' => 'immutable_date',
            'until' => 'immutable_date',
            'remind_days_before' => 'integer',
            'is_active' => 'boolean',
            'generated_until' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<PlannedItem, $this> */
    public function plannedItems(): HasMany
    {
        return $this->hasMany(PlannedItem::class, 'source_id')->where('source_type', 'recurring_bill');
    }
}
