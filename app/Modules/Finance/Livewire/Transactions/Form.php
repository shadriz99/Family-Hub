<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire\Transactions;

use App\Core\Support\HouseholdContext;
use App\Core\Support\Money;
use App\Modules\Finance\Enums\AllocationCategory;
use App\Modules\Finance\Enums\CategoryType;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Events\TransactionRecorded;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\AccountBalanceService;
use App\Modules\Finance\Services\CategorySuggestionService;
use App\Modules\Finance\Services\ReceiptOcrService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

final class Form extends Component
{
    use WithFileUploads;

    // Mode: 'quick', 'detail', 'transfer', 'scan'
    public string $mode = 'quick';
    public string $type = 'expense';

    // Transaction form fields
    public string $amount = '';
    public ?string $account_id = null;
    public ?string $to_account_id = null;
    public ?string $category_id = null;
    public ?string $parent_category_id = null;
    public string $subcategory_name = '';
    public string $occurred_on = '';
    public string $payee = '';
    public string $note = '';
    public ?string $allocation = null;
    public ?string $allocation_detail = null;

    // Transfer admin fee
    public string $admin_fee = '';

    // File upload for receipt
    /** @var mixed */
    public $receipt_file = null;
    public ?string $receipt_ocr_summary = null;

    // Suggestions
    public ?string $suggested_category_name = null;
    public ?string $suggested_category_id = null;

    public function mount(HouseholdContext $context): void
    {
        $this->occurred_on = now()->toDateString();
        $this->type = request()->query('type', 'expense');
        if ($this->type === 'transfer') {
            $this->mode = 'transfer';
        }

        $household = $context->current();

        // Default to last account used by this user in this household
        $lastTx = Transaction::query()
            ->where('household_id', $household->id)
            ->where('created_by', auth()->id())
            ->latest('created_at')
            ->first();

        $defaultAccount = $lastTx !== null ? $lastTx->account : Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->first();

        $this->account_id = $defaultAccount?->id;
    }

    public function updatedPayee(CategorySuggestionService $suggester, HouseholdContext $context): void
    {
        if (trim($this->payee) === '') {
            $this->suggested_category_name = null;
            $this->suggested_category_id = null;

            return;
        }

        $household = $context->current();
        $typeEnum = TransactionType::tryFrom($this->type) ?? TransactionType::Expense;
        $suggestion = $suggester->suggestCategory($household, $this->payee, $typeEnum);

        if ($suggestion !== null) {
            $this->suggested_category_id = $suggestion->id;
            $this->suggested_category_name = $suggestion->name;
        } else {
            $this->suggested_category_id = null;
            $this->suggested_category_name = null;
        }
    }

    public function applySuggestion(): void
    {
        if ($this->suggested_category_id !== null) {
            $cat = Category::find($this->suggested_category_id);
            if ($cat !== null) {
                if ($cat->parent_id !== null) {
                    $this->parent_category_id = $cat->parent_id;
                    $this->subcategory_name = $cat->name;
                    $this->category_id = $cat->id;
                } else {
                    $this->parent_category_id = $cat->id;
                    $this->subcategory_name = '';
                    $this->category_id = $cat->id;
                }
            }
            $this->suggested_category_name = null;
        }
    }

    public function updatedReceiptFile(ReceiptOcrService $ocrService): void
    {
        if (! $this->receipt_file) {
            return;
        }

        $this->validate([
            'receipt_file' => 'image|max:10240', // 10MB max
        ]);

        $path = $this->receipt_file->getRealPath();
        $result = $ocrService->scan($path);

        if ($result['payee'] !== null) {
            $this->payee = $result['payee'];
        }
        if ($result['occurred_on'] !== null) {
            $this->occurred_on = $result['occurred_on']->toDateString();
        }
        if ($result['amount'] !== null && $result['amount'] > 0) {
            $this->amount = (string) $result['amount'];
        }

        $confidencePct = round($result['confidence'] * 100);
        $this->receipt_ocr_summary = "Pindai berhasil (Keyakinan: {$confidencePct}%). Silakan tinjau dan konfirmasi sebelum menyimpan.";
        $this->mode = 'detail'; // US-FIN-01 AC5: scan always enters review step
    }

