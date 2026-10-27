<?php

declare(strict_types=1);

namespace App\Modules\Asset\Models;

use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Asset\Enums\AssetStatus;
use App\Modules\Asset\Enums\MeterUnit;
use App\Modules\Asset\Enums\PlanStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $household_id
 * @property string $category_id
 * @property string|null $location_id
 * @property string $name
 * @property string|null $brand
 * @property string|null $model
 * @property string|null $serial_number
 * @property string|null $plate_number
 * @property CarbonImmutable|null $purchase_date
 * @property int|null $purchase_price
 * @property string|null $purchased_from
 * @property string|null $purchase_transaction_id
 * @property AssetStatus $status
 * @property CarbonImmutable|null $disposed_on
 * @property MeterUnit|null $meter_unit
 * @property int|null $current_meter
 * @property CarbonImmutable|null $meter_updated_on
 * @property bool $is_favorite
 * @property string|null $notes
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read AssetCategory|null $category
 * @property-read Location|null $location
 */
final class Asset extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'ast_assets';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'category_id',
        'location_id',
        'name',
        'brand',
        'model',
        'serial_number',
        'plate_number',
        'purchase_date',
        'purchase_price',
        'purchased_from',
        'purchase_transaction_id',
        'status',
        'disposed_on',
        'meter_unit',
        'current_meter',
        'meter_updated_on',
        'is_favorite',
        'notes',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
            'meter_unit' => MeterUnit::class,
            'purchase_date' => 'immutable_date',
            'disposed_on' => 'immutable_date',
            'meter_updated_on' => 'immutable_date',
            'purchase_price' => 'integer',
            'current_meter' => 'integer',
            'is_favorite' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<AssetCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<AssetDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(AssetDocument::class, 'asset_id');
    }

    /**
     * @return HasMany<MaintenancePlan, $this>
     */
    public function maintenancePlans(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class, 'asset_id');
    }

    /**
     * @return HasMany<ServiceRecord, $this>
     */
    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class, 'asset_id');
    }

    /**
     * @return HasMany<MeterReading, $this>
     */
    public function meterReadings(): HasMany
    {
        return $this->hasMany(MeterReading::class, 'asset_id');
    }

    /**
     * @return HasMany<Issue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class, 'asset_id');
    }

    /**
     * Helper to get most urgent plan status.
     */
    public function urgentMaintenanceStatus(): ?PlanStatus
    {
        $activePlans = $this->maintenancePlans()->where('is_active', true)->get();
        if ($activePlans->isEmpty()) {
            return null;
        }

        $order = [
            PlanStatus::Overdue->value => 1,
            PlanStatus::Due->value => 2,
            PlanStatus::Soon->value => 3,
            PlanStatus::Ok->value => 4,
        ];

        $mostUrgent = PlanStatus::Ok;
        $minRank = 4;

        foreach ($activePlans as $plan) {
            $status = $plan->computedStatus();
            $rank = $order[$status->value];
            if ($rank < $minRank) {
                $minRank = $rank;
                $mostUrgent = $status;
            }
        }

        return $mostUrgent;
    }
}
