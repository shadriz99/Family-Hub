<?php

declare(strict_types=1);

namespace App\Modules\Finance\Providers;

use App\Core\Events\HouseholdCreated;
use App\Core\Support\HouseholdContext;
use App\Modules\Finance\Console\Commands\ApplyPeriodSettingsCommand;
use App\Modules\Finance\Console\Commands\GeneratePlannedItemsCommand;
use App\Modules\Finance\Console\Commands\MarkOverduePlannedItemsCommand;
use App\Modules\Finance\Console\Commands\TelegramPollCommand;
use App\Modules\Finance\Contracts\FinanceGateway;
use App\Modules\Finance\Events\TransactionDeleted;
use App\Modules\Finance\Events\TransactionRecorded;
use App\Modules\Finance\Events\TransactionUpdated;
use App\Modules\Finance\Integrations\FinanceAlertSource;
use App\Modules\Finance\Integrations\FinanceHouseholdExporter;
use App\Modules\Finance\Integrations\FinanceSummaryWidget;
use App\Modules\Finance\Integrations\PlannedItemCalendarSource;
use App\Modules\Finance\Listeners\HandleTransactionEvents;
use App\Modules\Finance\Listeners\SeedDefaultFinanceCategories;
use App\Modules\Finance\Services\DefaultFinanceGateway;
use App\Modules\Finance\Services\NullFinanceGateway;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FinanceGateway::class, function ($app): FinanceGateway {
            if (! config('modules.finance', false)) {
                return $app->make(NullFinanceGateway::class);
            }

            /** @var HouseholdContext $context */
            $context = $app->make(HouseholdContext::class);
            $household = $context->currentOrNull();

            if ($household === null || ! $household->finance_integration_enabled) {
                return $app->make(NullFinanceGateway::class);
            }

            return $app->make(DefaultFinanceGateway::class);
        });

        // Register integrations as tagged singletons
        $this->app->singleton(PlannedItemCalendarSource::class);
        $this->app->singleton(FinanceAlertSource::class);
        $this->app->singleton(FinanceSummaryWidget::class);
        $this->app->singleton(FinanceHouseholdExporter::class);

        $this->app->tag([PlannedItemCalendarSource::class], 'calendar.sources');
        $this->app->tag([FinanceAlertSource::class], 'alert.sources');
        $this->app->tag([FinanceSummaryWidget::class], 'dashboard.widgets');
        $this->app->tag([FinanceHouseholdExporter::class], 'household.exporters');
    }

    public function boot(): void
    {
        Event::listen(HouseholdCreated::class, SeedDefaultFinanceCategories::class);

        // Register transaction event listeners
        Event::listen(TransactionRecorded::class, [HandleTransactionEvents::class, 'onRecorded']);
        Event::listen(TransactionUpdated::class, [HandleTransactionEvents::class, 'onUpdated']);
        Event::listen(TransactionDeleted::class, [HandleTransactionEvents::class, 'onDeleted']);

        if ($this->app->runningInConsole()) {
            $this->commands([
                GeneratePlannedItemsCommand::class,
                MarkOverduePlannedItemsCommand::class,
                ApplyPeriodSettingsCommand::class,
                TelegramPollCommand::class,
            ]);
        }

        if (! config('modules.finance', false)) {
            return;
        }

        $this->registerLivewireComponents();
        $this->registerRoutes();
        $this->loadViewsFrom(resource_path('views/modules/finance'), 'finance');
    }

    private function registerLivewireComponents(): void
    {
        Livewire::component('finance.overview', \App\Modules\Finance\Livewire\Overview::class);
        Livewire::component('finance.accounts.index', \App\Modules\Finance\Livewire\Accounts\Index::class);
        Livewire::component('finance.categories.index', \App\Modules\Finance\Livewire\Categories\Index::class);
        Livewire::component('finance.transactions.index', \App\Modules\Finance\Livewire\Transactions\Index::class);
        Livewire::component('finance.transactions.form', \App\Modules\Finance\Livewire\Transactions\Form::class);
        Livewire::component('finance.transactions.show', \App\Modules\Finance\Livewire\Transactions\Show::class);
        Livewire::component('finance.budgets.index', \App\Modules\Finance\Livewire\Budgets\Index::class);
        Livewire::component('finance.plan.index', \App\Modules\Finance\Livewire\Plan\Index::class);
        Livewire::component('finance.recurring.index', \App\Modules\Finance\Livewire\Recurring\Index::class);
        Livewire::component('finance.loans.index', \App\Modules\Finance\Livewire\Loans\Index::class);
        Livewire::component('finance.loans.form', \App\Modules\Finance\Livewire\Loans\Form::class);
        Livewire::component('finance.loans.show', \App\Modules\Finance\Livewire\Loans\Show::class);
        Livewire::component('finance.reports.index', \App\Modules\Finance\Livewire\Reports\Index::class);

        // Fallbacks for Livewire auto-resolution from class names
        Livewire::component('app.modules.finance.livewire.accounts', \App\Modules\Finance\Livewire\Accounts\Index::class);
        Livewire::component('app.modules.finance.livewire.categories', \App\Modules\Finance\Livewire\Categories\Index::class);
        Livewire::component('app.modules.finance.livewire.transactions', \App\Modules\Finance\Livewire\Transactions\Index::class);
        Livewire::component('app.modules.finance.livewire.budgets', \App\Modules\Finance\Livewire\Budgets\Index::class);
        Livewire::component('app.modules.finance.livewire.plan', \App\Modules\Finance\Livewire\Plan\Index::class);
        Livewire::component('app.modules.finance.livewire.recurring', \App\Modules\Finance\Livewire\Recurring\Index::class);
        Livewire::component('app.modules.finance.livewire.loans', \App\Modules\Finance\Livewire\Loans\Index::class);
        Livewire::component('app.modules.finance.livewire.reports', \App\Modules\Finance\Livewire\Reports\Index::class);
    }

    private function registerRoutes(): void
    {
        $routesPath = base_path('routes/modules/finance.php');

        if (file_exists($routesPath)) {
            Route::middleware('web')->group($routesPath);
        }
    }
}
