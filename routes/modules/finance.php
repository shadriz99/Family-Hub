<?php

declare(strict_types=1);

use App\Modules\Finance\Http\Controllers\ExportTransactionsCsvController;
use App\Modules\Finance\Livewire\Accounts\Index as AccountsIndex;
use App\Modules\Finance\Livewire\Budgets\Index as BudgetsIndex;
use App\Modules\Finance\Livewire\Categories\Index as CategoriesIndex;
use App\Modules\Finance\Livewire\Loans\Form as LoansForm;
use App\Modules\Finance\Livewire\Loans\Index as LoansIndex;
use App\Modules\Finance\Livewire\Loans\Show as LoansShow;
use App\Modules\Finance\Livewire\Overview;
use App\Modules\Finance\Livewire\Plan\Index as PlanIndex;
use App\Modules\Finance\Livewire\Recurring\Index as RecurringIndex;
use App\Modules\Finance\Livewire\Reports\Index as ReportsIndex;
use App\Modules\Finance\Livewire\Transactions\Form as TransactionsForm;
use App\Modules\Finance\Livewire\Transactions\Index as TransactionsIndex;
use App\Modules\Finance\Livewire\Transactions\Show as TransactionsShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'onboarded'])->prefix('finance')->name('finance.')->group(function (): void {
    // Overview
    Route::get('/', Overview::class)->name('index');

    // Transactions
    Route::get('/transactions', TransactionsIndex::class)->name('transactions.index');
    Route::get('/transactions/create', TransactionsForm::class)->name('transactions.create');
    Route::get('/transactions/{id}', TransactionsShow::class)->name('transactions.show');

    // Accounts
    Route::get('/accounts', AccountsIndex::class)->name('accounts.index');

    // Categories
    Route::get('/categories', CategoriesIndex::class)->name('categories.index');

    // Budgets
    Route::get('/budgets', BudgetsIndex::class)->name('budgets.index');

    // Monthly Plan
    Route::get('/plan', PlanIndex::class)->name('plan.index');

    // Recurring Bills & Income
    Route::get('/recurring', RecurringIndex::class)->name('recurring.index');

    // Loans (Hutang & Piutang)
    Route::get('/loans', LoansIndex::class)->name('loans.index');
    Route::get('/loans/create', LoansForm::class)->name('loans.create');
    Route::get('/loans/{id}/edit', LoansForm::class)->name('loans.edit');
    Route::get('/loans/{id}', LoansShow::class)->name('loans.show');

    // Reports
    Route::get('/reports', ReportsIndex::class)->name('reports.index');

    // CSV Export
    Route::get('/export/transactions.csv', ExportTransactionsCsvController::class)->name('export.transactions');
});
