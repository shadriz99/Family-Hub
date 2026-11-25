<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureHouseholdOnboarded;
use App\Http\Middleware\EnsureSystemAdmin;
use App\Http\Middleware\UseHouseholdContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
        $middleware->web(append: [UseHouseholdContext::class]);
        $middleware->alias([
            'onboarded' => EnsureHouseholdOnboarded::class,
            'admin' => EnsureSystemAdmin::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
