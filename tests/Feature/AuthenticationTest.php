<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('id');
});

it('renders the localized login with Livewire and no registration link', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertSeeLivewire('auth.login-form')
        ->assertSee('Akun baru hanya tersedia melalui undangan.')
        ->assertDontSee('name="password_confirmation"', false);
});

it('authenticates an existing user and logs them out', function (): void {
    $user = User::create([
        'name' => 'Foundation User',
        'email' => 'foundation@example.test',
        'password' => 'test-password',
    ]);

    $this->withSession(['auth-test-marker' => true]);
    $originalSessionId = session()->getId();

    $this->post('/login', [
        'email' => 'FOUNDATION@example.test',
        'password' => 'test-password',
    ])->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($originalSessionId);

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
    $this->assertNull(session('auth-test-marker'));
});

it('rejects invalid credentials without authenticating', function (): void {
    $this->post('/login', [
        'email' => 'unknown@example.test',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai.']);
    $this->assertGuest();
});

it('validates missing credentials in Indonesian', function (): void {
    $this->post('/login', [])->assertSessionHasErrors([
        'email' => 'Alamat email wajib diisi.',
        'password' => 'Kata sandi wajib diisi.',
    ]);
});

it('displays Fortify errors and retains email after a failed login', function (): void {
    $this->followingRedirects()->from('/login')->post('/login', [
        'email' => 'retry@example.test',
        'password' => 'wrong-password',
    ])->assertOk()
        ->assertSee('Email atau kata sandi tidak sesuai.')
        ->assertSee('value="retry@example.test"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertDontSee('value="wrong-password"', false);
});

it('limits login attempts to five per minute per normalized email and IP', function (): void {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/login', [
            'email' => 'limited@example.test',
            'password' => 'wrong-password',
        ])->assertRedirect();
    }

    $this->post('/login', [
        'email' => 'LIMITED@example.test',
        'password' => 'wrong-password',
    ])->assertTooManyRequests();

    $this->post('/login', [
        'email' => 'other@example.test',
        'password' => 'wrong-password',
    ])->assertRedirect();
});

it('keeps registration, passkeys, and TOTP disabled', function (): void {
    expect(config('fortify.features'))->toBe([]);

    foreach (['register', 'passkeys/login', 'two-factor-challenge'] as $path) {
        $this->get('/'.$path)->assertNotFound();
        $this->post('/'.$path, [])->assertNotFound();
    }
});

it('redirects authenticated users away from login', function (): void {
    $user = User::create([
        'name' => 'Foundation User',
        'email' => 'authenticated@example.test',
        'password' => 'test-password',
    ]);

    $this->actingAs($user)->get('/login')->assertRedirect('/');
});

it('renders and submits password confirmation for an authenticated user', function (): void {
    $user = User::create([
        'name' => 'Foundation User',
        'email' => 'confirmation@example.test',
        'password' => 'test-password',
    ]);

    $this->actingAs($user)->get('/user/confirm-password')->assertOk();
    $this->post('/user/confirm-password', [
        'password' => 'wrong-password',
    ])->assertSessionHasErrors(['password' => 'Kata sandi yang diberikan tidak sesuai.']);
    $this->assertNull(session('auth.password_confirmed_at'));

    $this->post('/user/confirm-password', [
        'password' => 'test-password',
    ])->assertRedirect();
    $this->assertNotNull(session('auth.password_confirmed_at'));
});

it('redirects guests to login before password confirmation', function (): void {
    $this->get('/user/confirm-password')->assertRedirect('/login');
    $this->post('/user/confirm-password', ['password' => 'test-password'])->assertRedirect('/login');
});

it('rejects structured credentials without an exception', function (): void {
    $this->post('/login', ['email' => ['invalid'], 'password' => ['invalid']])
        ->assertSessionHasErrors(['email', 'password']);
    $this->assertGuest();
});

it('does not register password reset or profile feature routes yet', function (): void {
    expect(Route::has('password.request'))->toBeFalse()
        ->and(Route::has('user-profile-information.update'))->toBeFalse();
});