    /**
     * Quick save when tapping a category directly (US-FIN-01 AC2).
     */
    public function quickSaveCategory(string $categoryId, HouseholdContext $context, AccountBalanceService $balanceService): void
    {
        $this->parent_category_id = null;
        $this->subcategory_name = '';
        $this->category_id = $categoryId;

        $parsedAmount = 0;
        try {
            $parsedAmount = Money::parse($this->amount);
        } catch (\Throwable) {
            $this->addError('amount', 'Nominal tidak valid.');

            return;
        }

        if ($parsedAmount <= 0) {
            $this->addError('amount', 'Masukkan nominal sebelum memilih kategori.');

            return;
        }

        $this->saveTransaction($context, $balanceService);
    }

    public function saveTransaction(HouseholdContext $context, AccountBalanceService $balanceService): void
    {
        $household = $context->current();

        if ($this->type !== 'transfer') {
            if ($this->parent_category_id !== null) {
                $parentCat = Category::query()->where('household_id', $household->id)->findOrFail($this->parent_category_id);
                $cleanSub = trim($this->subcategory_name);

                if ($cleanSub !== '') {
                    $foundSub = Category::query()
                        ->where('household_id', $household->id)
                        ->where('parent_id', $parentCat->id)
                        ->whereRaw('LOWER(name) = ?', [strtolower($cleanSub)])
                        ->first();

                    if ($foundSub === null) {
                        $foundSub = Category::create([
                            'household_id' => $household->id,
                            'parent_id' => $parentCat->id,
                            'name' => $cleanSub,
                            'type' => $parentCat->type,
                            'default_allocation' => $parentCat->default_allocation,
                            'created_by' => auth()->id(),
                        ]);
                    }
                    $this->category_id = $foundSub->id;
                } else {
                    $this->category_id = $parentCat->id;
                }
            }
        }

        $rules = [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required'],
            'account_id' => ['required', 'exists:fin_accounts,id'],
            'occurred_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'payee' => ['nullable', 'string', 'max:100'],
        ];

        if ($this->type === 'transfer') {
            $rules['to_account_id'] = ['required', 'exists:fin_accounts,id', 'different:account_id'];
        } else {
            $rules['category_id'] = ['required', 'exists:fin_categories,id'];
        }

        $this->validate($rules);

        $parsedAmount = Money::parse($this->amount);
        $occurredOnDate = CarbonImmutable::parse($this->occurred_on);
        $typeEnum = TransactionType::from($this->type);

        $category = null;
        $allocationEnum = null;

        if ($this->category_id !== null) {
            $category = Category::query()->findOrFail($this->category_id);
            if ($typeEnum === TransactionType::Expense) {
                $allocationEnum = $this->allocation !== null
                    ? AllocationCategory::tryFrom($this->allocation)
                    : $category->default_allocation;
            }
        } elseif ($typeEnum === TransactionType::Transfer && $this->allocation === 'savings') {
            $allocationEnum = AllocationCategory::Savings;
        }

        $tx = DB::transaction(function () use ($household, $typeEnum, $parsedAmount, $occurredOnDate, $category, $allocationEnum, $balanceService): Transaction {
            $transaction = new Transaction();
            $transaction->household_id = $household->id;
            $transaction->type = $typeEnum;
            $transaction->account_id = $this->account_id;
            $transaction->to_account_id = $typeEnum === TransactionType::Transfer ? $this->to_account_id : null;
            $transaction->category_id = $category?->id;
            $transaction->amount = $parsedAmount;
            $transaction->occurred_on = $occurredOnDate;
            $transaction->payee = $this->payee !== '' ? $this->payee : null;
            $transaction->note = $this->note !== '' ? $this->note : null;
            $transaction->allocation = $allocationEnum;
            $transaction->allocation_detail = $this->allocation_detail !== '' ? $this->allocation_detail : null;
            $transaction->created_by = auth()->id();
            $transaction->save();

            $balanceService->bustCache($transaction->account_id);
            if ($transaction->to_account_id !== null) {
                $balanceService->bustCache($transaction->to_account_id);
            }

            TransactionRecorded::dispatch($transaction->id);

            // US-FIN-02 AC1: Optional admin fee transfer creates linked transaction
            if ($typeEnum === TransactionType::Transfer && trim($this->admin_fee) !== '') {
                $parsedFee = Money::parse($this->admin_fee);
                if ($parsedFee > 0) {
                    $feeCategory = Category::query()
                        ->where('household_id', $household->id)
                        ->where('system_key', 'bank_fee')
                        ->first();

                    if ($feeCategory !== null) {
                        $feeTx = new Transaction();
                        $feeTx->household_id = $household->id;
                        $feeTx->type = TransactionType::Expense;
                        $feeTx->account_id = $this->account_id;
                        $feeTx->category_id = $feeCategory->id;
                        $feeTx->amount = $parsedFee;
                        $feeTx->occurred_on = $occurredOnDate;
                        $feeTx->note = 'Biaya Admin Transfer';
                        $feeTx->related_transaction_id = $transaction->id;
                        $feeTx->allocation = AllocationCategory::Living;
                        $feeTx->created_by = auth()->id();
                        $feeTx->save();

                        $balanceService->bustCache($this->account_id);
                        TransactionRecorded::dispatch($feeTx->id);
                    }
                }
            }

            return $transaction;
        });

        // US-FIN-01 AC3: Toast with 5-second undo support
        $formatted = Money::format($parsedAmount);
        $catLabel = $category !== null ? $category->name : 'Transfer';
        $message = "{$typeEnum->label()} {$formatted} – {$catLabel} tersimpan";

        session()->flash('undo_id', $tx->id);
        session()->flash('undo_message', $message);

        $this->redirect(route('finance.transactions.index'), navigate: true);
    }

