<?php

declare(strict_types=1);

namespace App\Core\Services;

use App\Core\Notifications\TwoFactorOtpNotification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class TwoFactorAuthService
{
    public const MAX_ATTEMPTS = 5;
    public const OTP_EXPIRY_MINUTES = 10;
    public const NUM_RECOVERY_CODES = 8;

    /**
     * Generates a 6-digit numeric OTP, saves hashed value and expiry, and sends via email notification.
     */
    public function generateAndSendOtp(User $user): string
    {
        $plainOtp = sprintf('%06d', random_int(100000, 999999));

        $user->forceFill([
            'two_factor_otp_code' => Hash::make($plainOtp),
            'two_factor_otp_expires_at' => CarbonImmutable::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'two_factor_attempts' => 0,
        ])->save();

        $user->notify(new TwoFactorOtpNotification($plainOtp));

        return $plainOtp;
    }

    /**
     * Verifies the provided OTP code against the user's stored hashed OTP.
     * Enforces expiration and max 5 attempts limit.
     */
    public function verifyOtp(User $user, string $inputOtp): bool
    {
        $inputOtp = trim($inputOtp);

        if ($user->two_factor_attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if ($user->two_factor_otp_expires_at === null || $user->two_factor_otp_expires_at->isPast()) {
            return false;
        }

        if (empty($user->two_factor_otp_code)) {
            return false;
        }

        if (Hash::check($inputOtp, $user->two_factor_otp_code)) {
            // Success: clear OTP state
            $user->forceFill([
                'two_factor_otp_code' => null,
                'two_factor_otp_expires_at' => null,
                'two_factor_attempts' => 0,
            ])->save();

            return true;
        }

        // Failure: increment attempts
        $user->forceFill([
            'two_factor_attempts' => $user->two_factor_attempts + 1,
        ])->save();

        return false;
    }

    /**
     * Verifies and consumes a single-use recovery code.
     */
    public function verifyRecoveryCode(User $user, string $inputCode): bool
    {
        $normalizedInput = trim(strtoupper($inputCode));
        $hashedCodes = $user->two_factor_recovery_codes;

        if (! is_array($hashedCodes) || empty($hashedCodes)) {
            return false;
        }

        foreach ($hashedCodes as $index => $hashedCode) {
            if (Hash::check($normalizedInput, $hashedCode)) {
                // Remove used code (single-use enforcement)
                unset($hashedCodes[$index]);

                $user->forceFill([
                    'two_factor_recovery_codes' => array_values($hashedCodes),
                    'two_factor_otp_code' => null,
                    'two_factor_otp_expires_at' => null,
                    'two_factor_attempts' => 0,
                ])->save();

                return true;
            }
        }

        return false;
    }

    /**
     * Generates a set of 8 recovery codes formatted as XXXXX-XXXXX.
     * Returns an array with 'plain' and 'hashed' lists.
     *
     * @return array{plain: array<int, string>, hashed: array<int, string>}
     */
    public function generateRecoveryCodes(): array
    {
        $plain = [];
        $hashed = [];

        for ($i = 0; $i < self::NUM_RECOVERY_CODES; $i++) {
            $code = sprintf(
                '%s-%s',
                Str::upper(Str::random(5)),
                Str::upper(Str::random(5))
            );
            $plain[] = $code;
            $hashed[] = Hash::make($code);
        }

        return [
            'plain' => $plain,
            'hashed' => $hashed,
        ];
    }

    /**
     * Enables 2FA for the user, generates and saves 8 hashed recovery codes,
     * and returns the plain recovery codes for one-time display.
     *
     * @return array<int, string> Plain recovery codes
     */
    public function enableTwoFactor(User $user): array
    {
        $codes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_email_enabled' => true,
            'two_factor_recovery_codes' => $codes['hashed'],
            'two_factor_otp_code' => null,
            'two_factor_otp_expires_at' => null,
            'two_factor_attempts' => 0,
        ])->save();

        return $codes['plain'];
    }

    /**
     * Disables 2FA and wipes all 2FA OTP and recovery code data.
     */
    public function disableTwoFactor(User $user): void
    {
        $user->forceFill([
            'two_factor_email_enabled' => false,
            'two_factor_recovery_codes' => null,
            'two_factor_otp_code' => null,
            'two_factor_otp_expires_at' => null,
            'two_factor_attempts' => 0,
        ])->save();
    }
}
