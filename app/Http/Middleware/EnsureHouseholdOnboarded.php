<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureHouseholdOnboarded
{
    /**
     * Handle an incoming request.
     * Redirects household owners who haven't completed onboarding to the onboarding wizard.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->is_system_admin) {
            return $next($request);
        }

        $household = Household::query()
            ->whereHas('members', fn (Builder $query): Builder => $query->where('user_id', $user->getKey())->where('role', MemberRole::Owner->value))
            ->first();

        if ($household !== null && ! $household->isOnboarded()) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
