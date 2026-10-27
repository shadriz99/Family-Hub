<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            'original_date' => 'date',
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
