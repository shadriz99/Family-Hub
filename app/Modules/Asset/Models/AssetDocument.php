<?php

declare(strict_types=1);

namespace App\Modules\Asset\Models;

use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Asset\Enums\DocumentStatus;
use App\Modules\Asset\Enums\DocumentType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $household_id
 * @property string|null $asset_id
 * @property DocumentType $type
 * @property string $title
 * @property string|null $number
 * @property string|null $issuer
 * @property CarbonImmutable|null $issued_on
 * @property CarbonImmutable|null $expires_on
 * @property array<int>|null $remind_days
 * @property DocumentStatus $status
 * @property int|null $renewal_cost_estimate
 * @property bool $add_to_finance_plan
 * @property string|null $notes
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Asset|null $asset
 */
final class AssetDocument extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'ast_documents';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'asset_id',
        'type',
        'title',
        'number',
        'issuer',
        'issued_on',
        'expires_on',
        'remind_days',
        'status',
        'renewal_cost_estimate',
        'add_to_finance_plan',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'issued_on' => 'immutable_date',
            'expires_on' => 'immutable_date',
            'remind_days' => 'array',
            'renewal_cost_estimate' => 'integer',
            'add_to_finance_plan' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Derived status check (is it expired right now?).
     */
    public function isExpired(): bool
    {
        if ($this->status === DocumentStatus::Superseded) {
            return false;
        }

        if ($this->expires_on === null) {
            return false;
        }

        return $this->expires_on->isPast() && ! $this->expires_on->isToday();
    }
}
