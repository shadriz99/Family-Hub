<?php

declare(strict_types=1);

namespace App\Modules\Asset\Models;

use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Asset\Enums\IssuePriority;
use App\Modules\Asset\Enums\IssueStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $household_id
 * @property string $title
 * @property string|null $description
 * @property string|null $asset_id
 * @property string|null $location_id
 * @property IssuePriority $priority
 * @property IssueStatus $status
 * @property CarbonImmutable|null $scheduled_on
 * @property string|null $vendor_id
 * @property CarbonImmutable|null $resolved_on
 * @property string|null $resolution_note
 * @property string $reported_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read Asset|null $asset
 * @property-read Location|null $location
 * @property-read Vendor|null $vendor
 * @property-read User|null $reporter
 */
final class Issue extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'ast_issues';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'title',
        'description',
        'asset_id',
        'location_id',
        'priority',
        'status',
        'scheduled_on',
        'vendor_id',
        'resolved_on',
        'resolution_note',
        'reported_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'priority' => IssuePriority::class,
            'status' => IssueStatus::class,
            'scheduled_on' => 'immutable_date',
            'resolved_on' => 'immutable_date',
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
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * @return HasMany<ServiceRecord, $this>
     */
    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class, 'issue_id');
    }
}
