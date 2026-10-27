<?php

declare(strict_types=1);

use App\Modules\Organizer\Http\Controllers\CalendarExportController;
use App\Modules\Organizer\Livewire\Calendar\Index as CalendarIndex;
use App\Modules\Organizer\Livewire\Notes\Editor as NoteEditor;
use App\Modules\Organizer\Livewire\Notes\Index as NotesIndex;
use App\Modules\Organizer\Livewire\Reminders\Index as RemindersIndex;
use App\Modules\Organizer\Livewire\Shopping\Index as ShoppingIndex;
use App\Modules\Organizer\Livewire\Shopping\Show as ShoppingShow;
use App\Modules\Organizer\Livewire\Tasks\Form as TaskForm;
use App\Modules\Organizer\Livewire\Tasks\Index as TasksIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'onboarded'])->group(function (): void {
    // Tasks
    Route::get('/organizer/tasks', TasksIndex::class)->name('organizer.tasks.index');
    Route::get('/organizer/tasks/create', TaskForm::class)->name('organizer.tasks.create');
    Route::get('/organizer/tasks/{id}', TaskForm::class)->name('organizer.tasks.edit');

    // Notes
    Route::get('/organizer/notes', NotesIndex::class)->name('organizer.notes.index');
    Route::get('/organizer/notes/create', NoteEditor::class)->name('organizer.notes.create');
    Route::get('/organizer/notes/{id}', NoteEditor::class)->name('organizer.notes.edit');

    // Shopping
    Route::get('/organizer/shopping', ShoppingIndex::class)->name('organizer.shopping.index');
    Route::get('/organizer/shopping/{id}', ShoppingShow::class)->name('organizer.shopping.show');

    // Calendar
    Route::get('/calendar', CalendarIndex::class)->name('calendar.index');
    Route::get('/calendar/export', CalendarExportController::class)->name('calendar.export');

    // Reminders
    Route::get('/organizer/reminders', RemindersIndex::class)->name('organizer.reminders.index');
});
