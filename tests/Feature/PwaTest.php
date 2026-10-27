<?php

declare(strict_types=1);

use App\Core\Enums\MemberRole;
use App\Core\Models\Household;
use App\Core\Models\HouseholdMember;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::create([
        'name' => 'Dani Pratama',
        'email' => 'dani@familyhub.test',
        'password' => 'password123',
    ]);

    $this->household = Household::create([
        'name' => 'Keluarga Dani',
        'timezone' => 'Asia/Jakarta',
        'finance_period_start_day' => 1,
        'created_by' => $this->user->id,
        'onboarded_at' => now(),
    ]);

    $member = new HouseholdMember([
        'role' => MemberRole::Owner,
        'color' => MemberRole::Owner->color(),
        'slot' => 1,
        'joined_at' => now(),
    ]);
    $member->user()->associate($this->user);
    $this->household->members()->save($member);

    app(HouseholdContext::class)->use($this->household);
});

it('verifies manifest.json exists and contains correct PWA configuration', function (): void {
    $manifestPath = public_path('manifest.json');
    expect(file_exists($manifestPath))->toBeTrue();

    $content = json_decode((string) file_get_contents($manifestPath), true);
    expect($content['name'])->toBe('Family Hub')
        ->and($content['short_name'])->toBe('FamilyHub')
        ->and($content['display'])->toBe('standalone')
        ->and($content['theme_color'])->toBe('#28543e')
        ->and($content['background_color'])->toBe('#f6f4ee')
        ->and($content['icons'])->toHaveCount(2);
});

it('verifies sw.js exists with cache isolation and push event handlers', function (): void {
    $swPath = public_path('sw.js');
    expect(file_exists($swPath))->toBeTrue();

    $content = (string) file_get_contents($swPath);
    expect($content)->toContain('fh-static-v1')
        ->and($content)->toContain('fh-dynamic-v1')
        ->and($content)->toContain('CLEAR_CACHE')
        ->and($content)->toContain('/offline.html')
        ->and($content)->toContain("addEventListener('push'");
});

it('verifies offline.html exists and provides friendly offline screen', function (): void {
    $offlinePath = public_path('offline.html');
    expect(file_exists($offlinePath))->toBeTrue();

    $content = (string) file_get_contents($offlinePath);
    expect($content)->toContain('Sedang Offline')
        ->and($content)->toContain('#28543e');
});

it('renders PWA manifest and theme meta tags on authenticated and guest layouts', function (): void {
    // Guest welcome page
    $guestResponse = $this->get('/');
    $guestResponse->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.json">', false)
        ->assertSee('<meta name="theme-color" content="#28543e">', false)
        ->assertSee('<meta name="apple-mobile-web-app-capable" content="yes">', false);

    // Authenticated layout
    $authResponse = $this->actingAs($this->user)->get('/');
    $authResponse->assertOk()
        ->assertSee('<link rel="manifest" href="/manifest.json">', false)
        ->assertSee('<meta name="theme-color" content="#28543e">', false);
});

it('renders the offline banner component in the authenticated layout', function (): void {
    $response = $this->actingAs($this->user)->get('/');

    $response->assertOk()
        ->assertSee('Mode Offline: Anda sedang tidak terhubung ke internet.');
});
