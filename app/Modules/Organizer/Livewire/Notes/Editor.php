<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Livewire\Notes;

use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Organizer\Enums\NoteType;
use App\Modules\Organizer\Models\Note;
use App\Modules\Organizer\Models\NoteItem;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Editor extends Component
{
    public ?string $noteId = null;
    public string $type = 'text'; // text, checklist
    public string $title = '';
    public string $body = '';
    public string $color = 'default';
    public string $labelsInput = '';
    public bool $is_pinned = false;
    public bool $is_private = false;

    /** @var array<int, array{id?: string, text: string, is_done: bool}> */
    public array $items = [];

    // Checklist fast input
    public string $newItemText = '';

    // Concurrency / Conflict handling
    public ?string $loadedUpdatedAt = null;
    public bool $showConflictDialog = false;
    public ?string $conflictEditorName = null;
    public ?string $conflictUpdatedAt = null;
    public ?string $serverBody = null;

    public bool $isSaved = true;

    public function mount(?string $id = null): void
    {
        if ($id !== null && $id !== 'create') {
            $note = Note::query()->with('items')->findOrFail($id);
            $this->noteId = $note->id;
            $this->type = $note->type->value;
            $this->title = $note->title ?? '';
            $this->body = $note->body ?? '';
            $this->color = $note->color;
            $this->labelsInput = ! empty($note->labels) ? implode(', ', $note->labels) : '';
            $this->is_pinned = $note->is_pinned;
            $this->is_private = $note->is_private;
            $this->loadedUpdatedAt = $note->updated_at?->toISOString();

            foreach ($note->items as $item) {
                $this->items[] = [
                    'id' => $item->id,
                    'text' => $item->text,
                    'is_done' => $item->is_done,
                ];
            }
        }
    }

    public function updated(string $propertyName): void
    {
        $this->isSaved = false;
    }

    public function addChecklistItem(): void
    {
        if (trim($this->newItemText) === '') {
            return;
        }

        $this->items[] = [
            'text' => trim($this->newItemText),
            'is_done' => false,
        ];
        $this->newItemText = '';
        $this->isSaved = false;
    }

    public function removeChecklistItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->isSaved = false;
    }

    public function clearCompletedItems(): void
    {
        $this->items = array_values(array_filter($this->items, fn ($item) => empty($item['is_done'])));
        $this->isSaved = false;
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:20000'],
            'color' => ['required', 'in:default,yellow,green,blue,pink,purple'],
        ]);

        /** @var User $user */
        $user = auth()->user();
        $household = app(HouseholdContext::class)->current();

        // Process labels (max 5)
        $labels = array_filter(array_map('trim', explode(',', $this->labelsInput)));
        $labels = array_slice(array_unique($labels), 0, 5);

        if ($this->noteId !== null) {
            /** @var Note $note */
            $note = Note::query()->findOrFail($this->noteId);

            // Check edit conflict: if updated_at changed on server since loaded
            if ($this->loadedUpdatedAt !== null && $note->updated_at) {
                $serverIso = $note->updated_at->toISOString();
                if ($serverIso !== $this->loadedUpdatedAt && $note->last_edited_by !== $user->id) {
                    $this->conflictEditorName = $note->lastEditor !== null ? $note->lastEditor->name : 'Pasangan Anda';
                    $this->conflictUpdatedAt = $note->updated_at->diffForHumans();
                    $this->serverBody = $note->body;
                    $this->showConflictDialog = true;

                    return;
                }
            }

            $note->type = NoteType::from($this->type);
            $note->title = $this->title !== '' ? $this->title : null;
            $note->body = $this->body !== '' ? $this->body : null;
            $note->color = $this->color;
            $note->labels = ! empty($labels) ? $labels : null;
            $note->is_pinned = $this->is_pinned;
            $note->is_private = $this->is_private;
            $note->last_edited_by = $user->id;
            $note->save();

            $this->syncChecklistItems($note);
            $this->loadedUpdatedAt = $note->fresh()->updated_at?->toISOString();
        } else {
            $note = new Note();
            $note->household_id = $household->id;
            $note->created_by = $user->id;
            $note->last_edited_by = $user->id;
            $note->type = NoteType::from($this->type);
            $note->title = $this->title !== '' ? $this->title : null;
            $note->body = $this->body !== '' ? $this->body : null;
            $note->color = $this->color;
            $note->labels = ! empty($labels) ? $labels : null;
            $note->is_pinned = $this->is_pinned;
            $note->is_private = $this->is_private;
            $note->save();

            $this->noteId = $note->id;
            $this->syncChecklistItems($note);
            $this->loadedUpdatedAt = $note->fresh()->updated_at?->toISOString();
        }

        $this->isSaved = true;
    }

    public function overwriteConflict(): void
    {
        $this->showConflictDialog = false;
        $this->loadedUpdatedAt = null; // force bypass
        $this->save();
    }

    public function saveAsCopy(): void
    {
        $this->showConflictDialog = false;
        $this->noteId = null;
        $this->title = $this->title . ' (Salinan)';
        $this->save();
        $this->redirect("/organizer/notes/{$this->noteId}", navigate: true);
    }

    private function syncChecklistItems(Note $note): void
    {
        if ($this->type !== 'checklist') {
            return;
        }

        $existingIds = [];
        foreach ($this->items as $idx => $itemData) {
            $text = trim($itemData['text']);
            if ($text === '') {
                continue;
            }

            if (! empty($itemData['id'])) {
                $item = NoteItem::query()->find($itemData['id']);
                if ($item !== null) {
                    $item->text = $text;
                    $item->is_done = (bool) $itemData['is_done'];
                    $item->sort_order = $idx + 1;
                    $item->save();
                    $existingIds[] = $item->id;
                }
            } else {
                $newItem = new NoteItem();
                $newItem->note_id = $note->id;
                $newItem->text = $text;
                $newItem->is_done = (bool) $itemData['is_done'];
                $newItem->sort_order = $idx + 1;
                $newItem->save();
                $existingIds[] = $newItem->id;
            }
        }

        NoteItem::query()->where('note_id', $note->id)->whereNotIn('id', $existingIds)->delete();
    }

    public function render(): View
    {
        return view('modules.organizer.notes.editor')
            ->layout('layouts.app', ['title' => $this->title ?: 'Editor Catatan']);
    }
}
