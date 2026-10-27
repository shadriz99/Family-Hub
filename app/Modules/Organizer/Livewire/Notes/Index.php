<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Livewire\Notes;

use App\Core\Support\HouseholdContext;
use App\Modules\Organizer\Models\Note;
use App\Modules\Organizer\Models\NoteItem;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;

final class Index extends Component
{
    #[Url]
    public string $view = 'active'; // active, archived, trash

    #[Url]
    public string $search = '';

    #[Url]
    public string $selectedLabel = '';

    public function togglePin(string $noteId): void
    {
        $note = Note::query()->findOrFail($noteId);
        $note->is_pinned = ! $note->is_pinned;
        $note->save();
    }

    public function archive(string $noteId): void
    {
        $note = Note::query()->findOrFail($noteId);
        $note->archived_at = CarbonImmutable::now();
        $note->is_pinned = false;
        $note->save();
    }

    public function unarchive(string $noteId): void
    {
        $note = Note::query()->findOrFail($noteId);
        $note->archived_at = null;
        $note->save();
    }

    public function delete(string $noteId): void
    {
        $note = Note::query()->findOrFail($noteId);
        $note->delete(); // Soft delete
    }

    public function restore(string $noteId): void
    {
        $note = Note::onlyTrashed()->findOrFail($noteId);
        $note->restore();
    }

    public function forceDeleteNote(string $noteId): void
    {
        $note = Note::onlyTrashed()->findOrFail($noteId);
        $note->forceDelete();
    }

    public function toggleItem(string $itemId): void
    {
        $item = NoteItem::query()->findOrFail($itemId);
        $item->is_done = ! $item->is_done;
        $item->save();
    }

    public function render(): View
    {
        $household = app(HouseholdContext::class)->current();

        $query = Note::query()
            ->with(['items', 'creator', 'lastEditor'])
            ->where('household_id', $household->id);

        if ($this->view === 'trash') {
            $query->onlyTrashed();
        } elseif ($this->view === 'archived') {
            $query->whereNotNull('archived_at');
        } else {
            // active
            $query->whereNull('archived_at');
        }

        if (trim($this->search) !== '') {
            $s = '%' . trim($this->search) . '%';
            $query->where(function (Builder $q) use ($s): void {
                $q->where('title', 'like', $s)
                    ->orWhere('body', 'like', $s)
                    ->orWhereHas('items', function (Builder $iq) use ($s): void {
                        $iq->where('text', 'like', $s);
                    });
            });
        }

        if (trim($this->selectedLabel) !== '') {
            $query->whereJsonContains('labels', $this->selectedLabel);
        }

        // Pinned notes first, then latest updated
        $notes = $query->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->get();

        // Extract all unique labels across household notes
        $allLabels = Note::query()
            ->where('household_id', $household->id)
            ->whereNotNull('labels')
            ->pluck('labels')
            ->flatten()
            ->unique()
            ->values()
            ->all();

        return view('modules.organizer.notes.index', [
            'notes' => $notes,
            'allLabels' => $allLabels,
        ])->layout('layouts.app', ['title' => 'Catatan Keluarga']);
    }
}
