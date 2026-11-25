<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Core\Services\TwoFactorAuthService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TwoFactorAuthenticatedSessionController extends Controller
{
    public function __construct(
        protected TwoFactorAuthService $twoFactorService
    ) {
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('login.id');

        if (! $userId) {
            return redirect()->route('login');
        }

        /** @var User|null $user */
        $user = User::find($userId);

        if ($user === null || ! $user->two_factor_email_enabled) {
            $request->session()->forget(['login.id', 'login.remember']);

            return redirect()->route('login');
        }

        $code = (string) $request->input('code', '');
        $recoveryCode = (string) $request->input('recovery_code', '');

        if (! empty($recoveryCode)) {
            $valid = $this->twoFactorService->verifyRecoveryCode($user, $recoveryCode);

            if (! $valid) {
                throw ValidationException::withMessages([
                    'recovery_code' => [__('Kode pemulihan tidak valid atau sudah pernah digunakan.')],
                ]);
            }
        } elseif (! empty($code)) {
            if ($user->two_factor_attempts >= TwoFactorAuthService::MAX_ATTEMPTS) {
                throw ValidationException::withMessages([
                    'code' => [__('Batas 5 kali percobaan terlampaui. Silakan klik kirim ulang kode baru.')],
                ]);
            }

            if ($user->two_factor_otp_expires_at === null || $user->two_factor_otp_expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'code' => [__('Kode OTP telah kadaluarsa (berlaku 10 menit). Silakan klik kirim ulang kode.')],
                ]);
            }

            $valid = $this->twoFactorService->verifyOtp($user, $code);

            if (! $valid) {
                $remaining = max(0, TwoFactorAuthService::MAX_ATTEMPTS - (int) $user->fresh()->two_factor_attempts);

                throw ValidationException::withMessages([
                    'code' => [__("Kode verifikasi OTP salah. Sisa kesempatan: {$remaining}.")],
                ]);
            }
        } else {
            throw ValidationException::withMessages([
                'code' => [__('Masukkan kode verifikasi OTP 6 digit atau kode pemulihan.')],
            ]);
        }

        // Authentication succeeded
        $remember = (bool) $request->session()->pull('login.remember', false);
        $request->session()->forget('login.id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home', '/'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('login.id');

        if (! $userId) {
            return redirect()->route('login');
        }

        /** @var User|null $user */
        $user = User::find($userId);

        if ($user === null || ! $user->two_factor_email_enabled) {
            return redirect()->route('login');
        }

        $this->twoFactorService->generateAndSendOtp($user);

        return back()->with('status', __('Kode verifikasi baru telah dikirimkan ke alamat email Anda.'));
    }
}
