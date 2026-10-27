<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire\Recurring;

use App\Core\Support\HouseholdContext;
use App\Core\Support\Money;
use App\Modules\Finance\Actions\GeneratePlannedItemsFromRecurringBills;
use App\Modules\Finance\Enums\Direction;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\RecurringBill;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class Index extends Component
{
    public bool $showFormModal = false;

    public ?string $editingBillId = null;
    public string $name = '';
    public string $direction = 'expense';
    public ?string $category_id = null;
    public ?string $account_id = null;
    public string $amount = '';
    public bool $is_variable_amount = false;
    public string $preset = 'monthly_day';
    public int $monthly_day = 1;
    public string $dtstart = '';
    public ?string $until = null;
    public int $remind_days_before = 3;

    public function mount(): void
    {
        $this->dtstart = now()->toDateString();
        $this->monthly_day = (int) now()->day;
    }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->editingBillId = null;
        $this->name = '';
        $this->direction = 'expense';
        $this->category_id = null;
        $this->account_id = null;
        $this->amount = '';
        $this->is_variable_amount = false;
        $this->preset = 'monthly_day';
        $this->monthly_day = (int) now()->day;
        $this->dtstart = now()->toDateString();
        $this->until = null;
        $this->remind_days_before = 3;
        $this->showFormModal = true;
    }

    public function openEdit(string $id, HouseholdContext $context): void
    {
        $household = $context->current();
        $bill = RecurringBill::query()->where('household_id', $household->id)->findOrFail($id);

        $this->resetValidation();
        $this->editingBillId = $bill->id;
        $this->name = $bill->name;
        $this->direction = $bill->direction->value;
        $this->category_id = $bill->category_id;
        $this->account_id = $bill->account_id;
        $this->amount = (string) $bill->amount;
        $this->is_variable_amount = $bill->is_variable_amount;
        $this->dtstart = $bill->dtstart->toDateString();
        $this->until = $bill->until?->toDateString();
        $this->remind_days_before = $bill->remind_days_before;

        if (preg_match('/BYMONTHDAY=(\d+)/', $bill->rrule, $m)) {
            $this->preset = 'monthly_day';
            $this->monthly_day = (int) $m[1];
        } else {
            $this->preset = 'monthly_day';
            $this->monthly_day = (int) $bill->dtstart->day;
        }

        $this->showFormModal = true;
    }

    public function saveBill(HouseholdContext $context, GeneratePlannedItemsFromRecurringBills $generator): void
    {
        $household = $context->current();

        $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'direction' => ['required', Rule::enum(Direction::class)],
            'category_id' => ['required', 'exists:fin_categories,id'],
            'account_id' => ['required', 'exists:fin_accounts,id'],
            'amount' => ['required'],
            'dtstart' => ['required', 'date'],
            'remind_days_before' => ['required', 'integer', 'min:0', 'max:30'],
        ]);

        $parsedAmount = Money::parse($this->amount);
        $dtstartDate = CarbonImmutable::parse($this->dtstart);
        $untilDate = $this->until !== null && trim($this->until) !== '' ? CarbonImmutable::parse($this->until) : null;

        $day = min(28, max(1, $this->monthly_day));
        $rrule = "FREQ=MONTHLY;BYMONTHDAY={$day}";

        if ($this->editingBillId !== null) {
            $bill = RecurringBill::query()->where('household_id', $household->id)->findOrFail($this->editingBillId);
            $bill->name = $this->name;
            $bill->direction = Direction::from($this->direction);
            $bill->category_id = $this->category_id;
            $bill->account_id = $this->account_id;
            $bill->amount = $parsedAmount;
            $bill->is_variable_amount = $this->is_variable_amount;
            $bill->rrule = $rrule;
            $bill->dtstart = $dtstartDate;
            $bill->until = $untilDate;
            $bill->remind_days_before = $this->remind_days_before;
            $bill->save();

            session()->flash('message', "Tagihan rutin '{$bill->name}' berhasil diperbarui.");
        } else {
            $bill = new RecurringBill();
            $bill->household_id = $household->id;
            $bill->name = $this->name;
            $bill->direction = Direction::from($this->direction);
            $bill->category_id = $this->category_id;
            $bill->account_id = $this->account_id;
            $bill->amount = $parsedAmount;
            $bill->is_variable_amount = $this->is_variable_amount;
            $bill->rrule = $rrule;
            $bill->dtstart = $dtstartDate;
            $bill->until = $untilDate;
            $bill->remind_days_before = $this->remind_days_before;
            $bill->created_by = auth()->id();
            $bill->save();

            session()->flash('message', "Tagihan rutin '{$bill->name}' berhasil dibuat.");
        }

        // Generate planned items for this bill up to horizon immediately
        $generator->execute($household, $bill);

        $this->showFormModal = false;
    }

    public function toggleActive(string $id, HouseholdContext $context, GeneratePlannedItemsFromRecurringBills $generator): void
    {
        $household = $context->current();
        $bill = RecurringBill::query()->where('household_id', $household->id)->findOrFail($id);

        $bill->is_active = ! $bill->is_active;
        $bill->save();

        $generator->execute($household, $bill);

        $statusStr = $bill->is_active ? 'diaktifkan' : 'dinonaktifkan';
        session()->flash('message', "Tagihan '{$bill->name}' berhasil {$statusStr}.");
    }

    public function render(HouseholdContext $context): View
    {
        $household = $context->current();

        $bills = RecurringBill::query()
            ->where('household_id', $household->id)
            ->with(['category', 'account'])
            ->orderBy('name')
            ->get();

        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get();

        $categories = Category::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get();

        return view('finance::recurring.index', [
            'bills' => $bills,
            'accounts' => $accounts,
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => 'Tagihan & Pemasukan Rutin']);
    }
}
