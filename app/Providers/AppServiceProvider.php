<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Alerts\AlertAggregator;
use App\Core\Calendar\CalendarAggregator;
use App\Core\Console\Commands\CreateSystemAdminCommand;
use App\Core\Console\Commands\DispatchRemindersCommand;
use App\Core\Console\Commands\PurgeSoftDeletedHouseholdsCommand;
use App\Core\Console\Commands\SendDailyDigestCommand;
use App\Core\Console\Commands\SendTestEmailCommand;
use App\Core\Dashboard\DashboardManager;
use App\Core\Exporters\CoreHouseholdExporter;
use App\Core\Models\Household;
use App\Core\Policies\HouseholdPolicy;
use App\Core\Services\HouseholdExportService;
use App\Core\Support\HouseholdContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HouseholdContext::class);

        $this->app->singleton(CalendarAggregator::class, function ($app) {
            return new CalendarAggregator($app);
        });

        $this->app->singleton(AlertAggregator::class, function ($app) {
            return new AlertAggregator($app);
        });

        $this->app->singleton(DashboardManager::class, function ($app) {
            return new DashboardManager(
                $app->tagged('dashboard.widgets'),
                $app->make(AlertAggregator::class),
                $app->make(CalendarAggregator::class),
            );
        });

        $this->app->tag([CoreHouseholdExporter::class], 'household.exporters');

        $this->app->singleton(HouseholdExportService::class, function ($app) {
            return new HouseholdExportService($app->tagged('household.exporters'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Household::class, HouseholdPolicy::class);
        Model::preventLazyLoading(! $this->app->isProduction());

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateSystemAdminCommand::class,
                DispatchRemindersCommand::class,
                SendDailyDigestCommand::class,
                PurgeSoftDeletedHouseholdsCommand::class,
                SendTestEmailCommand::class,
            ]);
        }

        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event): void {
            if ($event->connection->getDriverName() === 'sqlite') {
                $event->connection->unprepared(
                    'PRAGMA busy_timeout = 5000; PRAGMA journal_mode = WAL; PRAGMA synchronous = NORMAL; PRAGMA cache_size = -8000; PRAGMA temp_store = MEMORY;'
                );
            }
        });
    }
}
