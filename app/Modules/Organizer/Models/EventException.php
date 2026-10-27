<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $event_id
 * @property CarbonImmutable $original_date
 * @property bool $is_cancelled
 * @property array<string, mixed>|null $override
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
final class EventException extends Model
{
    use HasUlids;

    protected $table = 'org_event_exceptions';

    protected $fillable = [
        'event_id',
        'original_date',
        'is_cancelled',
        'override',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'original_date' => 'immutable_date',
            'is_cancelled' => 'boolean',
            'override' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
