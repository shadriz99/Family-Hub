<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Models\Household;
use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Finance\Enums\LoanDirection;
use App\Modules\Finance\Enums\LoanFrequency;
use App\Modules\Finance\Enums\LoanStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $household_id
 * @property LoanDirection $direction
 * @property string $name
 * @property string $counterparty
 * @property int $principal
 * @property int $installment_count
 * @property int $installment_amount
 * @property int $total_payable
 * @property LoanFrequency $frequency
 * @property CarbonImmutable $first_due_date
 * @property string $account_id
 * @property string $category_id
 * @property string|null $disbursement_transaction_id
 * @property string|null $interest_note
 * @property int $remind_days_before
 * @property LoanStatus $status
 * @property CarbonImmutable|null $paid_off_at
 * @property string|null $notes
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Household $household
 * @property-read Account $account
 * @property-read Category $category
 * @property-read User|null $creator
 */
class Loan extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'fin_loans';

    protected $fillable = [
        'household_id',
        'direction',
        'name',
        'counterparty',
        'principal',
        'installment_count',
        'installment_amount',
        'total_payable',
        'frequency',
        'first_due_date',
        'account_id',
        'category_id',
        'disbursement_transaction_id',
        'interest_note',
        'remind_days_before',
        'status',
        'paid_off_at',
        'notes',
        'created_by',
    ];

    protected $attributes = [
        'frequency' => 'monthly',
        'remind_days_before' => 3,
        'status' => 'active',
    ];

    protected function casts(): array
    {
        return [
            'direction' => LoanDirection::class,
            'principal' => 'integer',
            'installment_count' => 'integer',
            'installment_amount' => 'integer',
            'total_payable' => 'integer',
            'frequency' => LoanFrequency::class,
            'first_due_date' => 'immutable_date',
            'remind_days_before' => 'integer',
            'status' => LoanStatus::class,
            'paid_off_at' => 'immutable_date',
        ];
    }

    public function totalDifference(): int
    {
        return max(0, $this->total_payable - $this->principal);
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
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

    /** @return HasMany<PlannedItem, $this> */
    public function plannedItems(): HasMany
    {
        return $this->hasMany(PlannedItem::class, 'source_id')->where('source_type', 'loan')->orderBy('sequence');
    }
}
