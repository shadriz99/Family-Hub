<?php

declare(strict_types=1);

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
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'onboarded'])->group(function (): void {
    // Assets Index & Create
    Route::get('/assets', AssetIndex::class)->name('assets.index');
    Route::get('/assets/create', AssetForm::class)->name('assets.create');

    // Maintenance Cross-asset
    Route::get('/assets/maintenance', MaintenanceIndex::class)->name('assets.maintenance');

    // Documents
    Route::get('/assets/documents', DocumentIndex::class)->name('assets.documents');
    Route::get('/assets/documents/create', DocumentForm::class)->name('assets.documents.create');
    Route::get('/assets/documents/{document}', DocumentShow::class)->name('assets.documents.show');
    Route::get('/assets/documents/{document}/edit', DocumentForm::class)->name('assets.documents.edit');

    // Issues / Kerusakan
    Route::get('/assets/issues', IssueIndex::class)->name('assets.issues');
    Route::get('/assets/issues/create', IssueForm::class)->name('assets.issues.create');
    Route::get('/assets/issues/{issue}', IssueShow::class)->name('assets.issues.show');
    Route::get('/assets/issues/{issue}/edit', IssueForm::class)->name('assets.issues.edit');

    // Vendors
    Route::get('/assets/vendors', VendorIndex::class)->name('assets.vendors');
    Route::get('/assets/vendors/create', VendorForm::class)->name('assets.vendors.create');
    Route::get('/assets/vendors/{vendor}', VendorShow::class)->name('assets.vendors.show');
    Route::get('/assets/vendors/{vendor}/edit', VendorForm::class)->name('assets.vendors.edit');

    // Locations
    Route::get('/assets/locations', LocationIndex::class)->name('assets.locations');

    // Maintenance Plan form
    Route::get('/assets/{asset}/plans/create', MaintenanceForm::class)->name('assets.plans.create');
    Route::get('/assets/plans/{plan}/edit', MaintenanceForm::class)->name('assets.plans.edit');

    // Service Record form
    Route::get('/assets/{asset}/services/create', ServiceForm::class)->name('assets.services.create');

    // Asset Document create for specific asset
    Route::get('/assets/{asset}/documents/create', DocumentForm::class)->name('assets.asset_documents.create');

    // Asset Detail & Edit (last to prevent matching collisions)
    Route::get('/assets/{asset}', AssetShow::class)->name('assets.show');
    Route::get('/assets/{asset}/edit', AssetForm::class)->name('assets.edit');
});
