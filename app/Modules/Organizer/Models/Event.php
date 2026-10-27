<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Models;

use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Organizer\Enums\EventKind;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $household_id
 * @property string $created_by
 * @property EventKind $kind
 * @property string $title
 * @property string|null $description
 * @property string|null $location
 * @property bool $all_day
 * @property CarbonImmutable|null $start_at
 * @property CarbonImmutable|null $end_at
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable|null $end_date
 * @property string|null $rrule
 * @property string|null $for_user_id
 * @property string|null $color
 * @property array<int>|null $reminder_offsets
 * @property int|null $birth_year
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
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
            'start_at' => 'immutable_datetime',
            'end_at' => 'immutable_datetime',
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
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
