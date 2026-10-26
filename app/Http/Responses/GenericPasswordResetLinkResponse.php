<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use RuntimeException;

/**
 * Reports the same outcome for every failed reset-link attempt, so the public form
 * cannot be used to discover which emails have an account.
 */
final class GenericPasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse
{
    public function __construct(private readonly string $status)
    {
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     */
    public function toResponse($request): RedirectResponse
    {
        if (! in_array($this->status, [Password::INVALID_USER, Password::RESET_THROTTLED], true)) {
            report(new RuntimeException('Unexpected password reset link status: '.$this->status));
        }

        return back()
            ->withInput($request->only('email'))
            ->with('status', __('auth.reset_sent'));
    }
}
