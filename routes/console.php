<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reminders:dispatch')->everyMinute();
Schedule::command('backup:clean')->dailyAt('01:30');
Schedule::command('backup:run')->dailyAt('02:00');

// Scheduled Finance jobs (§10)
Schedule::command('finance:generate-planned-items')->dailyAt('00:10');
Schedule::command('finance:mark-overdue')->dailyAt('00:20');
Schedule::command('finance:apply-period-settings')->daily();

// Scheduled Organizer jobs (IMP-01, BR-ORG-001)
Schedule::command('organizer:roll-recurring-tasks')->dailyAt('00:30');

// Scheduled Asset jobs (IMP-01, §10)
Schedule::command('assets:refresh-due-dates')->dailyAt('00:40');
Schedule::command('assets:odometer-reminders')->monthlyOn(1, '08:00');

// Scheduled Core & Notification jobs (IMP-01, SEC-9)
Schedule::command('digest:send')->everyFifteenMinutes();
Schedule::command('households:purge-deleted')->dailyAt('03:10');
