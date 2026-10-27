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
