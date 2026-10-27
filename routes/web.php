<?php

declare(strict_types=1);

use App\Livewire\Admin\AdminDashboard;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Settings\ActivityLogViewer;
use App\Livewire\Settings\HouseholdSettings;
use App\Livewire\Settings\NotificationSettings;
use App\Livewire\Settings\ProfileSettings;
use Illuminate\Support\Facades\Route;

Route::post('/forgot-password', [Laravel\Fortify\Http\Controllers\PasswordResetLinkController::class, 'store'])
    ->middleware(['web', 'guest', 'throttle:forgot-password'])
    ->name('password.email');

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();

        if ($user !== null && $user->is_system_admin) {
            return redirect()->route('admin.dashboard');
        }

        $household = app(\App\Core\Support\HouseholdContext::class)->currentOrNull();

        if ($household === null) {
            return view('welcome');
        }

        $manager = app(\App\Core\Dashboard\DashboardManager::class);

        return view('dashboard', [
            'household' => $household,
            'greeting' => $user !== null ? $manager->greeting($user->name, null, $household->timezone) : __('Selamat datang'),
            'alerts' => $manager->alerts($household),
            'todayAndTomorrow' => $manager->todayAndTomorrow($household),
            'upcomingWeek' => $manager->upcomingWeek($household),
            'widgets' => $manager->widgets($household),
        ]);
    }

    return view('welcome');
})->middleware(['onboarded']);

Route::get('/onboarding', fn () => view('onboarding'))
    ->middleware(['auth'])
    ->name('onboarding');

Route::get('/invitation/{token}', fn (string $token) => view('auth.accept-invitation', ['token' => $token]))
    ->name('invitation.show');

Route::get('/notifications', NotificationCenter::class)
    ->middleware(['auth', 'onboarded'])
    ->name('notifications.index');

Route::get('/settings', fn () => redirect()->route('settings.profile'))
    ->middleware(['auth', 'onboarded'])
    ->name('settings.index');

Route::get('/settings/profile', ProfileSettings::class)
    ->middleware(['auth', 'onboarded'])
    ->name('settings.profile');

Route::get('/settings/household', HouseholdSettings::class)
    ->middleware(['auth', 'onboarded'])
    ->name('settings.household');

Route::get('/settings/notifications', NotificationSettings::class)
    ->middleware(['auth', 'onboarded'])
    ->name('settings.notifications');

Route::get('/settings/activity', ActivityLogViewer::class)
    ->middleware(['auth', 'onboarded'])
    ->name('settings.activity');

Route::get('/admin', AdminDashboard::class)
    ->middleware(['auth', 'admin'])
    ->name('admin.dashboard');
