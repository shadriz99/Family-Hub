<?php

declare(strict_types=1);

use App\Core\Notifications\TwoFactorOtpNotification;
use App\Core\Services\TwoFactorAuthService;
use App\Livewire\Settings\ProfileSettings;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();

    $this->user = User::create([
        'name' => 'Siti Nurhaliza',
        'email' => 'siti@familyhub.test',
        'password' => 'secret123',
    ]);
});

test('user can enable 2FA with current password confirmation and receives 8 recovery codes', function (): void {
    Livewire::actingAs($this->user)
        ->test(ProfileSettings::class)
        ->set('twoFactorPasswordConfirm', 'wrong-password')
        ->call('enableTwoFactor')
        ->assertHasErrors(['twoFactorPasswordConfirm']);

    expect($this->user->fresh()->two_factor_email_enabled)->toBeFalse();

    Livewire::actingAs($this->user)
        ->test(ProfileSettings::class)
        ->set('twoFactorPasswordConfirm', 'secret123')
        ->call('enableTwoFactor')
        ->assertHasNoErrors()
        ->assertSet('twoFactorEnabled', true)
        ->assertSet('showRecoveryCodesModal', true)
        ->assertCount('plainRecoveryCodes', 8);

    $user = $this->user->fresh();
    expect($user->two_factor_email_enabled)->toBeTrue()
        ->and($user->two_factor_recovery_codes)->toHaveCount(8);
});

test('user can disable 2FA with current password confirmation', function (): void {
    $service = app(TwoFactorAuthService::class);
    $service->enableTwoFactor($this->user);

    expect($this->user->fresh()->two_factor_email_enabled)->toBeTrue();

    Livewire::actingAs($this->user)
        ->test(ProfileSettings::class)
        ->set('twoFactorPasswordConfirm', 'secret123')
        ->call('disableTwoFactor')
        ->assertHasNoErrors()
        ->assertSet('twoFactorEnabled', false);

    $user = $this->user->fresh();
    expect($user->two_factor_email_enabled)->toBeFalse()
        ->and($user->two_factor_recovery_codes)->toBeNull();
});

test('login redirects to two-factor-challenge and sends OTP email when 2FA is enabled', function (): void {
    $service = app(TwoFactorAuthService::class);
    $service->enableTwoFactor($this->user);

    $response = $this->post('/login', [
        'email' => 'siti@familyhub.test',
        'password' => 'secret123',
    ]);

    $response->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();

    Notification::assertSentTo($this->user, TwoFactorOtpNotification::class, function ($notification) {
        expect(strlen($notification->otpCode))->toBe(6);
        return true;
    });

    $user = $this->user->fresh();
    expect($user->two_factor_otp_code)->not->toBeNull()
        ->and($user->two_factor_otp_expires_at)->not->toBeNull();
});

test('submitting valid OTP logs user in successfully', function (): void {
    $service = app(TwoFactorAuthService::class);
    $service->enableTwoFactor($this->user);
    $plainOtp = $service->generateAndSendOtp($this->user);

    $this->withSession(['login.id' => $this->user->id])
        ->post(route('two-factor.login'), [
            'code' => $plainOtp,
        ])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($this->user);
    $user = $this->user->fresh();
    expect($user->two_factor_otp_code)->toBeNull()
        ->and($user->two_factor_attempts)->toBe(0);
});

test('submitting invalid OTP increments attempts and rejects after 5 failures', function (): void {
    $service = app(TwoFactorAuthService::class);
    $service->enableTwoFactor($this->user);
    $service->generateAndSendOtp($this->user);

    // 4 failed attempts
    for ($i = 1; $i <= 4; $i++) {
        $this->withSession(['login.id' => $this->user->id])
            ->post(route('two-factor.login'), ['code' => '000000'])
            ->assertSessionHasErrors('code');
        expect($this->user->fresh()->two_factor_attempts)->toBe($i);
    }

    // 5th attempt
    $this->withSession(['login.id' => $this->user->id])
        ->post(route('two-factor.login'), ['code' => '000000'])
        ->assertSessionHasErrors('code');
    expect($this->user->fresh()->two_factor_attempts)->toBe(5);

    // 6th attempt is blocked because max attempts (5) reached
    $this->withSession(['login.id' => $this->user->id])
        ->post(route('two-factor.login'), ['code' => '000000'])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('submitting expired OTP is rejected', function (): void {
    $service = app(TwoFactorAuthService::class);
    $service->enableTwoFactor($this->user);
    $plainOtp = $service->generateAndSendOtp($this->user);

    // Travel 11 minutes into future (>10 min expiry)
    $this->travel(11)->minutes();

    $this->withSession(['login.id' => $this->user->id])
        ->post(route('two-factor.login'), ['code' => $plainOtp])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

test('submitting valid recovery code logs in and consumes the code single-use', function (): void {
    $service = app(TwoFactorAuthService::class);
    $plainCodes = $service->enableTwoFactor($this->user);
    $testRecoveryCode = $plainCodes[0];

    expect($this->user->fresh()->two_factor_recovery_codes)->toHaveCount(8);

    $this->withSession(['login.id' => $this->user->id])
        ->post(route('two-factor.login'), [
            'recovery_code' => $testRecoveryCode,
        ])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($this->user);

    // Recovery code must be consumed (7 left)
    $remaining = $this->user->fresh()->two_factor_recovery_codes;
    expect($remaining)->toHaveCount(7);

    // Using the same recovery code again fails
    auth()->logout();
    $this->withSession(['login.id' => $this->user->id])
        ->post(route('two-factor.login'), [
            'recovery_code' => $testRecoveryCode,
        ])
        ->assertSessionHasErrors('recovery_code');

    $this->assertGuest();
});
