<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class LighthouseReadinessTest extends TestCase
{
    public function test_pwa_manifest_meets_lighthouse_installability_criteria(): void
    {
        $manifestPath = public_path('manifest.json');
        $this->assertFileExists($manifestPath);

        $json = json_decode((string) File::get($manifestPath), true);
        $this->assertIsArray($json);

        // Required for PWA Installability badge
        $this->assertSame('Family Hub', $json['name']);
        $this->assertSame('FamilyHub', $json['short_name']);
        $this->assertSame('/', $json['start_url']);
        $this->assertSame('standalone', $json['display']);
        $this->assertSame('#f6f4ee', $json['background_color']);
        $this->assertSame('#28543e', $json['theme_color']);

        $icons = $json['icons'];
        $this->assertIsArray($icons);
        $sizes = array_column($icons, 'sizes');

        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);

        foreach ($icons as $icon) {
            $this->assertFileExists(public_path($icon['src']));
        }
    }

    public function test_service_worker_and_offline_page_meet_best_practices(): void
    {
        $swPath = public_path('sw.js');
        $offlinePath = public_path('offline.html');

        $this->assertFileExists($swPath);
        $this->assertFileExists($offlinePath);

        $swContent = (string) File::get($swPath);
        $this->assertStringContainsString('STATIC_CACHE', $swContent);
        $this->assertStringContainsString('/offline.html', $swContent);
        $this->assertStringContainsString('CLEAR_CACHE', $swContent);

        $offlineContent = (string) File::get($offlinePath);
        $this->assertStringContainsString('<!DOCTYPE html>', $offlineContent);
        $this->assertStringContainsString('lang="id"', $offlineContent);
        $this->assertStringContainsString('viewport', $offlineContent);
        $this->assertStringContainsString('prefers-color-scheme: dark', $offlineContent);
    }

    public function test_production_asset_build_is_optimized_and_below_budget(): void
    {
        $manifestPath = public_path('build/manifest.json');
        $this->assertFileExists($manifestPath);

        $manifest = json_decode((string) File::get($manifestPath), true);
        $this->assertIsArray($manifest);

        $this->assertArrayHasKey('resources/css/app.css', $manifest);
        $this->assertArrayHasKey('resources/js/app.js', $manifest);

        $cssFile = public_path('build/' . $manifest['resources/css/app.css']['file']);
        $jsFile = public_path('build/' . $manifest['resources/js/app.js']['file']);

        $this->assertFileExists($cssFile);
        $this->assertFileExists($jsFile);

        // Budget check: CSS < 350KB uncompressed, JS < 50KB
        $this->assertLessThan(350 * 1024, filesize($cssFile), 'Production CSS exceeds 350KB budget.');
        $this->assertLessThan(50 * 1024, filesize($jsFile), 'Production JS exceeds 50KB budget.');
    }
}
