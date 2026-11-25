<?php

declare(strict_types=1);

namespace App\Modules\Finance\Http\Middleware;

use App\Core\Support\HouseholdContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureFinancePeriodIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        $household = app(HouseholdContext::class)->currentOrNull();

        if ($household !== null && ! $household->hasActiveFinancePeriod()) {
            return redirect()->route('finance.period.setup');
        }

        return $next($request);
    }
}
