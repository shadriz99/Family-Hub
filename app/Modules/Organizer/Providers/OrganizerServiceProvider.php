<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Providers;

use Illuminate\Support\ServiceProvider;

final class OrganizerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings and integration singletons will be registered here as features are added
    }

    public function boot(): void
    {
        if (! config('modules.organizer', false)) {
            return;
        }

        // Module routes and components will be registered here
    }
}