    public function render(HouseholdContext $context): View
    {
        $household = $context->current();

        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get();

        $typeEnum = TransactionType::tryFrom($this->type) ?? TransactionType::Expense;
        $catType = $typeEnum === TransactionType::Income ? CategoryType::Income : CategoryType::Expense;

        // Top 8 most frequent categories in last 30 days for quick add grid
        $frequentCategoryIds = Transaction::query()
            ->where('household_id', $household->id)
            ->where('type', $typeEnum)
            ->where('occurred_on', '>=', now()->subDays(30)->toDateString())
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(8)
            ->pluck('category_id')
            ->all();

        $quickCategories = Category::query()
            ->where('household_id', $household->id)
            ->whereIn('id', $frequentCategoryIds)
            ->with('parent')
            ->get();

        // If less than 8, fill with default active categories
        if ($quickCategories->count() < 8) {
            $filler = Category::query()
                ->where('household_id', $household->id)
                ->where('type', $catType)
                ->whereNull('archived_at')
                ->whereNotIn('id', $quickCategories->pluck('id'))
                ->with('parent')
                ->limit(8 - $quickCategories->count())
                ->get();
            $quickCategories = $quickCategories->merge($filler);
        }

        $parentCategories = Category::query()
            ->where('household_id', $household->id)
            ->whereNull('parent_id')
            ->where('type', $catType)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get();

        if ($this->parent_category_id === null && $parentCategories->isNotEmpty()) {
            $this->parent_category_id = $parentCategories->first()->id;
        }

        $availableSubcategories = $this->parent_category_id !== null
            ? Category::query()
                ->where('household_id', $household->id)
                ->where('parent_id', $this->parent_category_id)
                ->whereNull('archived_at')
                ->orderBy('sort_order')
                ->get()
            : collect();

        $allCategories = Category::query()
            ->where('household_id', $household->id)
            ->where('type', $catType)
            ->whereNull('archived_at')
            ->with('parent')
            ->orderBy('sort_order')
            ->get();

        return view('finance::transactions.form', [
            'accounts' => $accounts,
            'quickCategories' => $quickCategories,
            'allCategories' => $allCategories,
            'parentCategories' => $parentCategories,
            'availableSubcategories' => $availableSubcategories,
        ])->layout('layouts.app', ['title' => 'Catat Transaksi']);
    }
}
