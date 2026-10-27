<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Models\Household;
use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\TransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $household_id
 * @property TransactionType $type
 * @property string $account_id
 * @property string|null $to_account_id
 * @property string|null $category_id
 * @property int $amount
 * @property CarbonImmutable $occurred_on
 * @property string|null $note
 * @property string|null $payee
 * @property string|null $planned_item_id
 * @property string|null $related_transaction_id
 * @property string|null $source_type
 * @property string|null $source_id
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property AllocationCategory|null $allocation
 * @property string|null $allocation_detail
 * @property string|null $allocation_reversal_of_id
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Household $household
 * @property-read Account $account
 * @property-read Account|null $toAccount
 * @property-read Category|null $category
 * @property-read PlannedItem|null $plannedItem
 * @property-read Transaction|null $relatedTransaction
 * @property-read Transaction|null $allocationReversalOf
 * @property-read User|null $creator
 * @property-read User|null $updater
 */
class Transaction extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'fin_transactions';

    protected $fillable = [
        'household_id',
        'type',
        'account_id',
        'to_account_id',
        'category_id',
        'amount',
        'occurred_on',
        'note',
        'payee',
        'planned_item_id',
        'related_transaction_id',
        'source_type',
        'source_id',
        'created_by',
        'updated_by',
        'allocation',
        'allocation_detail',
        'allocation_reversal_of_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'integer',
            'occurred_on' => 'immutable_date',
            'allocation' => AllocationCategory::class,
        ];
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /** @return BelongsTo<Account, $this> */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** @return BelongsTo<PlannedItem, $this> */
    public function plannedItem(): BelongsTo
    {
        return $this->belongsTo(PlannedItem::class, 'planned_item_id');
    }

    /** @return BelongsTo<Transaction, $this> */
    public function relatedTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'related_transaction_id');
    }

    /** @return BelongsTo<Transaction, $this> */
    public function allocationReversalOf(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'allocation_reversal_of_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return MorphTo<Model, $this> */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
