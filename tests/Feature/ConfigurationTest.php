<?php

namespace Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_application_uses_expected_core_configuration(): void
    {
        $this->assertSame('mysql', config('database.default'));
        $this->assertArrayHasKey('b2', config('filesystems.disks'));
        $this->assertSame('s3', config('filesystems.disks.b2.driver'));
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('2.7.7', config('app.version'));
        $this->assertArrayHasKey('database', config('cache.stores'));
        $this->assertArrayHasKey('redis', config('cache.stores'));
        $this->assertSame(['redis', 'database'], config('cache.stores.redis_failover.stores'));
        $this->assertSame('predis', config('database.redis.client'));
    }

    public function test_vercel_deployment_configuration_matches_laravel_build(): void
    {
        $vercel = json_decode(file_get_contents(base_path('vercel.json')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('npm run build', $vercel['buildCommand']);
        $this->assertSame('public', $vercel['outputDirectory']);
        $this->assertSame('vercel-php@0.9.0', $vercel['functions']['api/*.php']['runtime']);
        $this->assertSame(30, $vercel['functions']['api/*.php']['maxDuration']);
        $this->assertArrayNotHasKey('APP_URL', $vercel['env']);
        $this->assertArrayNotHasKey('ASSET_URL', $vercel['env']);
        $this->assertSame('2.7.7', $vercel['env']['APP_VERSION']);
        $this->assertSame('true', $vercel['env']['SESSION_SECURE_COOKIE']);
        $this->assertSame('true', $vercel['env']['SESSION_ENCRYPT']);
        $this->assertSame('database', $vercel['env']['CACHE_DRIVER']);
        $this->assertSame([
            ['path' => '/cron/reminder', 'schedule' => '0 23 * * *'],
        ], $vercel['crons']);
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

    public function test_ci_runs_secret_and_dependency_security_checks(): void
    {
        $workflow = (string) file_get_contents(base_path('.github/workflows/security.yml'));
        $phpunit = (string) file_get_contents(base_path('phpunit.xml'));

        $this->assertStringContainsString('gitleaks/gitleaks-action@v2', $workflow);
        $this->assertStringContainsString('composer audit --locked', $workflow);
        $this->assertStringContainsString('mkdir -p bootstrap/cache storage/framework/cache/data', $workflow);
        $this->assertStringContainsString('composer install --no-interaction --prefer-dist --no-progress', $workflow);
        $this->assertStringContainsString('php artisan test', $workflow);
        $this->assertStringContainsString('npm ci', $workflow);
        $this->assertStringContainsString('npm audit', $workflow);
        $this->assertStringContainsString('npm run build', $workflow);
        $this->assertStringContainsString('pull_request:', $workflow);
        $this->assertStringContainsString('schedule:', $workflow);
        $this->assertStringContainsString('<env name="APP_KEY" value="base64:', $phpunit);
        $this->assertTrue(
            strpos($workflow, 'npm run build') < strpos($workflow, 'php artisan test'),
            'Aset frontend harus dibangun sebelum tes Laravel yang merender view.'
        );
    }

    public function test_dashboard_does_not_trigger_daily_maintenance(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($route) => $route->getName() === 'dashboard');

        $this->assertNotNull($route);
        $this->assertNotContains('dailytasks', $route->gatherMiddleware());
        $this->assertFileDoesNotExist(app_path('Http/Middleware/DailyTasksMiddleware.php'));
    }

    public function test_observability_covers_requests_queries_queue_and_telegram(): void
    {
        $requestMetrics = (string) file_get_contents(app_path('Http/Middleware/RequestMetrics.php'));
        $provider = (string) file_get_contents(app_path('Providers/AppServiceProvider.php'));
        $telegram = (string) file_get_contents(app_path('Jobs/SendTelegramJob.php'));

        $this->assertStringContainsString("'duration_ms'", $requestMetrics);
        $this->assertStringContainsString("'query_duration_ms'", $requestMetrics);
        $this->assertStringContainsString('database.slow_query', $provider);
        $this->assertStringContainsString('queue.failed', $provider);
        $this->assertStringContainsString('telegram.permanently_failed', $telegram);
    }

    public function test_report_views_use_valid_pdf_styles_and_bundled_charts(): void
    {
        $rekapPdf = (string) file_get_contents(resource_path('views/laporan/rekap/pdf.blade.php'));
        $trendView = (string) file_get_contents(resource_path('views/laporan/tren/index.blade.php'));
        $trendPanels = (string) file_get_contents(resource_path('views/components/trend-chart-panels.blade.php'));
        $chartScript = (string) file_get_contents(resource_path('js/dashboard-charts.js'));

        $this->assertStringNotContainsString('.signature {', $rekapPdf);
        $this->assertStringNotContainsString('replace_file_content', $rekapPdf);
        $this->assertSame(1, substr_count($trendView, "@section('content')"));
        $this->assertStringNotContainsString('cdn.jsdelivr.net/npm/chart.js', $trendView);
        $this->assertStringContainsString('data-report-trend-chart', $trendPanels);
        $this->assertStringContainsString("document.querySelectorAll('[data-report-trend-chart]')", $chartScript);
        $this->assertStringContainsString("import('chart.js/auto')", $chartScript);

        $pdfOutput = Pdf::loadView('laporan.rekap.pdf', [
            'data' => collect(),
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'user' => (object) [
                'role' => 'Dinas',
                'nama_pengguna' => 'Petugas Verifikasi',
                'username' => 'verifikator',
            ],
            'printIdentity' => [
                'printed_at' => now(),
                'printer' => [
                    'nama' => 'Petugas Verifikasi',
                    'username' => 'verifikator',
                    'role' => 'Dinas',
                ],
                'supervisor' => [
                    'jabatan' => null,
                    'nama' => null,
                    'nip' => null,
                ],
            ],
        ])->setPaper('a4', 'landscape')->output();

        $this->assertStringStartsWith('%PDF-', $pdfOutput);

        $renderedTrend = view('laporan.tren.index', [
            'chartData' => [
                'labels' => ['Januari'],
                'masuk' => ['dalam' => [1], 'luar_kota' => [2], 'luar_negeri' => [0]],
                'keluar' => ['dalam' => [3], 'luar_kota' => [1], 'luar_negeri' => [0]],
            ],
            'year' => 2026,
            'month' => null,
        ])->render();

        $this->assertSame(2, substr_count($renderedTrend, 'data-report-trend-chart'));
        $this->assertStringContainsString('&quot;Dalam Kota (Bogor)&quot;', $renderedTrend);
    }

    public function test_frontend_entrypoints_are_scoped_to_each_page_type(): void
    {
        $viteConfig = (string) file_get_contents(base_path('vite.config.js'));
        $appScript = (string) file_get_contents(resource_path('js/app.js'));
        $loginScript = (string) file_get_contents(resource_path('js/login.js'));
        $publicScript = (string) file_get_contents(resource_path('js/public.js'));
        $loginView = (string) file_get_contents(resource_path('views/auth/login.blade.php'));
        $publicLayout = (string) file_get_contents(resource_path('views/layouts/public.blade.php'));

        foreach (['app.js', 'login.js', 'public.js'] as $entrypoint) {
            $this->assertStringContainsString("resources/js/{$entrypoint}", $viteConfig);
        }

        $this->assertStringContainsString('resources/js/login.js', $loginView);
        $this->assertStringNotContainsString('resources/js/app.js', $loginView);
        $this->assertStringContainsString('resources/js/public.js', $publicLayout);
        $this->assertStringNotContainsString('resources/js/app.js', $publicLayout);

        $this->assertStringContainsString('./autosave.js', $appScript);
        $this->assertStringNotContainsString('./autosave.js', $loginScript);
        $this->assertStringNotContainsString('./autosave.js', $publicScript);
        $this->assertStringNotContainsString('alpine-components', $loginScript);
        $this->assertStringContainsString('./dashboard-charts.js', $publicScript);
        $this->assertStringContainsString('./public-statistics-filters.js', $publicScript);
        $this->assertStringNotContainsString('./public-statistics-filters.js', $appScript);
    }
}
