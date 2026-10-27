<?php

declare(strict_types=1);

namespace App\Modules\Asset\Models;

use App\Core\Support\BelongsToHousehold;
use App\Models\User;
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
 * @property array<string>|null $services
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property string|null $address
 * @property string|null $notes
 * @property int|null $rating
 * @property bool $is_favorite
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
final class Vendor extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'ast_vendors';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'name',
        'services',
        'phone',
        'whatsapp',
        'address',
        'notes',
        'rating',
        'is_favorite',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'services' => 'array',
            'rating' => 'integer',
            'is_favorite' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'deleted_at' => 'immutable_datetime',
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
     * @return HasMany<ServiceRecord, $this>
     */
    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class, 'vendor_id');
    }

    /**
     * @return HasMany<MaintenancePlan, $this>
     */
    public function maintenancePlans(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class, 'vendor_id');
    }

    /**
     * @return HasMany<Issue, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class, 'vendor_id');
    }
}
