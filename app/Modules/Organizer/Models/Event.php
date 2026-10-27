<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Models;

use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Organizer\Enums\EventKind;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Event extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'org_events';

    protected $fillable = [
        'household_id',
        'created_by',
        'kind',
        'title',
        'description',
        'location',
        'all_day',
        'start_at',
        'end_at',
        'start_date',
        'end_date',
        'rrule',
        'for_user_id',
        'color',
        'reminder_offsets',
        'birth_year',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => EventKind::class,
            'all_day' => 'boolean',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'start_date' => 'date',
            'end_date' => 'date',
            'reminder_offsets' => 'array',
            'birth_year' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function forUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'for_user_id');
    }

    /**
     * @return HasMany<EventException, $this>
     */
    public function exceptions(): HasMany
    {
        return $this->hasMany(EventException::class, 'event_id');
    }
}
