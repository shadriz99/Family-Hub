<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Models;

use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Organizer\Enums\NoteType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $household_id
 * @property string $created_by
 * @property NoteType $type
 * @property string|null $title
 * @property string|null $body
 * @property string $color
 * @property array<string>|null $labels
 * @property bool $is_pinned
 * @property bool $is_private
 * @property CarbonImmutable|null $archived_at
 * @property string|null $last_edited_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
final class Note extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'org_notes';

    protected $fillable = [
        'household_id',
        'created_by',
        'type',
        'title',
        'body',
        'color',
        'labels',
        'is_pinned',
        'is_private',
        'archived_at',
        'last_edited_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => NoteType::class,
            'labels' => 'array',
            'is_pinned' => 'boolean',
            'is_private' => 'boolean',
            'archived_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('privacy', function (Builder $query): void {
            $userId = auth()->id();

            $query->where(function (Builder $q) use ($userId): void {
                $q->where('is_private', false);

                if ($userId !== null) {
                    $q->orWhere('created_by', $userId);
                }
            });
        });
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
    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    /**
     * @return HasMany<NoteItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(NoteItem::class, 'note_id')->orderBy('sort_order');
    }
}
