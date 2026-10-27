<?php

declare(strict_types=1);

namespace App\Modules\Organizer\Providers;

use App\Modules\Organizer\Integrations\OrganizerAlertSource;
use App\Modules\Organizer\Integrations\OrganizerEventCalendarSource;
use App\Modules\Organizer\Integrations\OrganizerHouseholdExporter;
use App\Modules\Organizer\Integrations\OrganizerSummaryWidget;
use App\Modules\Organizer\Integrations\OrganizerTaskCalendarSource;
use App\Modules\Organizer\Livewire\Calendar\Index as CalendarIndex;
use App\Modules\Organizer\Livewire\Notes\Editor as NoteEditor;
use App\Modules\Organizer\Livewire\Notes\Index as NotesIndex;
use App\Modules\Organizer\Livewire\Reminders\Index as RemindersIndex;
use App\Modules\Organizer\Livewire\Shopping\Index as ShoppingIndex;
use App\Modules\Organizer\Livewire\Shopping\Show as ShoppingShow;
use App\Modules\Organizer\Livewire\Tasks\Form as TaskForm;
use App\Modules\Organizer\Livewire\Tasks\Index as TasksIndex;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class OrganizerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register integrations as tagged singletons
        $this->app->singleton(OrganizerEventCalendarSource::class);
        $this->app->singleton(OrganizerTaskCalendarSource::class);
        $this->app->singleton(OrganizerAlertSource::class);
        $this->app->singleton(OrganizerSummaryWidget::class);
        $this->app->singleton(OrganizerHouseholdExporter::class);

        $this->app->tag([
            OrganizerEventCalendarSource::class,
            OrganizerTaskCalendarSource::class,
        ], 'calendar.sources');

        $this->app->tag([OrganizerAlertSource::class], 'alert.sources');
        $this->app->tag([OrganizerSummaryWidget::class], 'dashboard.widgets');
        $this->app->tag([OrganizerHouseholdExporter::class], 'household.exporters');
    }

    public function boot(): void
    {
        if (! config('modules.organizer', false)) {
            return;
        }

        // Register routes
        Route::middleware('web')->group(base_path('routes/modules/organizer.php'));

        // Register Livewire components
        Livewire::component('organizer.tasks.index', TasksIndex::class);
        Livewire::component('organizer.tasks.form', TaskForm::class);
        Livewire::component('organizer.notes.index', NotesIndex::class);
        Livewire::component('organizer.notes.editor', NoteEditor::class);
        Livewire::component('organizer.shopping.index', ShoppingIndex::class);
        Livewire::component('organizer.shopping.show', ShoppingShow::class);
        Livewire::component('organizer.calendar.index', CalendarIndex::class);
        Livewire::component('organizer.reminders.index', RemindersIndex::class);
    }
}
