<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Models;

use App\Core\Support\BelongsToHousehold;
use App\Models\User;
use App\Modules\Organizer\Enums\TaskPriority;
use App\Modules\Organizer\Enums\TaskReminderOffset;
use App\Modules\Organizer\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Task extends Model
{
    use BelongsToHousehold;
    use HasUlids;
    use SoftDeletes;

    protected $table = 'org_tasks';

    protected $fillable = [
        'household_id',
        'created_by',
        'series_id',
        'title',
        'description',
        'assignee_user_id',
        'due_date',
        'due_time',
        'priority',
        'rrule',
        'rotate_assignee',
        'reminder_offset',
        'status',
        'completed_at',
        'completed_by',
        'expense_on_complete',
        'expense_estimate',
        'expense_category_id',
        'expense_transaction_id',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'priority' => TaskPriority::class,
            'rotate_assignee' => 'boolean',
            'reminder_offset' => TaskReminderOffset::class,
            'status' => TaskStatus::class,
            'completed_at' => 'datetime',
            'expense_on_complete' => 'boolean',
            'expense_estimate' => 'integer',
            'sort_order' => 'integer',
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
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * @return HasMany<TaskItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TaskItem::class, 'task_id')->orderBy('sort_order');
    }
}
