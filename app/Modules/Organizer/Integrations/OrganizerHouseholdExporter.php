<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Integrations;

use App\Core\Contracts\HouseholdExporter;
use App\Core\Models\Household;
use App\Modules\Organizer\Models\Event;
use App\Modules\Organizer\Models\Note;
use App\Modules\Organizer\Models\NoteItem;
use App\Modules\Organizer\Models\ShoppingItem;
use App\Modules\Organizer\Models\ShoppingList;
use App\Modules\Organizer\Models\Task;
use App\Modules\Organizer\Models\TaskItem;

final class OrganizerHouseholdExporter implements HouseholdExporter
{
    public function module(): string
    {
        return 'organizer';
    }

    /**
     * @return array<string, mixed>
     */
    public function export(Household $household): array
    {
        $tasks = Task::query()
            ->with('items')
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (Task $t): array => [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'due_date' => $t->due_date?->toDateString(),
                'due_time' => $t->due_time,
                'priority' => $t->priority->value,
                'status' => $t->status->value,
                'completed_at' => $t->completed_at?->toISOString(),
                'items' => $t->items->map(fn (TaskItem $i): array => [
                    'text' => $i->text,
                    'is_done' => $i->is_done,
                ])->all(),
            ]);

        // BR-ORG-010: Exporter includes non-private notes + private notes of the exporter if owner
        $notes = Note::withoutGlobalScope('privacy')
            ->with('items')
            ->where('household_id', $household->id)
            ->where(function ($q): void {
                $q->where('is_private', false);
                if (auth()->check()) {
                    $q->orWhere('created_by', auth()->id());
                }
            })
            ->get()
            ->map(fn (Note $n): array => [
                'id' => $n->id,
                'type' => $n->type->value,
                'title' => $n->title,
                'body' => $n->body,
                'color' => $n->color,
                'labels' => $n->labels,
                'is_pinned' => $n->is_pinned,
                'is_private' => $n->is_private,
                'items' => $n->items->map(fn (NoteItem $i): array => [
                    'text' => $i->text,
                    'is_done' => $i->is_done,
                ])->all(),
            ]);

        $shoppingLists = ShoppingList::query()
            ->with('items')
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (ShoppingList $l): array => [
                'id' => $l->id,
                'name' => $l->name,
                'store' => $l->store,
                'status' => $l->status->value,
                'actual_total' => $l->actual_total,
                'items' => $l->items->map(fn (ShoppingItem $i): array => [
                    'name' => $i->name,
                    'quantity' => $i->quantity,
                    'unit' => $i->unit,
                    'estimated_price' => $i->estimated_price,
                    'group' => $i->group->value,
                    'is_checked' => $i->is_checked,
                ])->all(),
            ]);

        $events = Event::query()
            ->with('exceptions')
            ->where('household_id', $household->id)
            ->get()
            ->map(fn (Event $e): array => [
                'id' => $e->id,
                'kind' => $e->kind->value,
                'title' => $e->title,
                'location' => $e->location,
                'all_day' => $e->all_day,
                'start_date' => $e->start_date->toDateString(),
                'end_date' => $e->end_date?->toDateString(),
                'rrule' => $e->rrule,
            ]);

        return [
            'organizer/tasks.json' => $tasks->all(),
            'organizer/notes.json' => $notes->all(),
            'organizer/shopping_lists.json' => $shoppingLists->all(),
            'organizer/events.json' => $events->all(),
        ];
    }
}
