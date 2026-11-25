<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Core\Services\TwoFactorAuthService;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;

final class RedirectIfTwoFactorEmailAuthenticatable
{
    public function __construct(
        protected StatefulGuard $guard,
        protected LoginRateLimiter $limiter,
        protected TwoFactorAuthService $twoFactorService
    ) {
    }

    /**
     * @param callable(Request): mixed $next
     * @return mixed
     */
    public function handle(Request $request, callable $next)
    {
        $user = $this->validateCredentials($request);

        if ($user !== null && $user->two_factor_email_enabled) {
            return $this->twoFactorChallengeResponse($request, $user);
        }

        return $next($request);
    }

    protected function validateCredentials(Request $request): ?User
    {
        if (Fortify::$authenticateUsingCallback) {
            $user = call_user_func(Fortify::$authenticateUsingCallback, $request);

            if (! $user) {
                $this->limiter->increment($request);

                throw ValidationException::withMessages([
                    Fortify::username() => [trans('auth.failed')],
                ]);
            }

            return $user;
        }

        return null;
    }

    protected function twoFactorChallengeResponse(Request $request, User $user)
    {
        $this->twoFactorService->generateAndSendOtp($user);

        $request->session()->put([
            'login.id' => $user->getKey(),
            'login.remember' => $request->boolean('remember'),
        ]);

        return $request->wantsJson()
            ? response()->json(['two_factor' => true])
            : redirect()->route('two-factor.challenge');
    }
}
