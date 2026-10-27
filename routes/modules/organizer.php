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
    // Tasks (canonical and shorthand aliases)
    Route::get('/tasks', TasksIndex::class)->name('tasks.index');
    Route::get('/tasks/create', TaskForm::class)->name('tasks.create');
    Route::get('/tasks/{id}', TaskForm::class)->name('tasks.edit');
    Route::get('/organizer/tasks', TasksIndex::class)->name('organizer.tasks.index');
    Route::get('/organizer/tasks/create', TaskForm::class)->name('organizer.tasks.create');
    Route::get('/organizer/tasks/{id}', TaskForm::class)->name('organizer.tasks.edit');

    // Notes
    Route::get('/notes', NotesIndex::class)->name('notes.index');
    Route::get('/notes/create', NoteEditor::class)->name('notes.create');
    Route::get('/notes/{id}', NoteEditor::class)->name('notes.edit');
    Route::get('/organizer/notes', NotesIndex::class)->name('organizer.notes.index');
    Route::get('/organizer/notes/create', NoteEditor::class)->name('organizer.notes.create');
    Route::get('/organizer/notes/{id}', NoteEditor::class)->name('organizer.notes.edit');

    // Shopping
    Route::get('/shopping', ShoppingIndex::class)->name('shopping.index');
    Route::get('/shopping/create', fn () => redirect()->route('organizer.shopping.index'))->name('shopping.create');
    Route::get('/shopping/{id}', ShoppingShow::class)->name('shopping.show');
    Route::get('/organizer/shopping', ShoppingIndex::class)->name('organizer.shopping.index');
    Route::get('/organizer/shopping/create', fn () => redirect()->route('organizer.shopping.index'));
    Route::get('/organizer/shopping/{id}', ShoppingShow::class)->name('organizer.shopping.show');

    // Calendar
    Route::get('/calendar', CalendarIndex::class)->name('calendar.index');
    Route::get('/calendar/export', CalendarExportController::class)->name('calendar.export');

    // Reminders
    Route::get('/reminders', RemindersIndex::class)->name('reminders.index');
    Route::get('/organizer/reminders', RemindersIndex::class)->name('organizer.reminders.index');
});
