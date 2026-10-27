<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Livewire\Shopping;

use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Organizer\Enums\ShoppingListStatus;
use App\Modules\Organizer\Models\ShoppingList;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

final class Index extends Component
{
    #[Url]
    public string $view = 'active'; // active, history

    public string $newListName = '';
    public string $newListStore = '';

    public function createList(): void
    {
        $this->validate([
            'newListName' => ['required', 'string', 'max:80'],
            'newListStore' => ['nullable', 'string', 'max:80'],
        ]);

        /** @var User $user */
        $user = auth()->user();
        $household = app(HouseholdContext::class)->current();

        $list = ShoppingList::create([
            'household_id' => $household->id,
            'created_by' => $user->id,
            'name' => trim($this->newListName),
            'store' => trim($this->newListStore) !== '' ? trim($this->newListStore) : null,
            'status' => ShoppingListStatus::Active,
        ]);

        $this->newListName = '';
        $this->newListStore = '';
        $this->redirect("/organizer/shopping/{$list->id}", navigate: true);
    }

    public function deleteList(string $listId): void
    {
        $list = ShoppingList::query()->findOrFail($listId);
        $list->delete(); // Soft delete per BR-ORG-021
    }

    public function render(): View
    {
        $household = app(HouseholdContext::class)->current();

        $query = ShoppingList::query()
            ->with(['creator', 'completedByUser'])
            ->withCount([
                'items as total_items',
                'items as checked_items' => function ($q): void {
                    $q->where('is_checked', true);
                },
            ])
            ->where('household_id', $household->id);

        if ($this->view === 'history') {
            $query->whereIn('status', [ShoppingListStatus::Completed, ShoppingListStatus::Archived])
                ->orderByDesc('completed_at')
                ->orderByDesc('updated_at');
        } else {
            $query->where('status', ShoppingListStatus::Active)
                ->orderByDesc('created_at');
        }

        $lists = $query->get();

        return view('modules.organizer.shopping.index', [
            'lists' => $lists,
        ])->layout('layouts.app', ['title' => 'Daftar Belanja']);
    }
}
