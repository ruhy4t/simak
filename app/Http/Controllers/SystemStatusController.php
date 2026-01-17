<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SystemStatusController extends Controller
{
    public function index()
    {
        // Only SuperAdmin or Dinas should see this
        if (!in_array(Auth::user()->role, ['SuperAdmin', 'Dinas'])) {
            abort(403);
        }

        // 1. Database Size
        $dbSize = Cache::remember('system_db_size', 300, function () {
            try {
                $dbName = config('database.connections.mysql.database');
                // Use a default database name if config is empty (though it shouldn't be)
                if (!$dbName)
                    $dbName = 'forge';

                $result = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size 
                                    FROM information_schema.TABLES 
                                    WHERE table_schema = ?", [$dbName]);
                return $result[0]->size ?? 0;
            } catch (\Exception $e) {
                return 0;
            }
        });

        // 2. B2 Stats
        $b2Stats = Cache::remember('system_b2_stats', 300, function () {
            $stats = [
                'count' => 0,
                'size' => 0,
                'error' => null,
                'formatted_size' => '0 MB'
            ];

            try {
                if (config('filesystems.disks.b2.key')) {
                    $disk = Storage::disk('b2');

                    // Get all files (might be slow for many files)
                    // We recursively fetch to count everything
                    $files = $disk->allFiles();
                    $stats['count'] = count($files);

                    // Calculate size
                    // Optimization: We assume S3 adapter caches metadata or we limit this
                    $totalSize = 0;

                    // If too many files, we skip size calculation to prevent timeout
                    if ($stats['count'] < 1000) {
                        foreach ($files as $file) {
                            $totalSize += $disk->size($file);
                        }
                    } else {
                        $stats['size_warning'] = 'Too many files to calculate exact size quickly.';
                    }

                    $stats['size'] = $totalSize;
                    $stats['formatted_size'] = number_format($totalSize / 1024 / 1024, 2) . ' MB';
                } else {
                    $stats['error'] = 'B2 not configured.';
                }
            } catch (\Exception $e) {
                $stats['error'] = $e->getMessage();
            }

            return $stats;
        });

        return view('admin.system.status', compact('dbSize', 'b2Stats'));
    }

    public function refresh()
    {
        Cache::forget('system_db_size');
        Cache::forget('system_b2_stats');
        return redirect()->route('system.status')->with('success', 'Status refreshed.');
    }
}
