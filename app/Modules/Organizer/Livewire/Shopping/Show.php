<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Livewire\Shopping;

use App\Core\Support\HouseholdContext;
use App\Models\User;
use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Finance\DTO\ExpenseData;
use App\Modules\Organizer\Enums\ShoppingListStatus;
use App\Modules\Organizer\Models\ShoppingItem;
use App\Modules\Organizer\Models\ShoppingList;
use App\Modules\Organizer\Services\ShoppingItemParser;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Show extends Component
{
    public string $listId;

    // Quick add
    public string $quickInput = '';
    public ?int $quickEstimatedPrice = null;

    // Complete shopping modal state
    public bool $showCompleteModal = false;
    public int $actualTotal = 0;
    public ?string $expenseAccountId = null;
    public ?string $expenseCategoryId = null;
    public string $uncheckedAction = 'remove'; // remove, move_to_new

    public function mount(string $id): void
    {
        $this->listId = $id;

        // Pre-fill account & default shopping category from Finance Gateway
        $gateway = app(FinanceGateway::class);
        $accounts = $gateway->accountOptions();
        if (! empty($accounts)) {
            $this->expenseAccountId = array_key_first($accounts);
        }
        $this->expenseCategoryId = $gateway->defaultCategoryId('shopping');
    }

    public function addItem(): void
    {
        if (trim($this->quickInput) === '') {
            return;
        }

        $parsed = app(ShoppingItemParser::class)->parse($this->quickInput);

        $maxSort = (int) ShoppingItem::query()
            ->where('shopping_list_id', $this->listId)
            ->max('sort_order');

        ShoppingItem::create([
            'shopping_list_id' => $this->listId,
            'name' => $parsed['name'],
            'quantity' => $parsed['quantity'],
            'unit' => $parsed['unit'],
            'estimated_price' => $this->quickEstimatedPrice,
            'group' => $parsed['group'],
            'sort_order' => $maxSort + 1,
        ]);

        $this->quickInput = '';
        $this->quickEstimatedPrice = null;
    }

    public function toggleItem(string $itemId): void
    {
        /** @var User $user */
        $user = auth()->user();
        /** @var ShoppingItem $item */
        $item = ShoppingItem::query()->findOrFail($itemId);

        $item->is_checked = ! $item->is_checked;
        if ($item->is_checked) {
            $item->checked_by = $user->id;
            $item->checked_at = CarbonImmutable::now();
        } else {
            $item->checked_by = null;
            $item->checked_at = null;
        }
        $item->save();
    }

    public function deleteItem(string $itemId): void
    {
        $item = ShoppingItem::query()->findOrFail($itemId);
        $item->delete();
    }

    public function openCompleteModal(): void
    {
        $list = $this->getList();
        // Default actual total is the sum of estimated prices of checked items
        $checkedEstimated = (int) $list->items
            ->where('is_checked', true)
            ->whereNotNull('estimated_price')
            ->sum(fn ($i) => ((float) $i->quantity) * ((int) $i->estimated_price));

        $this->actualTotal = $checkedEstimated > 0 ? $checkedEstimated : 0;
        $this->showCompleteModal = true;
    }

    public function completeShopping(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $list = $this->getList();

        $transactionId = null;
        if (config('modules.finance', false) && $this->actualTotal > 0 && $this->expenseAccountId && $this->expenseCategoryId) {
            $gateway = app(FinanceGateway::class);
            $expenseData = new ExpenseData(
                accountId: $this->expenseAccountId,
                categoryId: $this->expenseCategoryId,
                amount: $this->actualTotal,
                occurredOn: CarbonImmutable::now(),
                note: "Belanja: {$list->name}" . ($list->store ? " di {$list->store}" : ''),
                sourceType: 'org_shopping_list',
                sourceId: $list->id,
            );
            $transactionId = $gateway->recordExpense($expenseData);
        }

        // Handle unchecked items
        $uncheckedItems = $list->items()->where('is_checked', false)->get();
        if ($this->uncheckedAction === 'move_to_new' && $uncheckedItems->isNotEmpty()) {
            $household = app(HouseholdContext::class)->current();
            $newList = ShoppingList::create([
                'household_id' => $household->id,
                'created_by' => $user->id,
                'name' => "Sisa Belanja: {$list->name}",
                'store' => $list->store,
                'status' => ShoppingListStatus::Active,
            ]);

            foreach ($uncheckedItems as $item) {
                $item->shopping_list_id = $newList->id;
                $item->save();
            }
        } elseif ($this->uncheckedAction === 'remove') {
            $list->items()->where('is_checked', false)->delete();
        }

        $list->status = ShoppingListStatus::Completed;
        $list->completed_at = CarbonImmutable::now();
        $list->completed_by = $user->id;
        $list->actual_total = $this->actualTotal;
        $list->transaction_id = $transactionId;
        $list->save();

        $this->showCompleteModal = false;
        $this->redirect('/organizer/shopping', navigate: true);
    }

    public function reopenList(): void
    {
        $list = $this->getList();
        $list->status = ShoppingListStatus::Active;
        $list->save();
    }

    public function shopAgain(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $household = app(HouseholdContext::class)->current();
        $oldList = $this->getList();

        $newList = ShoppingList::create([
            'household_id' => $household->id,
            'created_by' => $user->id,
            'name' => "Belanja: {$oldList->name}",
            'store' => $oldList->store,
            'status' => ShoppingListStatus::Active,
        ]);

        foreach ($oldList->items as $item) {
            ShoppingItem::create([
                'shopping_list_id' => $newList->id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'estimated_price' => $item->estimated_price,
                'group' => $item->group,
                'note' => $item->note,
                'is_checked' => false,
                'sort_order' => $item->sort_order,
            ]);
        }

        $this->redirect("/organizer/shopping/{$newList->id}", navigate: true);
    }

    private function getList(): ShoppingList
    {
        return ShoppingList::query()
            ->with(['items.checkedByUser', 'creator', 'completedByUser'])
            ->findOrFail($this->listId);
    }

    public function render(): View
    {
        $list = $this->getList();

        // Separate items into unchecked and checked (checked move to bottom)
        $unchecked = $list->items->where('is_checked', false)->sortBy('sort_order');
        $checked = $list->items->where('is_checked', true)->sortByDesc('checked_at');

        // Autocomplete: top 200 past item names
        $household = app(HouseholdContext::class)->current();
        $popularItems = ShoppingItem::query()
            ->whereHas('shoppingList', fn ($q) => $q->where('household_id', $household->id))
            ->select('name')
            ->distinct()
            ->limit(200)
            ->pluck('name');

        $gateway = app(FinanceGateway::class);
        $accounts = $gateway->accountOptions();
        $categories = $gateway->expenseCategoryOptions();

        return view('modules.organizer.shopping.show', [
            'list' => $list,
            'unchecked' => $unchecked,
            'checked' => $checked,
            'popularItems' => $popularItems,
            'accounts' => $accounts,
            'categories' => $categories,
            'estimatedTotal' => $list->estimatedTotal(),
        ])->layout('layouts.app', ['title' => $list->name]);
    }
}
