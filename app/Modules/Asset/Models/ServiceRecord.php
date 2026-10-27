<?php

declare(strict_types=1);

namespace App\Modules\Asset\Models;

use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Asset\Enums\ServiceType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $household_id
 * @property string $asset_id
 * @property string|null $maintenance_plan_id
 * @property string|null $issue_id
 * @property ServiceType $type
 * @property CarbonImmutable $performed_on
 * @property int|null $meter_reading
 * @property string|null $vendor_id
 * @property int $cost
 * @property string|null $description
 * @property string|null $parts
 * @property string|null $transaction_id
 * @property string|null $service_warranty_document_id
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Asset|null $asset
 * @property-read MaintenancePlan|null $maintenancePlan
 * @property-read Issue|null $issue
 * @property-read Vendor|null $vendor
 * @property-read AssetDocument|null $warrantyDocument
 */
final class ServiceRecord extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'ast_service_records';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'asset_id',
        'maintenance_plan_id',
        'issue_id',
        'type',
        'performed_on',
        'meter_reading',
        'vendor_id',
        'cost',
        'description',
        'parts',
        'transaction_id',
        'service_warranty_document_id',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ServiceType::class,
            'performed_on' => 'immutable_date',
            'cost' => 'integer',
            'meter_reading' => 'integer',
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
     * @return BelongsTo<MaintenancePlan, $this>
     */
    public function maintenancePlan(): BelongsTo
    {
        return $this->belongsTo(MaintenancePlan::class, 'maintenance_plan_id');
    }

    /**
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * @return BelongsTo<AssetDocument, $this>
     */
    public function warrantyDocument(): BelongsTo
    {
        return $this->belongsTo(AssetDocument::class, 'service_warranty_document_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
