<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Models\Household;
use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Enums\PlannedSource;
use App\Modules\Finance\Enums\PlannedStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $household_id
 * @property Direction $direction
 * @property string $name
 * @property string $category_id
 * @property string|null $account_id
 * @property int $amount
 * @property int $paid_amount
 * @property CarbonImmutable $due_date
 * @property string $period
 * @property PlannedStatus $status
 * @property string|null $status_reason
 * @property PlannedSource $source_type
 * @property string|null $source_id
 * @property int|null $sequence
 * @property bool $paid_before_tracking
 * @property CarbonImmutable|null $settled_at
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Household $household
 * @property-read Category $category
 * @property-read Account|null $account
 * @property-read User|null $creator
 */
class PlannedItem extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'fin_planned_items';

    protected $fillable = [
        'household_id',
        'direction',
        'name',
        'category_id',
        'account_id',
        'amount',
        'paid_amount',
        'due_date',
        'period',
        'status',
        'status_reason',
        'source_type',
        'source_id',
        'sequence',
        'paid_before_tracking',
        'settled_at',
        'created_by',
    ];

    protected $attributes = [
        'paid_amount' => 0,
        'status' => 'planned',
        'source_type' => 'manual',
        'paid_before_tracking' => false,
    ];

    protected function casts(): array
    {
        return [
            'direction' => Direction::class,
            'amount' => 'integer',
            'paid_amount' => 'integer',
            'due_date' => 'immutable_date',
            'status' => PlannedStatus::class,
            'source_type' => PlannedSource::class,
            'sequence' => 'integer',
            'paid_before_tracking' => 'boolean',
            'settled_at' => 'immutable_datetime',
        ];
    }

    public function remainingAmount(): int
    {
        return max(0, $this->amount - $this->paid_amount);
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, [PlannedStatus::Planned, PlannedStatus::Partial], true)
            && $this->due_date->isPast()
            && ! $this->due_date->isToday();
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

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'planned_item_id');
    }
}
