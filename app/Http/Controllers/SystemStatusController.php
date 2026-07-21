<?php

namespace App\Http\Controllers;

use App\Services\SystemResourceUsageService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemStatusController extends Controller
{
    public function index(SystemResourceUsageService $usageService)
    {
        abort_unless(in_array(Auth::user()->role, ['SuperAdmin', 'Dinas'], true), 403);

        $cacheSeconds = max(30, (int) config('system_status.cache_seconds'));
        $dbStats = Cache::remember(
            'system_db_stats.v2',
            now()->addSeconds($cacheSeconds),
            fn () => $usageService->database()
        );
        $b2Stats = Cache::remember(
            'system_b2_stats.v2',
            now()->addSeconds($cacheSeconds),
            fn () => $usageService->backblaze()
        );

        $serverInfo = [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'environment' => app()->environment(),
            'commit' => env('VERCEL_GIT_COMMIT_SHA', 'Unknown'),
            'region' => env('VERCEL_REGION', 'Unknown'),
            'url' => env('APP_URL'),
            'db_host' => config('database.connections.mysql.host'),
            'db_version' => $this->databaseVersion(),
        ];

        return view('admin.system.status', compact('dbStats', 'b2Stats', 'serverInfo'));
    }

    public function refresh()
    {
        Cache::forget('system_db_size');
        Cache::forget('system_b2_stats');
        Cache::forget('system_db_stats.v2');
        Cache::forget('system_b2_stats.v2');

        return redirect()->route('system.status')->with('success', 'Status berhasil diperbarui dari layanan cloud.');
    }

    private function databaseVersion(): string
    {
        try {
            return DB::select('select version() as version')[0]->version ?? 'Unknown';
        } catch (Throwable) {
            return 'Unknown';
        }
    }
}
