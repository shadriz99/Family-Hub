<?php

declare(strict_types=1);

use App\Core\Actions\CreateHousehold;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Route::middleware('web')->get('/context-probe', function (): string {
        try {
            return currentHousehold()->getKey();
        } catch (LogicException) {
            return 'none';
        }
    });
});

it('activates the household of an authenticated member and forgets it after the response', function (): void {
    $user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password']);
    $household = app(CreateHousehold::class)->handle($user, 'Keluarga Uji');

    $this->actingAs($user)->get('/context-probe')->assertSee($household->getKey());

    expect(fn () => currentHousehold())->toThrow(LogicException::class);
});

it('leaves the context unset for an authenticated user without a household', function (): void {
    $user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password']);

    $this->actingAs($user)->get('/context-probe')->assertSee('none');
});

it('leaves the context unset for guests', function (): void {
    $this->get('/context-probe')->assertSee('none');
});

it('ignores a soft-deleted household even when the membership remains', function (): void {
    $user = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'test-password']);
    $household = app(CreateHousehold::class)->handle($user, 'Keluarga Uji');
    $household->delete();

    $this->actingAs($user)->get('/context-probe')->assertSee('none');
});
