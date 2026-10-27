<?php

declare(strict_types=1);

use App\Core\Actions\CreateHousehold;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('provisions a new system admin user via artisan command', function (): void {
    $this->artisan('core:create-admin', [
        '--name' => 'Super Admin',
        '--email' => 'admin@example.test',
        '--password' => 'secret-password-123',
    ])
        ->expectsOutput('System administrator [admin@example.test] created successfully.')
        ->assertSuccessful();

    $user = User::query()->where('email', 'admin@example.test')->sole();

    expect($user->name)->toBe('Super Admin')
        ->and($user->is_system_admin)->toBeTrue()
        ->and(Hash::check('secret-password-123', $user->password))->toBeTrue();
});

it('promotes an existing user without a household to system admin', function (): void {
    $user = User::create([
        'name' => 'Existing User',
        'email' => 'existing@example.test',
        'password' => 'initial-password',
        'is_system_admin' => false,
    ]);

    $this->artisan('core:create-admin', [
        '--name' => 'Existing User',
        '--email' => 'existing@example.test',
        '--password' => 'irrelevant-password',
    ])
        ->expectsOutput('User [existing@example.test] is now a system administrator.')
        ->assertSuccessful();

    expect($user->fresh()->is_system_admin)->toBeTrue();
});

it('refuses to promote a user who already belongs to a household', function (): void {
    $owner = User::create([
        'name' => 'Owner',
        'email' => 'owner@example.test',
        'password' => 'password',
    ]);
    app(CreateHousehold::class)->handle($owner, 'Keluarga Bahagia');

    $this->artisan('core:create-admin', [
        '--name' => 'Owner',
        '--email' => 'owner@example.test',
        '--password' => 'password',
    ])
        ->expectsOutput(__('household.admin_not_member'))
        ->assertFailed();

    expect($owner->fresh()->is_system_admin)->toBeFalse();
});

it('rejects invalid inputs when creating system admin', function (): void {
    $this->artisan('core:create-admin', [
        '--name' => '',
        '--email' => 'not-an-email',
        '--password' => 'short',
    ])->assertFailed();

    expect(User::query()->count())->toBe(0);
});
