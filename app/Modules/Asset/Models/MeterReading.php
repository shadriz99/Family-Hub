<?php

declare(strict_types=1);

namespace App\Modules\Asset\Models;

use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $household_id
 * @property string $asset_id
 * @property int $raw_reading
 * @property int $cumulative_offset
 * @property int $reading
 * @property CarbonImmutable $read_on
 * @property bool $is_reset
 * @property string $source
 * @property string|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Asset|null $asset
 */
final class MeterReading extends Model
{
    use BelongsToHousehold;
    use HasUlids;

    protected $table = 'ast_meter_readings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'household_id',
        'asset_id',
        'raw_reading',
        'cumulative_offset',
        'reading',
        'read_on',
        'is_reset',
        'source',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_reading' => 'integer',
            'cumulative_offset' => 'integer',
            'reading' => 'integer',
            'read_on' => 'immutable_date',
            'is_reset' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
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
}
