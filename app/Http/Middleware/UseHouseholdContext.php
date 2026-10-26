<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Models\Household;
use App\Core\Support\HouseholdContext;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class UseHouseholdContext
{
    public function __construct(private readonly HouseholdContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->forget();

        $user = $request->user();

        if ($user !== null) {
            $household = Household::query()
                ->whereHas('members', fn (Builder $query): Builder => $query->where('user_id', $user->getKey()))
                ->first();

            if ($household !== null) {
                $this->context->use($household);
            }
        }

        try {
            return $next($request);
        } finally {
            $this->context->forget();
        }
    }
}
