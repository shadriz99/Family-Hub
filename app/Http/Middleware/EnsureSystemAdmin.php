<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSystemAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->is_system_admin) {
            abort(Response::HTTP_FORBIDDEN, __('Aksi ini memerlukan izin administrator sistem.'));
        }

        return $next($request);
    }
}
