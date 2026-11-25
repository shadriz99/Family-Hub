<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Core\Models\Household;
use App\Core\Support\BelongsToHousehold;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $household_id
 * @property string $transaction_id
 * @property string $category_id
 * @property int $amount
 * @property string|null $notes
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read Household $household
 * @property-read Transaction $transaction
 * @property-read Category $category
 */
class TransactionSplit extends Model
{
    use BelongsToHousehold;
    use HasUlids;

    protected $table = 'fin_transaction_splits';

    protected $fillable = [
        'household_id',
        'transaction_id',
        'category_id',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    /** @return BelongsTo<Household, $this> */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /** @return BelongsTo<Transaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
