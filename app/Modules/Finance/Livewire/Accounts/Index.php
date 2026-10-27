<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire\Accounts;

use App\Core\Support\HouseholdContext;
use App\Core\Support\Money;
use App\Modules\Finance\Actions\AdjustAccountBalance;
use App\Modules\Finance\Actions\ReconcileAccounts;
use App\Modules\Finance\Enums\AccountType;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Services\AccountBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class Index extends Component
{
    // Modal states
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showAdjustModal = false;
    public bool $showReconcileModal = false;

    // Create / Edit fields
    public ?string $editingAccountId = null;
    public string $name = '';
    public string $type = 'bank';
    public string $opening_balance = '0';
    public string $opening_date = '';
    public ?string $credit_limit = null;
    public string $color = '#10b981';
    public string $icon = 'wallet';
    public bool $include_in_total = true;

    // Adjust balance fields
    public ?string $adjustAccountId = null;
    public string $adjustTargetBalance = '0';
    public string $adjustDate = '';
    public string $adjustNote = '';

    // Reconcile fields
    public string $reconcileDate = '';
    /** @var array<string, string> */
    public array $reconcileBalances = [];
    /** @var array<string, array{account: Account, calculated: int, actual: int, diff: int}> */
    public array $reconcilePreview = [];
    public bool $reconcilePreviewReady = false;

    public function mount(): void
    {
        $this->opening_date = now()->toDateString();
        $this->adjustDate = now()->toDateString();
        $this->reconcileDate = now()->toDateString();
    }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->editingAccountId = null;
        $this->name = '';
        $this->type = 'bank';
        $this->opening_balance = '0';
        $this->opening_date = now()->toDateString();
        $this->credit_limit = null;
        $this->color = '#10b981';
        $this->icon = 'wallet';
        $this->include_in_total = true;
        $this->showCreateModal = true;
    }

    public function saveAccount(HouseholdContext $context): void
    {
        $household = $context->current();

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('fin_accounts', 'name')
                    ->where('household_id', $household->id)
                    ->ignore($this->editingAccountId),
            ],
            'type' => ['required', Rule::enum(AccountType::class)],
            'opening_balance' => ['required'],
            'opening_date' => ['required', 'date'],
            'color' => ['required', 'string', 'max:7'],
            'icon' => ['required', 'string', 'max:40'],
            'include_in_total' => ['boolean'],
        ]);

        $openingBal = Money::parse($this->opening_balance);
        $creditLimit = $this->credit_limit !== null && trim($this->credit_limit) !== ''
            ? Money::parse($this->credit_limit)
            : null;

        if ($this->editingAccountId !== null) {
            $account = Account::query()->where('household_id', $household->id)->findOrFail($this->editingAccountId);
            $account->name = $this->name;
            $account->color = $this->color;
            $account->icon = $this->icon;
            $account->include_in_total = $this->include_in_total;
            if ($this->type === AccountType::CreditCard->value) {
                $account->credit_limit = $creditLimit;
            }
            $account->save();
            session()->flash('message', "Akun '{$account->name}' berhasil diperbarui.");
        } else {
            $account = new Account();
            $account->household_id = $household->id;
            $account->name = $this->name;
            $account->type = AccountType::from($this->type);
            $account->opening_balance = $openingBal;
            $account->opening_date = CarbonImmutable::parse($this->opening_date);
            $account->credit_limit = $creditLimit;
            $account->color = $this->color;
            $account->icon = $this->icon;
            $account->include_in_total = $this->include_in_total;
            $account->created_by = auth()->id();
            $account->save();
            session()->flash('message', "Akun '{$account->name}' berhasil dibuat.");
        }

        $this->showCreateModal = false;
        $this->showEditModal = false;
    }

    public function openEdit(string $id, HouseholdContext $context): void
    {
        $household = $context->current();
        $account = Account::query()->where('household_id', $household->id)->findOrFail($id);

        $this->resetValidation();
        $this->editingAccountId = $account->id;
        $this->name = $account->name;
        $this->type = $account->type->value;
        $this->opening_balance = (string) $account->opening_balance;
        $this->opening_date = $account->opening_date->toDateString();
        $this->credit_limit = $account->credit_limit !== null ? (string) $account->credit_limit : null;
        $this->color = $account->color;
        $this->icon = $account->icon;
        $this->include_in_total = $account->include_in_total;
        $this->showEditModal = true;
    }

    public function toggleArchive(string $id, HouseholdContext $context): void
    {
        $household = $context->current();
        $account = Account::query()->where('household_id', $household->id)->findOrFail($id);

        if ($account->isArchived()) {
            $account->unarchive();
            session()->flash('message', "Akun '{$account->name}' diaktifkan kembali.");
        } else {
            $account->archive();
            session()->flash('message', "Akun '{$account->name}' berhasil diarsipkan.");
        }
    }

    public function openAdjust(string $id, HouseholdContext $context, AccountBalanceService $balanceService): void
    {
        $household = $context->current();
        $account = Account::query()->where('household_id', $household->id)->findOrFail($id);

        $this->adjustAccountId = $account->id;
        $this->adjustTargetBalance = (string) $balanceService->getBalance($account);
        $this->adjustDate = now()->toDateString();
        $this->adjustNote = '';
        $this->showAdjustModal = true;
    }

    public function saveAdjustment(AdjustAccountBalance $adjustAction, HouseholdContext $context): void
    {
        $household = $context->current();
        $account = Account::query()->where('household_id', $household->id)->findOrFail($this->adjustAccountId);

        $target = Money::parse($this->adjustTargetBalance);
        $date = CarbonImmutable::parse($this->adjustDate);

        $adjustAction->execute(
            $account,
            $target,
            $date,
            $this->adjustNote !== '' ? $this->adjustNote : null,
            auth()->user()
        );

        $this->showAdjustModal = false;
        session()->flash('message', "Saldo akun '{$account->name}' berhasil disesuaikan.");
    }

    public function openReconcile(HouseholdContext $context, AccountBalanceService $balanceService): void
    {
        $household = $context->current();
        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->get();

        $this->reconcileDate = now()->toDateString();
        $this->reconcileBalances = [];
        foreach ($accounts as $acc) {
            $this->reconcileBalances[$acc->id] = (string) $balanceService->getBalance($acc);
        }

        $this->reconcilePreviewReady = false;
        $this->showReconcileModal = true;
    }

    public function generateReconcilePreview(ReconcileAccounts $reconciler, HouseholdContext $context): void
    {
        $household = $context->current();
        $date = CarbonImmutable::parse($this->reconcileDate);

        $numericBalances = [];
        foreach ($this->reconcileBalances as $accId => $val) {
            $numericBalances[$accId] = Money::parse($val);
        }

        $this->reconcilePreview = $reconciler->preview($household, $date, $numericBalances);
        $this->reconcilePreviewReady = true;
    }

    public function confirmReconcile(ReconcileAccounts $reconciler, HouseholdContext $context): void
    {
        $household = $context->current();
        $date = CarbonImmutable::parse($this->reconcileDate);

        $numericBalances = [];
        foreach ($this->reconcileBalances as $accId => $val) {
            $numericBalances[$accId] = Money::parse($val);
        }

        $reconciler->confirm($household, $date, $numericBalances, auth()->user());

        $this->showReconcileModal = false;
        $this->reconcilePreviewReady = false;
        session()->flash('message', 'Rekonsiliasi saldo akun berhasil diterapkan.');
    }

    public function render(HouseholdContext $context, AccountBalanceService $balanceService): View
    {
        $household = $context->current();

        $activeAccounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Account $acc) => [
                'account' => $acc,
                'balance' => $balanceService->getBalance($acc),
            ]);

        $archivedAccounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNotNull('archived_at')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Account $acc) => [
                'account' => $acc,
                'balance' => $balanceService->getBalance($acc),
            ]);

        return view('finance::accounts.index', [
            'activeAccounts' => $activeAccounts,
            'archivedAccounts' => $archivedAccounts,
        ])->layout('layouts.app', ['title' => 'Kelola Akun & Dompet']);
    }
}
