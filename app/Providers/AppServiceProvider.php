<?php

declare(strict_types=1);

namespace App\Providers;

use App\Core\Models\Household;
use App\Core\Policies\HouseholdPolicy;
use App\Core\Support\HouseholdContext;
use Illuminate\Database\Eloquent\Model;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Household::class, HouseholdPolicy::class);
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
