<?php

namespace Tests\Feature;

use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_application_uses_expected_core_configuration(): void
    {
        $this->assertSame('mysql', config('database.default'));
        $this->assertArrayHasKey('b2', config('filesystems.disks'));
        $this->assertSame('s3', config('filesystems.disks.b2.driver'));
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('2.0.0', config('app.version'));
        $this->assertArrayHasKey('database', config('cache.stores'));
    }

    public function test_vercel_deployment_configuration_matches_laravel_build(): void
    {
        $vercel = json_decode(file_get_contents(base_path('vercel.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('npm run build', $vercel['buildCommand']);
        $this->assertSame('public', $vercel['outputDirectory']);
        $this->assertSame('vercel-php@0.7.4', $vercel['functions']['api/*.php']['runtime']);
        $this->assertArrayNotHasKey('APP_URL', $vercel['env']);
        $this->assertArrayNotHasKey('ASSET_URL', $vercel['env']);
        $this->assertSame('true', $vercel['env']['SESSION_SECURE_COOKIE']);
        $this->assertSame('true', $vercel['env']['SESSION_ENCRYPT']);
        $this->assertSame('database', $vercel['env']['CACHE_DRIVER']);
        $this->assertSame(
            'public, max-age=31536000, immutable',
            $vercel['headers'][0]['headers'][0]['value']
        );
    }

    public function test_progressive_navigation_is_limited_to_script_free_pages(): void
    {
        $safeRoutes = config('ajax_navigation.safe_routes');
        $safeViews = config('ajax_navigation.safe_views');

        $this->assertCount(7, $safeRoutes);
        $this->assertCount(count($safeRoutes), $safeViews);

        foreach ($safeViews as $viewPath) {
            $contents = strtolower((string) file_get_contents(base_path($viewPath)));

            $this->assertStringNotContainsString('<script', $contents, $viewPath);
            $this->assertStringNotContainsString("@push('scripts'", $contents, $viewPath);
            $this->assertStringNotContainsString('@push("scripts"', $contents, $viewPath);
        }

        $navigationScript = (string) file_get_contents(resource_path('js/ajax-navigation.js'));
        $this->assertStringContainsString('currentPageIsSafe()', $navigationScript);
        $this->assertStringContainsString('normalNavigation(', $navigationScript);
        $this->assertStringContainsString("method: 'GET'", $navigationScript);
    }
}
