<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Alerts\AlertAggregator;
use App\Core\Calendar\CalendarAggregator;
use App\Core\Console\Commands\CreateSystemAdminCommand;
use App\Core\Models\Household;
use App\Core\Policies\HouseholdPolicy;
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
