<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('id');

    $this->user = User::create([
        'name' => 'Reset User',
        'email' => 'reset@example.test',
        'password' => 'old-password',
    ]);
});

it('renders the localized forgot password page', function (): void {
    $this->get('/forgot-password')
        ->assertOk()
        ->assertSee('Lupa kata sandi')
        ->assertSee('name="email"', false)
        ->assertSee('Kirim tautan');
});

it('sends a localized reset link to a registered email', function (): void {
    Notification::fake();

    $this->from('/forgot-password')->post('/forgot-password', ['email' => 'RESET@example.test'])
        ->assertRedirect('/forgot-password')
        ->assertSessionHas('status', __('auth.reset_sent'));

    Notification::assertSentTo($this->user, ResetPassword::class, function (ResetPassword $notification): bool {
        $mail = $notification->toMail($this->user);

        return $mail->subject === __('auth.reset_mail_subject')
            && in_array(__('auth.reset_mail_intro'), $mail->introLines, true)
            && str_contains((string) $mail->actionUrl, '/reset-password/');
    });
});

it('gives the same answer for an unknown email without sending anything', function (): void {
    Notification::fake();

    $this->from('/forgot-password')->post('/forgot-password', ['email' => 'unknown@example.test'])
        ->assertRedirect('/forgot-password')
        ->assertSessionHas('status', __('auth.reset_sent'))
        ->assertSessionHasNoErrors();

    expect(session('_old_input.email'))->toBe('unknown@example.test');
    Notification::assertNothingSent();
});

it('renders the reset page with the token and email prefilled', function (): void {
    $token = Password::broker()->createToken($this->user);

    $this->get(route('password.reset', ['token' => $token, 'email' => $this->user->email]))
        ->assertOk()
        ->assertSee('Buat kata sandi baru')
        ->assertSee('name="token"', false)
        ->assertSee('value="'.$token.'"', false)
        ->assertSee('value="'.$this->user->email.'"', false);
});

it('resets the password and consumes the token', function (): void {
    $token = Password::broker()->createToken($this->user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $this->user->email,
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])
        ->assertRedirect('/login')
        ->assertSessionHas('status', __('passwords.reset'));

    expect(Hash::check('brand-new-password', (string) $this->user->fresh()?->password))->toBeTrue()
        ->and(Hash::check('old-password', (string) $this->user->fresh()?->password))->toBeFalse();

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $this->user->email,
        'password' => 'another-password',
        'password_confirmation' => 'another-password',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('brand-new-password', (string) $this->user->fresh()?->password))->toBeTrue();
});

it('rejects an invalid token without changing the password', function (): void {
    $this->from('/reset-password')->post('/reset-password', [
        'token' => str_repeat('a', 64),
        'email' => $this->user->email,
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('old-password', (string) $this->user->fresh()?->password))->toBeTrue();
});

it('requires a matching password confirmation', function (): void {
    $token = Password::broker()->createToken($this->user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $this->user->email,
        'password' => 'brand-new-password',
        'password_confirmation' => 'different-password',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('old-password', (string) $this->user->fresh()?->password))->toBeTrue();
});
