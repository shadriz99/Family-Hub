<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        $household = app(\App\Core\Support\HouseholdContext::class)->currentOrNull();

        return view('dashboard', ['household' => $household]);
    }

    return view('welcome');
})->middleware(['onboarded']);

Route::get('/onboarding', fn () => view('onboarding'))
    ->middleware(['auth'])
    ->name('onboarding');

Route::get('/invitation/{token}', fn (string $token) => view('auth.accept-invitation', ['token' => $token]))
    ->name('invitation.show');
