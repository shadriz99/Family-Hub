<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Models;

use App\Models\User;
use App\Modules\Organizer\Enums\ShoppingGroup;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShoppingItem extends Model
{
    use HasUlids;

    protected $table = 'org_shopping_items';

    protected $fillable = [
        'shopping_list_id',
        'name',
        'quantity',
        'unit',
        'estimated_price',
        'group',
        'note',
        'is_checked',
        'checked_by',
        'checked_at',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'estimated_price' => 'integer',
            'group' => ShoppingGroup::class,
            'is_checked' => 'boolean',
            'checked_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ShoppingList, $this>
     */
    public function shoppingList(): BelongsTo
    {
        return $this->belongsTo(ShoppingList::class, 'shopping_list_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function checkedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
