<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Models\Household;
use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Finance\Enums\AccountType;
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
 * @property AccountType $type
 * @property int $opening_balance
 * @property CarbonImmutable $opening_date
 * @property int|null $credit_limit
 * @property string $color
 * @property string $icon
 * @property bool $include_in_total
 * @property int $sort_order
 * @property string|null $created_by
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Household $household
 * @property-read User|null $creator
 */
class Account extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'fin_accounts';

    protected $fillable = [
        'household_id',
        'name',
        'type',
        'opening_balance',
        'opening_date',
        'credit_limit',
        'color',
        'icon',
        'include_in_total',
        'sort_order',
        'created_by',
        'archived_at',
    ];

    protected $attributes = [
        'opening_balance' => 0,
        'color' => '#10b981',
        'icon' => 'wallet',
        'include_in_total' => true,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'opening_balance' => 'integer',
            'opening_date' => 'immutable_date',
            'credit_limit' => 'integer',
            'include_in_total' => 'boolean',
            'sort_order' => 'integer',
            'archived_at' => 'immutable_datetime',
        ];
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function archive(): void
    {
        $this->update(['archived_at' => now()]);
    }

    public function unarchive(): void
    {
        $this->update(['archived_at' => null]);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<Transaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_id');
    }

    /** @return HasMany<RecurringBill, $this> */
    public function recurringBills(): HasMany
    {
        return $this->hasMany(RecurringBill::class, 'account_id');
    }

    /** @return HasMany<Loan, $this> */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'account_id');
    }
}
