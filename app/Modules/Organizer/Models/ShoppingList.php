<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Models;

use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Organizer\Enums\ShoppingListStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ShoppingList extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'org_shopping_lists';

    protected $fillable = [
        'household_id',
        'created_by',
        'name',
        'store',
        'status',
        'completed_at',
        'completed_by',
        'actual_total',
        'transaction_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShoppingListStatus::class,
            'completed_at' => 'datetime',
            'actual_total' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * @return HasMany<ShoppingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShoppingItem::class, 'shopping_list_id')->orderBy('sort_order');
    }

    public function estimatedTotal(): int
    {
        return (int) $this->items()
            ->whereNotNull('estimated_price')
            ->get()
            ->sum(function (ShoppingItem $item): float {
                return ((float) $item->quantity) * ((int) $item->estimated_price);
            });
    }
}
