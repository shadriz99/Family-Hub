<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Models;

use App\Models\User;
use App\Modules\Organizer\Enums\ShoppingGroup;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $shopping_list_id
 * @property string $name
 * @property float $quantity
 * @property string|null $unit
 * @property int|null $estimated_price
 * @property ShoppingGroup $group
 * @property string|null $note
 * @property bool $is_checked
 * @property string|null $checked_by
 * @property CarbonImmutable|null $checked_at
 * @property int $sort_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
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
            'quantity' => 'float',
            'estimated_price' => 'integer',
            'group' => ShoppingGroup::class,
            'is_checked' => 'boolean',
            'checked_at' => 'immutable_datetime',
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
