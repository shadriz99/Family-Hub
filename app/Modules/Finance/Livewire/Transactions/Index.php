<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire\Transactions;

use App\Core\Support\HouseholdContext;
use App\Core\Support\Period;
use App\Core\Support\PeriodResolver;
use App\Modules\Finance\Enums\TransactionType;
use App\Modules\Finance\Events\TransactionDeleted;
use App\Modules\Finance\Models\Account;
use App\Modules\Finance\Models\Category;
use App\Modules\Finance\Models\Transaction;
use App\Modules\Finance\Services\AccountBalanceService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    // Filters
    public ?string $periodKey = null;
    public string $type = 'all';
    public ?string $account_id = null;
    public ?string $category_id = null;
    public ?string $created_by = null;
    public string $search = '';

    // Undo toast state
    public ?string $undoTransactionId = null;
    public ?string $undoMessage = null;

    /** @var array<string, mixed> */
    protected $queryString = [
        'periodKey' => ['except' => null, 'as' => 'period'],
        'type' => ['except' => 'all'],
        'account_id' => ['except' => null, 'as' => 'account'],
        'category_id' => ['except' => null, 'as' => 'category'],
        'created_by' => ['except' => null, 'as' => 'by'],
        'search' => ['except' => ''],
    ];

    public function mount(HouseholdContext $context): void
    {
        $household = $context->current();
        $resolver = PeriodResolver::for($household);

        if ($this->periodKey === null) {
            $this->periodKey = $resolver->current()->key;
        }

        if (session()->has('undo_id')) {
            $this->undoTransactionId = (string) session('undo_id');
            $this->undoMessage = (string) session('undo_message');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function undoLastTransaction(AccountBalanceService $balanceService, HouseholdContext $context): void
    {
        if ($this->undoTransactionId === null) {
            return;
        }

        $household = $context->current();
        $tx = Transaction::query()
            ->where('household_id', $household->id)
            ->find($this->undoTransactionId);

        if ($tx !== null) {
            $accId = $tx->account_id;
            $toAccId = $tx->to_account_id;
            $plannedId = $tx->planned_item_id;
            $catId = $tx->category_id;
            $txId = $tx->id;

            $tx->delete();

            $balanceService->bustCache($accId);
            if ($toAccId !== null) {
                $balanceService->bustCache($toAccId);
            }

            TransactionDeleted::dispatch($txId, $accId, $toAccId, $plannedId, $catId);

            session()->flash('message', 'Transaksi berhasil dibatalkan.');
        }

        $this->undoTransactionId = null;
        $this->undoMessage = null;
    }

    public function render(HouseholdContext $context): View
    {
        $household = $context->current();
        $resolver = PeriodResolver::for($household);

        /** @var Period $period */
        $period = $resolver->fromKey($this->periodKey ?? $resolver->current()->key);
        $prevPeriod = $resolver->previous($period);
        $nextPeriod = $resolver->next($period);

        $query = Transaction::query()
            ->where('household_id', $household->id)
            ->whereBetween('occurred_on', [
                $period->start->toDateString(),
                $period->end->toDateString(),
            ])
            ->with(['account', 'toAccount', 'category.parent', 'creator', 'plannedItem'])
            ->orderByDesc('occurred_on')
            ->orderByDesc('created_at');

        if ($this->type !== 'all') {
            $query->where('type', $this->type);
        }

        if ($this->account_id) {
            $accId = $this->account_id;
            $query->where(function ($q) use ($accId) {
                $q->where('account_id', $accId)->orWhere('to_account_id', $accId);
            });
        }

        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        }

        if ($this->created_by) {
            $query->where('created_by', $this->created_by);
        }

        if (trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('note', 'like', $term)
                    ->orWhere('payee', 'like', $term);
            });
        }

        $paginated = $query->paginate(30);

        // Group the paginated items by date with daily subtotals (US-FIN-08 AC1)
        $grouped = $paginated->getCollection()->groupBy(fn (Transaction $tx) => $tx->occurred_on->toDateString());

        $dailyGroups = [];
        foreach ($grouped as $dateStr => $txs) {
            $dateCarbon = CarbonImmutable::parse($dateStr);
            $incomeSum = $txs->where('type', TransactionType::Income)->sum('amount');
            $expenseSum = $txs->where('type', TransactionType::Expense)->sum('amount');

            $dailyGroups[] = [
                'date' => $dateCarbon,
                'transactions' => $txs,
                'income_total' => $incomeSum,
                'expense_total' => $expenseSum,
            ];
        }

        $accounts = Account::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->orderBy('sort_order')
            ->get();

        $categories = Category::query()
            ->where('household_id', $household->id)
            ->whereNull('archived_at')
            ->with('parent')
            ->orderBy('sort_order')
            ->get();

        $members = $household->members()->with('user')->get()->map(fn ($m) => $m->user)->filter();

        return view('finance::transactions.index', [
            'period' => $period,
            'prevPeriod' => $prevPeriod,
            'nextPeriod' => $nextPeriod,
            'dailyGroups' => $dailyGroups,
            'paginated' => $paginated,
            'accounts' => $accounts,
            'categories' => $categories,
            'members' => $members,
        ])->layout('layouts.app', ['title' => 'Daftar Transaksi']);
    }
}
