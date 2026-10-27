<?php

declare(strict_types=1);

namespace App\Modules\Asset\Providers;

use App\Core\Events\HouseholdCreated;
use App\Modules\Asset\Commands\RefreshAssetDueDatesCommand;
use App\Modules\Asset\Commands\SendOdometerRemindersCommand;
use App\Modules\Asset\Integrations\AssetAlertSource;
use App\Modules\Asset\Integrations\AssetAttentionWidget;
use App\Modules\Asset\Integrations\AssetDocumentCalendarSource;
use App\Modules\Asset\Integrations\AssetHouseholdExporter;
use App\Modules\Asset\Integrations\AssetIssueCalendarSource;
use App\Modules\Asset\Integrations\AssetMaintenanceCalendarSource;
use App\Modules\Asset\Listeners\SeedDefaultAssetDataListener;
use App\Modules\Asset\Livewire\Assets\Form as AssetForm;
use App\Modules\Asset\Livewire\Assets\Index as AssetIndex;
use App\Modules\Asset\Livewire\Assets\Show as AssetShow;
use App\Modules\Asset\Livewire\Documents\Form as DocumentForm;
use App\Modules\Asset\Livewire\Documents\Index as DocumentIndex;
use App\Modules\Asset\Livewire\Documents\Show as DocumentShow;
use App\Modules\Asset\Livewire\Issues\Form as IssueForm;
use App\Modules\Asset\Livewire\Issues\Index as IssueIndex;
use App\Modules\Asset\Livewire\Issues\Show as IssueShow;
use App\Modules\Asset\Livewire\Locations\Index as LocationIndex;
use App\Modules\Asset\Livewire\Maintenance\Form as MaintenanceForm;
use App\Modules\Asset\Livewire\Maintenance\Index as MaintenanceIndex;
use App\Modules\Asset\Livewire\Services\Form as ServiceForm;
use App\Modules\Asset\Livewire\Vendors\Form as VendorForm;
use App\Modules\Asset\Livewire\Vendors\Index as VendorIndex;
use App\Modules\Asset\Livewire\Vendors\Show as VendorShow;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class AssetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register integrations as singletons and tag them
        $this->app->singleton(AssetMaintenanceCalendarSource::class);
        $this->app->singleton(AssetDocumentCalendarSource::class);
        $this->app->singleton(AssetIssueCalendarSource::class);
        $this->app->singleton(AssetAlertSource::class);
        $this->app->singleton(AssetAttentionWidget::class);
        $this->app->singleton(AssetHouseholdExporter::class);

        $this->app->tag([
            AssetMaintenanceCalendarSource::class,
            AssetDocumentCalendarSource::class,
            AssetIssueCalendarSource::class,
        ], 'calendar.sources');

        $this->app->tag([AssetAlertSource::class], 'alert.sources');
        $this->app->tag([AssetAttentionWidget::class], 'dashboard.widgets');
        $this->app->tag([AssetHouseholdExporter::class], 'household.exporters');
    }

    public function boot(): void
    {
        // Always listen to household creation for seeding if module active
        Event::listen(HouseholdCreated::class, SeedDefaultAssetDataListener::class);

        if (! config('modules.asset', false)) {
            return;
        }

        // Register routes
        Route::middleware('web')->group(base_path('routes/modules/asset.php'));

        // Register Livewire components
        Livewire::component('asset.assets.index', AssetIndex::class);
        Livewire::component('asset.assets.form', AssetForm::class);
        Livewire::component('asset.assets.show', AssetShow::class);
        Livewire::component('asset.maintenance.index', MaintenanceIndex::class);
        Livewire::component('asset.maintenance.form', MaintenanceForm::class);
        Livewire::component('asset.services.form', ServiceForm::class);
        Livewire::component('asset.documents.index', DocumentIndex::class);
        Livewire::component('asset.documents.form', DocumentForm::class);
        Livewire::component('asset.documents.show', DocumentShow::class);
        Livewire::component('asset.issues.index', IssueIndex::class);
        Livewire::component('asset.issues.form', IssueForm::class);
        Livewire::component('asset.issues.show', IssueShow::class);
        Livewire::component('asset.vendors.index', VendorIndex::class);
        Livewire::component('asset.vendors.form', VendorForm::class);
        Livewire::component('asset.vendors.show', VendorShow::class);
        Livewire::component('asset.locations.index', LocationIndex::class);

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                RefreshAssetDueDatesCommand::class,
                SendOdometerRemindersCommand::class,
            ]);
        }
    }
}
