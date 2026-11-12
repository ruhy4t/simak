<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Services\RegionCacheService;

class RegionController extends Controller
{
    protected $cacheService;

    public function __construct(RegionCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index(Request $request)
    {
        try {
            // Use Cache Service for base data?
            // Actually index searches and filters, so caching the RESULT of search is hard.
            // But we can cache the FULL list if needed.
            // For now, let's keep the query logic BUT use the service for the 'Provinces' dropdown if needed.
            // The current index implementation paginates searches, so we stick to DB query.
            // BUT we must ensure we clear downstream caches when we modify data.

            // List provinces with search and pagination
            $query = DB::table('ref_provinsi')->orderBy('nama');

            if ($request->has('search') && $request->filled('search')) {
                $search = $request->search;
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            }

            if (DB::getSchemaBuilder()->hasTable('ref_provinsi')) {
                $provinces = $query->paginate(10);
            } else {
                $provinces = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
                $provinces->setPath($request->url());
            }

            return view('admin.regions.index', compact('provinces'));

        } catch (\Throwable $e) {
            Log::error('Failed to load region data: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response('Data wilayah gagal dimuat. Silakan muat ulang halaman atau hubungi administrator.', 500);
        }
    }

    public function show(Request $request, $id)
    {
        // Show regencies for a province
        $province = DB::table('ref_provinsi')->where('id', $id)->first();
        if (!$province)
            abort(404);

        $query = DB::table('ref_kabupaten')
            ->where('province_id', $id)
            ->orderBy('nama');

        if ($request->has('search') && $request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $regencies = $query->paginate(10);

        return view('admin.regions.show', compact('province', 'regencies'));
    }

    public function store(Request $request)
    {
        $type = $request->input('type'); // provinsi, kabupaten, kecamatan, kelurahan

        $request->validate([
            'id' => 'required|numeric',
            'nama' => 'required|string|max:255',
            'type' => 'required|in:provinsi,kabupaten,kecamatan,kelurahan',
            'parent_id' => 'required_unless:type,provinsi'
        ]);

        try {
            if ($type === 'provinsi') {
                DB::table('ref_provinsi')->insert([
                    'id' => $request->id,
                    'nama' => $this->formatName($request->nama),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } elseif ($type === 'kabupaten') {
                DB::table('ref_kabupaten')->insert([
                    'id' => $request->id,
                    'province_id' => $request->parent_id,
                    'nama' => $this->formatName($request->nama),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } elseif ($type === 'kecamatan') {
                DB::table('ref_kecamatan')->insert([
                    'id' => $request->id,
                    'regency_id' => $request->parent_id,
                    'nama' => $this->formatName($request->nama),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } elseif ($type === 'kelurahan') {
                DB::table('ref_kelurahan')->insert([
                    'id' => $request->id,
                    'district_id' => $request->parent_id,
                    'nama' => $this->formatName($request->nama),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            $this->cacheService->clearCacheFor($type, $request->parent_id);

            return back()->with('success', 'Data berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Failed to add region data: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->with('error', $this->safeErrorMessage('Gagal menambahkan data wilayah'));
        }
    }

    public function destroy(Request $request)
    {
        $type = $request->input('type');
        $id = $request->input('id');

        try {
            // Invalidate cache before deletion
            if (in_array($type, ['kabupaten', 'kecamatan', 'kelurahan'])) {
                $table = 'ref_' . $type;
                $item = DB::table($table)->where('id', $id)->first();
                $parentId = null;
                if ($item) {
                    if ($type == 'kabupaten')
                        $parentId = $item->province_id;
                    if ($type == 'kecamatan')
                        $parentId = $item->regency_id;
                    if ($type == 'kelurahan')
                        $parentId = $item->district_id;

                    $this->cacheService->clearCacheFor($type, $parentId);
                }
            } else {
                $this->cacheService->clearCacheFor($type);
            }

            if ($type === 'provinsi')
                DB::table('ref_provinsi')->where('id', $id)->delete();
            elseif ($type === 'kabupaten')
                DB::table('ref_kabupaten')->where('id', $id)->delete();
            elseif ($type === 'kecamatan')
                DB::table('ref_kecamatan')->where('id', $id)->delete();
            elseif ($type === 'kelurahan')
                DB::table('ref_kelurahan')->where('id', $id)->delete();

            return back()->with('success', 'Data berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Failed to delete region data: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->with('error', $this->safeErrorMessage('Gagal menghapus data wilayah'));
        }
    }

    public function sync()
    {
        // Trigger seeding
        try {
            // Increase time limit if allowed
            if (function_exists('set_time_limit')) {
                @set_time_limit(600);
            }

            // FIX: Manually require the seeder file and Run it directly (Bypassing Artisan)
            $seederClass = 'Database\\Seeders\\RegionSeeder';

            if (!class_exists($seederClass)) {
                $seederPath = database_path('seeders/RegionSeeder.php');
                if (!file_exists($seederPath)) {
                    throw new \Exception("File RegionSeeder.php tidak ditemukan di: $seederPath");
                }
                require_once $seederPath;
            }

            if (!class_exists($seederClass)) {
                throw new \Exception("Class RegionSeeder tidak ditemukan setelah require file.");
            }

            // Instantiate and run directly
            $seeder = new $seederClass();

            // DO NOT set mock command. Let it be null so the Seeder knows it's not in CLI.

            $seeder->run();

            // Clear Cache after sync
            $this->cacheService->clearAll();

            return back()->with('success', 'Data wilayah berhasil disinkronisasi.');
        } catch (\Throwable $e) {
            Log::error('Failed to synchronize region data: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return back()->with('error', $this->safeErrorMessage('Gagal menyinkronkan data wilayah'));
        }
    }
    /**
     * Format name to Title Case with abbreviation handling
     */
    private function formatName($name)
    {
        // Convert to Title Case
        $name = ucwords(strtolower($name));

        // Handle specific abbreviations
        $replacements = [
            'Dki ' => 'DKI ',
            'Di ' => 'DI ',
            'Diy ' => 'DIY ',
            'Nad ' => 'NAD ',
        ];

        foreach ($replacements as $search => $replace) {
            if (strpos($name, $search) === 0) {
                $name = str_replace($search, $replace, $name);
            }
        }

        return $name;
    }
}
