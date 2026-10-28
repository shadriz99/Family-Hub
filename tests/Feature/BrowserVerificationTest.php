<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Actions\CreateHousehold;
use App\Core\Support\HouseholdContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class BrowserVerificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Browser Tester',
            'email' => 'browser@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->household = app(CreateHousehold::class)->handle(
            $this->user,
            'Keluarga Browser',
            1,
            'Asia/Jakarta'
        );
        $this->household->forceFill(['onboarded_at' => now()])->save();
        app(HouseholdContext::class)->use($this->household);
    }

    public function test_welcome_page_has_responsive_meta_skip_link_and_pwa_tags(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">', false);
        $response->assertSee('<meta name="theme-color" content="#28543e">', false);
        $response->assertSee('<link rel="manifest" href="/manifest.json">', false);
        $response->assertSee('href="#main-content"', false);
        $response->assertSee('id="main-content"', false);
        $response->assertSee('Lewati ke konten utama');
    }

    public function test_login_and_auth_pages_have_accessible_forms_and_labels(): void
    {
        $loginResponse = $this->get(route('login'));
        $loginResponse->assertOk();
        $loginResponse->assertSee('<meta name="viewport"', false);
        $loginResponse->assertSee('id="email"', false);
        $loginResponse->assertSee('for="email"', false);
        $loginResponse->assertSee('id="password"', false);
        $loginResponse->assertSee('for="password"', false);
        $loginResponse->assertSee('type="submit"', false);

        $forgotResponse = $this->get(route('password.request'));
        $forgotResponse->assertOk();
        $forgotResponse->assertSee('id="forgot-email"', false);
        $forgotResponse->assertSee('for="forgot-email"', false);
    }

    public function test_authenticated_shell_includes_skip_link_offline_banner_and_bottom_nav(): void
    {
        $response = $this->actingAs($this->user)->get('/');
        $response->assertOk();

        // Skip link
        $response->assertSee('href="#main-content"', false);
        $response->assertSee('id="main-content"', false);

        // Offline banner
        $response->assertSee('x-show="$store.network?.isOffline"', false);
        $response->assertSee('Mode Offline');

        // Brand logo
        $response->assertSee('Family Hub');

        // Mobile bottom nav items
        $response->assertSee('aria-label="Navigasi Mobile"', false);
        $response->assertSee('Beranda');
        $response->assertSee('Keuangan');
        $response->assertSee('Kalender');
        $response->assertSee('Lainnya');
    }

    public function test_css_contains_reduced_motion_and_overflow_guards(): void
    {
        $cssContent = File::get(resource_path('css/app.css'));

        $this->assertStringContainsString('overflow-x: hidden', $cssContent);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $cssContent);
        $this->assertStringContainsString('animation-duration: 0.01ms', $cssContent);
        $this->assertStringContainsString('@custom-variant dark', $cssContent);
    }

    public function test_unauthenticated_protected_routes_redirect_to_login(): void
    {
        $routes = [
            '/finance',
            '/tasks',
            '/notes',
            '/shopping',
            '/calendar',
            '/assets',
        ];

        foreach ($routes as $uri) {
            $response = $this->get($uri);
            $response->assertRedirect(route('login'), "Route {$uri} must redirect to login for guests.");
        }
    }

    public function test_authenticated_user_can_access_all_module_shells(): void
    {
        $routes = [
            '/',
            '/finance',
            '/tasks',
            '/notes',
            '/shopping',
            '/calendar',
            '/assets',
        ];

        foreach ($routes as $uri) {
            $response = $this->actingAs($this->user)->get($uri);
            $response->assertOk("Route {$uri} must render 200 OK for authenticated user.");
            $response->assertSee('Family Hub', false, "Route {$uri} must render the brand name.");
            $response->assertSee('id="main-content"', false, "Route {$uri} must include main-content anchor.");
        }
    }
}
