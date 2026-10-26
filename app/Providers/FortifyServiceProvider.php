<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\GenericPasswordResetLinkResponse;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::loginView(fn () => view('auth.login'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        $this->app->singleton(FailedPasswordResetLinkRequestResponse::class, GenericPasswordResetLinkResponse::class);

        ResetPassword::toMailUsing(function (mixed $notifiable, string $token): MailMessage {
            $email = $notifiable instanceof User ? (string) $notifiable->getEmailForPasswordReset() : '';

            return (new MailMessage())
                ->subject(__('auth.reset_mail_subject'))
                ->line(__('auth.reset_mail_intro'))
                ->action(__('auth.reset_mail_action'), route('password.reset', [
                    'token' => $token,
                    'email' => $email,
                ]))
                ->line(__('auth.reset_mail_expiry', ['count' => config('auth.passwords.users.expire')]));
        });

        RateLimiter::for('login', function (Request $request): Limit {
            $email = $request->input('email');

            return Limit::perMinute(5)
                ->by(Str::lower(is_string($email) ? $email : '').'|'.$request->ip());
        });
    }
}
