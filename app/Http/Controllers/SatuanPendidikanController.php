<<<<<<< HEAD
<?php

namespace App\Http\Controllers;

use App\Models\SatuanPendidikan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SatuanPendidikanController extends Controller
{
    /**
     * Autocomplete search for Satuan Pendidikan
     * Prioritizes local database, falls back to external sources
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $keyword = $request->get('q', '');
        $limit = min($request->get('limit', 10), 20);

        if (strlen($keyword) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Keyword minimal 2 karakter',
                'data' => [],
            ]);
        }

        // Search in local database first
        $results = SatuanPendidikan::search($keyword)
            ->select(['id', 'npsn', 'nama', 'alamat_lengkap', 'kota', 'provinsi', 'is_local', 'status_sp'])
            ->orderBy('nama')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'source' => 'local',
            'data' => $results,
            'message' => $results->isEmpty()
                ? 'Tidak ditemukan di database lokal. Silakan cari di https://referensi.data.kemendikdasmen.go.id/pendidikan/dikdas'
                : null,
        ]);
    }

    /**
     * Get Satuan Pendidikan by NPSN
     * 
     * @param string $npsn
     * @return \Illuminate\Http\JsonResponse
     */
    public function findByNpsn($npsn)
    {
        try {
            // Gunakan DB facade langsung untuk menghindari error Model
            $satpen = \DB::table('satuan_pendidikan')->where('npsn', $npsn)->first();

            if ($satpen) {
                return response()->json([
                    'found' => true,
                    'success' => true,
                    'source' => 'local',
                    'nama' => $satpen->nama ?? '',
                    'alamat_lengkap' => $satpen->alamat_lengkap ?? '',
                    'kota' => $satpen->kota ?? '',
                    'provinsi' => $satpen->provinsi ?? '',
                    'data' => $satpen,
                ]);
            }

            return response()->json([
                'found' => false,
                'success' => false,
                'source' => null,
                'message' => 'NPSN tidak ditemukan di database lokal.',
                'data' => null,
            ]);
        } catch (\Exception $e) {
            \Log::error('NPSN lookup error: ' . $e->getMessage());
            return response()->json([
                'found' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all local Satuan Pendidikan (SMP Kota Bogor)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = SatuanPendidikan::local();

        // Filter by status (Negeri/Swasta)
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Search
        if ($request->has('q') && strlen($request->q) >= 2) {
            $query->search($request->q);
        }

        $satpen = $query->orderBy('nama')->get();

        return response()->json([
            'success' => true,
            'data' => $satpen,
            'total' => $satpen->count(),
        ]);
    }

    /**
     * Get provinces from local database
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Get provinces (Local DB -> External API Fallback)
     */
    public function getProvinces()
    {
        // Try Local DB First
        if (\Schema::hasTable('ref_provinsi')) {
            $provinces = Cache::rememberForever('ref_provinces_local', function () {
                return \DB::table('ref_provinsi')->select(['id', 'nama as name'])->orderBy('nama')->get();
            });

            if ($provinces->isNotEmpty()) {
                return response()->json(['success' => true, 'source' => 'local', 'data' => $provinces]);
            }
        }

        // Fallback to External API (Emsifa)
        return Cache::remember('ref_provinces_external', 86400, function () { // Cache for 24h
            try {
                $response = Http::timeout(5)->get('https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json');
                if ($response->successful()) {
                    return response()->json(['success' => true, 'source' => 'external', 'data' => $response->json()]);
                }
            } catch (\Exception $e) {
                \Log::error('External province fetch failed: ' . $e->getMessage());
            }
            return response()->json(['success' => false, 'data' => [], 'message' => 'Gagal memuat data provinsi']);
        });
    }

    /**
     * Get regencies (Local DB -> Local JSON -> External API Fallback)
     */
    public function getRegencies($provinceId)
    {
        // 1. Try Local DB
        if (\Schema::hasTable('ref_kabupaten')) {
            $regencies = Cache::rememberForever("ref_regencies_local_{$provinceId}", function () use ($provinceId) {
                return \DB::table('ref_kabupaten')
                    ->where('province_id', $provinceId)
                    ->select(['id', 'nama as name'])
                    ->orderBy('nama')
                    ->get();
            });

            if ($regencies->isNotEmpty()) {
                return response()->json(['success' => true, 'source' => 'local_db', 'data' => $regencies]);
            }
        }

        // 2. Try Local JSON File
        if (file_exists(public_path('data/kabkota.json'))) {
            $regencies = Cache::remember("ref_regencies_json_{$provinceId}", 86400, function () use ($provinceId) {
                $content = file_get_contents(public_path('data/kabkota.json'));
                $data = json_decode($content, true);
                return collect($data)->where('province_id', $provinceId)->values();
            });

            if ($regencies->isNotEmpty()) {
                return response()->json(['success' => true, 'source' => 'local_file', 'data' => $regencies]);
            }
        }

        // 3. Fallback to External API (Emsifa)
        return Cache::remember("ref_regencies_external_{$provinceId}", 86400, function () use ($provinceId) {
            try {
                $response = Http::timeout(5)->get("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$provinceId}.json");
                if ($response->successful()) {
                    return response()->json(['success' => true, 'source' => 'external', 'data' => $response->json()]);
                }
            } catch (\Exception $e) {
                \Log::error('External regency fetch failed: ' . $e->getMessage());
            }
            return response()->json(['success' => false, 'data' => [], 'message' => 'Gagal memuat data kabupaten']);
        });
    }

    /**
     * Get districts (Kecamatan) by regency ID from local database
     * 
     * @param string $regencyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDistricts($regencyId)
    {
        $districts = Cache::rememberForever("ref_districts_{$regencyId}", function () use ($regencyId) {
            if (\Schema::hasTable('ref_kecamatan')) {
                return \DB::table('ref_kecamatan')
                    ->where('regency_id', $regencyId)
                    ->select(['id', 'regency_id', 'nama as name'])
                    ->orderBy('nama')
                    ->get();
            }
            return collect([]);
        });

        if ($districts->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'source' => 'local_cached',
                'data' => $districts,
            ]);
        }

        return response()->json([
            'success' => false,
            'source' => 'local',
            'data' => [],
        ]);
    }

    /**
     * Get villages (Kelurahan/Desa) by district ID from local database
     * 
     * @param string $districtId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVillages($districtId)
    {
        $villages = Cache::rememberForever("ref_villages_{$districtId}", function () use ($districtId) {
            if (\Schema::hasTable('ref_kelurahan')) {
                return \DB::table('ref_kelurahan')
                    ->where('district_id', $districtId)
                    ->select(['id', 'district_id', 'nama as name'])
                    ->orderBy('nama')
                    ->get();
            }
            return collect([]);
        });

        if ($villages->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'source' => 'local_cached',
                'data' => $villages,
            ]);
        }

        return response()->json([
            'success' => false,
            'source' => 'local',
            'data' => [],
        ]);
    }
    /**
     * Auto-save new school from Mutation data when status = "Selesai"
     * Called ONLY when Dinas completes a mutation (completeLetter)
     * 
     * IMPORTANT: This method uses DB::table() directly to BYPASS Eloquent Observer
     * so that NO user account is created automatically.
     * User accounts are ONLY created via manual entry in Menu Satuan Pendidikan.
     * 
     * @param array $data School data from mutation
     * @return void
     */
    public static function maybeStoreNewSchool($data)
    {
        try {
            if (empty($data['npsn']) || empty($data['nama'])) {
                return;
            }

            // Validate NPSN format (8 chars)
            $npsn = trim($data['npsn']);
            if (strlen($npsn) !== 8) {
                \Log::warning("maybeStoreNewSchool: Invalid NPSN format: {$npsn}");
                return;
            }

            // Check if exists
            $exists = \DB::table('satuan_pendidikan')
                ->where('npsn', $npsn)
                ->exists();

            if (!$exists) {
                // Use DB::table to BYPASS Observer (no auto-create user account)
                \DB::table('satuan_pendidikan')->insert([
                    'npsn' => $npsn,
                    'nama' => trim($data['nama']),
                    'alamat_lengkap' => $data['alamat'] ?? null,
                    'kota' => $data['kota'] ?? null,
                    'provinsi' => $data['provinsi'] ?? null,
                    'bentuk_pendidikan' => 'SMP',
                    'status_sp' => 'Swasta', // Default for auto-saved schools
                    'is_local' => false, // Mark as external (not Kota Bogor)
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                \Log::info("Auto-saved new school (NO user account created): {$data['nama']} ({$npsn})");
            }
        } catch (\Exception $e) {
            \Log::error("Failed to auto-save school: " . $e->getMessage());
            // Fail silently to not disrupt the main flow
        }
    }

    /**
     * Management page - List all schools with search and pagination
     */
    public function manage(Request $request)
    {
        $query = \DB::table('satuan_pendidikan')
            ->orderBy('is_local', 'desc')
            ->orderBy('nama', 'asc');

        // Search filter
        if ($request->filled('search')) {
            $search = '%' . strip_tags($request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', $search)
                    ->orWhere('npsn', 'like', $search)
                    ->orWhere('kota', 'like', $search);
            });
        }

        // Status filter (Negeri/Swasta)
        if ($request->filled('status')) {
            $query->where('status_sp', $request->status);
        }

        // Is Local filter
        if ($request->filled('is_local')) {
            $query->where('is_local', $request->is_local);
        }

        $satpenList = $query->paginate(10);

        // Statistics for charts (only local schools - Kota Bogor)
        $statsQuery = \DB::table('satuan_pendidikan')->where('is_local', true);

        // Status statistics (Negeri/Swasta)
        $byStatus = \DB::table('satuan_pendidikan')
            ->where('is_local', true)
            ->select('status_sp', \DB::raw('count(*) as total'))
            ->groupBy('status_sp')
            ->get()
            ->pluck('total', 'status_sp')
            ->toArray();

        // Kecamatan statistics with Negeri/Swasta breakdown
        $byKecamatan = \DB::table('satuan_pendidikan')
            ->where('is_local', true)
            ->whereNotNull('kecamatan')
            ->where('kecamatan', '!=', '')
            ->select(
                'kecamatan',
                \DB::raw("SUM(CASE WHEN status_sp = 'Negeri' THEN 1 ELSE 0 END) as negeri"),
                \DB::raw("SUM(CASE WHEN status_sp = 'Swasta' THEN 1 ELSE 0 END) as swasta"),
                \DB::raw('count(*) as total')
            )
            ->groupBy('kecamatan')
            ->orderBy('total', 'desc')
            ->get();

        // Total counts
        $totalLocal = \DB::table('satuan_pendidikan')->where('is_local', true)->count();
        $totalExternal = \DB::table('satuan_pendidikan')->where('is_local', false)->count();

        return view('satpen.index', compact(
            'satpenList',
            'byStatus',
            'byKecamatan',
            'totalLocal',
            'totalExternal'
        ));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('satpen.create');
    }

    /**
     * Store new school
     */
    public function store(Request $request)
    {
        $request->validate([
            'npsn' => 'required|string|size:8|unique:satuan_pendidikan,npsn',
            'nama' => 'required|string|max:150',
            'bentuk_pendidikan' => 'required|in:SMP,SMP SPK,SMP Terbuka',
            'status_sp' => 'required|in:Negeri,Swasta',
            'alamat_jalan' => 'nullable|string',
            'kelurahan' => 'nullable|string|max:50',
            'kecamatan' => 'nullable|string|max:50',
            'kota' => 'required|string|max:50',
            'provinsi' => 'required|string|max:50',
        ], [
            'npsn.size' => 'NPSN harus 8 digit',
            'npsn.unique' => 'NPSN sudah terdaftar',
        ]);

        try {
            // Build alamat_lengkap
            $alamatParts = array_filter([
                $request->alamat_jalan,
                $request->kelurahan ? "Kel. {$request->kelurahan}" : '',
                $request->kecamatan ? "Kec. {$request->kecamatan}" : '',
                $request->kota,
                $request->provinsi,
            ]);
            $alamatLengkap = implode(', ', $alamatParts);

            \DB::table('satuan_pendidikan')->insert([
                'npsn' => $request->npsn,
                'nama' => $request->nama,
                'bentuk_pendidikan' => $request->bentuk_pendidikan,
                'status_sp' => $request->status_sp,
                'alamat_jalan' => $request->alamat_jalan,
                'kelurahan' => $request->kelurahan,
                'kecamatan' => $request->kecamatan,
                'kota' => $request->kota,
                'provinsi' => $request->provinsi,
                'alamat_lengkap' => $alamatLengkap,
                'is_local' => 1, // Manual entry is local
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->route('satpen.index')
                ->with('success', "Satuan Pendidikan {$request->nama} berhasil ditambahkan");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menambahkan data: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $satpen = \DB::table('satuan_pendidikan')->where('id', $id)->first();

        if (!$satpen) {
            abort(404);
        }

        return view('satpen.edit', compact('satpen'));
    }

    /**
     * Update school data
     */
    public function update(Request $request, $id)
    {
        $satpen = \DB::table('satuan_pendidikan')->where('id', $id)->first();

        if (!$satpen) {
            abort(404);
        }

        $request->validate([
            'npsn' => "required|string|size:8|unique:satuan_pendidikan,npsn,{$id}",
            'nama' => 'required|string|max:150',
            'bentuk_pendidikan' => 'required|in:SMP,SMP SPK,SMP Terbuka',
            'status_sp' => 'required|in:Negeri,Swasta',
            'alamat_jalan' => 'nullable|string',
            'kelurahan' => 'nullable|string|max:50',
            'kecamatan' => 'nullable|string|max:50',
            'kota' => 'required|string|max:50',
            'provinsi' => 'required|string|max:50',
        ], [
            'npsn.size' => 'NPSN harus 8 digit',
            'npsn.unique' => 'NPSN sudah terdaftar',
        ]);

        try {
            // Build alamat_lengkap
            $alamatParts = array_filter([
                $request->alamat_jalan,
                $request->kelurahan ? "Kel. {$request->kelurahan}" : '',
                $request->kecamatan ? "Kec. {$request->kecamatan}" : '',
                $request->kota,
                $request->provinsi,
            ]);
            $alamatLengkap = implode(', ', $alamatParts);

            \DB::table('satuan_pendidikan')->where('id', $id)->update([
                'npsn' => $request->npsn,
                'nama' => $request->nama,
                'bentuk_pendidikan' => $request->bentuk_pendidikan,
                'status_sp' => $request->status_sp,
                'alamat_jalan' => $request->alamat_jalan,
                'kelurahan' => $request->kelurahan,
                'kecamatan' => $request->kecamatan,
                'kota' => $request->kota,
                'provinsi' => $request->provinsi,
                'alamat_lengkap' => $alamatLengkap,
                'is_local' => $request->is_local ?? 0,
                'updated_at' => now(),
            ]);

            return redirect()->route('satpen.index')
                ->with('success', "Satuan Pendidikan {$request->nama} berhasil diperbarui");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage())->withInput();
        }
    }
}
=======
<?php

namespace App\Http\Controllers;

use App\Models\SatuanPendidikan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SatuanPendidikanController extends Controller
{
    /**
     * Autocomplete search for Satuan Pendidikan
     * Prioritizes local database, falls back to external sources
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $keyword = $request->get('q', '');
        $limit = min($request->get('limit', 10), 20);

        if (strlen($keyword) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Keyword minimal 2 karakter',
                'data' => [],
            ]);
        }

        // Search in local database first
        $results = SatuanPendidikan::search($keyword)
            ->select(['id', 'npsn', 'nama', 'alamat_lengkap', 'kota', 'provinsi', 'is_local', 'status_sp'])
            ->orderBy('nama')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'source' => 'local',
            'data' => $results,
            'message' => $results->isEmpty()
                ? 'Tidak ditemukan di database lokal. Silakan cari di https://referensi.data.kemendikdasmen.go.id/pendidikan/dikdas'
                : null,
        ]);
    }

    /**
     * Get Satuan Pendidikan by NPSN
     * 
     * @param string $npsn
     * @return \Illuminate\Http\JsonResponse
     */
    public function findByNpsn($npsn)
    {
        try {
            // Gunakan DB facade langsung untuk menghindari error Model
            $satpen = \DB::table('satuan_pendidikan')->where('npsn', $npsn)->first();

            if ($satpen) {
                return response()->json([
                    'found' => true,
                    'success' => true,
                    'source' => 'local',
                    'nama' => $satpen->nama ?? '',
                    'alamat_lengkap' => $satpen->alamat_lengkap ?? '',
                    'kota' => $satpen->kota ?? '',
                    'provinsi' => $satpen->provinsi ?? '',
                    'data' => $satpen,
                ]);
            }

            return response()->json([
                'found' => false,
                'success' => false,
                'source' => null,
                'message' => 'NPSN tidak ditemukan di database lokal.',
                'data' => null,
            ]);
        } catch (\Exception $e) {
            \Log::error('NPSN lookup error: ' . $e->getMessage());
            return response()->json([
                'found' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all local Satuan Pendidikan (SMP Kota Bogor)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = SatuanPendidikan::local();

        // Filter by status (Negeri/Swasta)
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Search
        if ($request->has('q') && strlen($request->q) >= 2) {
            $query->search($request->q);
        }

        $satpen = $query->orderBy('nama')->get();

        return response()->json([
            'success' => true,
            'data' => $satpen,
            'total' => $satpen->count(),
        ]);
    }

    /**
     * Get provinces from local database
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Get provinces (Local DB -> External API Fallback)
     */
    public function getProvinces()
    {
        // Try Local DB First
        if (\Schema::hasTable('ref_provinsi')) {
            $provinces = Cache::rememberForever('ref_provinces_local', function () {
                return \DB::table('ref_provinsi')->select(['id', 'nama as name'])->orderBy('nama')->get();
            });

            if ($provinces->isNotEmpty()) {
                return response()->json(['success' => true, 'source' => 'local', 'data' => $provinces]);
            }
        }

        // Fallback to External API (Emsifa)
        return Cache::remember('ref_provinces_external', 86400, function () { // Cache for 24h
            try {
                $response = Http::timeout(5)->get('https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json');
                if ($response->successful()) {
                    return response()->json(['success' => true, 'source' => 'external', 'data' => $response->json()]);
                }
            } catch (\Exception $e) {
                \Log::error('External province fetch failed: ' . $e->getMessage());
            }
            return response()->json(['success' => false, 'data' => [], 'message' => 'Gagal memuat data provinsi']);
        });
    }

    /**
     * Get regencies (Local DB -> Local JSON -> External API Fallback)
     */
    public function getRegencies($provinceId)
    {
        // 1. Try Local DB
        if (\Schema::hasTable('ref_kabupaten')) {
            $regencies = Cache::rememberForever("ref_regencies_local_{$provinceId}", function () use ($provinceId) {
                return \DB::table('ref_kabupaten')
                    ->where('province_id', $provinceId)
                    ->select(['id', 'nama as name'])
                    ->orderBy('nama')
                    ->get();
            });

            if ($regencies->isNotEmpty()) {
                return response()->json(['success' => true, 'source' => 'local_db', 'data' => $regencies]);
            }
        }

        // 2. Try Local JSON File
        if (file_exists(public_path('data/kabkota.json'))) {
            $regencies = Cache::remember("ref_regencies_json_{$provinceId}", 86400, function () use ($provinceId) {
                $content = file_get_contents(public_path('data/kabkota.json'));
                $data = json_decode($content, true);
                return collect($data)->where('province_id', $provinceId)->values();
            });

            if ($regencies->isNotEmpty()) {
                return response()->json(['success' => true, 'source' => 'local_file', 'data' => $regencies]);
            }
        }

        // 3. Fallback to External API (Emsifa)
        return Cache::remember("ref_regencies_external_{$provinceId}", 86400, function () use ($provinceId) {
            try {
                $response = Http::timeout(5)->get("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$provinceId}.json");
                if ($response->successful()) {
                    return response()->json(['success' => true, 'source' => 'external', 'data' => $response->json()]);
                }
            } catch (\Exception $e) {
                \Log::error('External regency fetch failed: ' . $e->getMessage());
            }
            return response()->json(['success' => false, 'data' => [], 'message' => 'Gagal memuat data kabupaten']);
        });
    }

    /**
     * Get districts (Kecamatan) by regency ID from local database
     * 
     * @param string $regencyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDistricts($regencyId)
    {
        $districts = Cache::rememberForever("ref_districts_{$regencyId}", function () use ($regencyId) {
            if (\Schema::hasTable('ref_kecamatan')) {
                return \DB::table('ref_kecamatan')
                    ->where('regency_id', $regencyId)
                    ->select(['id', 'regency_id', 'nama as name'])
                    ->orderBy('nama')
                    ->get();
            }
            return collect([]);
        });

        if ($districts->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'source' => 'local_cached',
                'data' => $districts,
            ]);
        }

        return response()->json([
            'success' => false,
            'source' => 'local',
            'data' => [],
        ]);
    }

    /**
     * Get villages (Kelurahan/Desa) by district ID from local database
     * 
     * @param string $districtId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVillages($districtId)
    {
        $villages = Cache::rememberForever("ref_villages_{$districtId}", function () use ($districtId) {
            if (\Schema::hasTable('ref_kelurahan')) {
                return \DB::table('ref_kelurahan')
                    ->where('district_id', $districtId)
                    ->select(['id', 'district_id', 'nama as name'])
                    ->orderBy('nama')
                    ->get();
            }
            return collect([]);
        });

        if ($villages->isNotEmpty()) {
            return response()->json([
                'success' => true,
                'source' => 'local_cached',
                'data' => $villages,
            ]);
        }

        return response()->json([
            'success' => false,
            'source' => 'local',
            'data' => [],
        ]);
    }
    /**
     * Auto-save new school from Mutation data when status = "Selesai"
     * Called ONLY when Dinas completes a mutation (completeLetter)
     * 
     * IMPORTANT: This method uses DB::table() directly to BYPASS Eloquent Observer
     * so that NO user account is created automatically.
     * User accounts are ONLY created via manual entry in Menu Satuan Pendidikan.
     * 
     * @param array $data School data from mutation
     * @return void
     */
    public static function maybeStoreNewSchool($data)
    {
        try {
            if (empty($data['npsn']) || empty($data['nama'])) {
                return;
            }

            // Validate NPSN format (8 chars)
            $npsn = trim($data['npsn']);
            if (strlen($npsn) !== 8) {
                \Log::warning("maybeStoreNewSchool: Invalid NPSN format: {$npsn}");
                return;
            }

            // Check if exists
            $exists = \DB::table('satuan_pendidikan')
                ->where('npsn', $npsn)
                ->exists();

            if (!$exists) {
                // Use DB::table to BYPASS Observer (no auto-create user account)
                \DB::table('satuan_pendidikan')->insert([
                    'npsn' => $npsn,
                    'nama' => trim($data['nama']),
                    'alamat_lengkap' => $data['alamat'] ?? null,
                    'kota' => $data['kota'] ?? null,
                    'provinsi' => $data['provinsi'] ?? null,
                    'bentuk_pendidikan' => 'SMP',
                    'status_sp' => 'Swasta', // Default for auto-saved schools
                    'is_local' => false, // Mark as external (not Kota Bogor)
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                \Log::info("Auto-saved new school (NO user account created): {$data['nama']} ({$npsn})");
            }
        } catch (\Exception $e) {
            \Log::error("Failed to auto-save school: " . $e->getMessage());
            // Fail silently to not disrupt the main flow
        }
    }

    /**
     * Management page - List all schools with search and pagination
     */
    public function manage(Request $request)
    {
        $query = \DB::table('satuan_pendidikan')
            ->orderBy('is_local', 'desc')
            ->orderBy('nama', 'asc');

        // Search filter
        if ($request->filled('search')) {
            $search = '%' . strip_tags($request->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', $search)
                    ->orWhere('npsn', 'like', $search)
                    ->orWhere('kota', 'like', $search);
            });
        }

        // Status filter (Negeri/Swasta)
        if ($request->filled('status')) {
            $query->where('status_sp', $request->status);
        }

        // Is Local filter
        if ($request->filled('is_local')) {
            $query->where('is_local', $request->is_local);
        }

        $satpenList = $query->paginate(10);

        // Statistics for charts (only local schools - Kota Bogor)
        $statsQuery = \DB::table('satuan_pendidikan')->where('is_local', true);

        // Status statistics (Negeri/Swasta)
        $byStatus = \DB::table('satuan_pendidikan')
            ->where('is_local', true)
            ->select('status_sp', \DB::raw('count(*) as total'))
            ->groupBy('status_sp')
            ->get()
            ->pluck('total', 'status_sp')
            ->toArray();

        // Kecamatan statistics with Negeri/Swasta breakdown
        $byKecamatan = \DB::table('satuan_pendidikan')
            ->where('is_local', true)
            ->whereNotNull('kecamatan')
            ->where('kecamatan', '!=', '')
            ->select(
                'kecamatan',
                \DB::raw("SUM(CASE WHEN status_sp = 'Negeri' THEN 1 ELSE 0 END) as negeri"),
                \DB::raw("SUM(CASE WHEN status_sp = 'Swasta' THEN 1 ELSE 0 END) as swasta"),
                \DB::raw('count(*) as total')
            )
            ->groupBy('kecamatan')
            ->orderBy('total', 'desc')
            ->get();

        // Total counts
        $totalLocal = \DB::table('satuan_pendidikan')->where('is_local', true)->count();
        $totalExternal = \DB::table('satuan_pendidikan')->where('is_local', false)->count();

        return view('satpen.index', compact(
            'satpenList',
            'byStatus',
            'byKecamatan',
            'totalLocal',
            'totalExternal'
        ));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('satpen.create');
    }

    /**
     * Store new school
     */
    public function store(Request $request)
    {
        $request->validate([
            'npsn' => 'required|string|size:8|unique:satuan_pendidikan,npsn',
            'nama' => 'required|string|max:150',
            'bentuk_pendidikan' => 'required|in:SMP,SMP SPK,SMP Terbuka',
            'status_sp' => 'required|in:Negeri,Swasta',
            'alamat_jalan' => 'nullable|string',
            'kelurahan' => 'nullable|string|max:50',
            'kecamatan' => 'nullable|string|max:50',
            'kota' => 'required|string|max:50',
            'provinsi' => 'required|string|max:50',
        ], [
            'npsn.size' => 'NPSN harus 8 digit',
            'npsn.unique' => 'NPSN sudah terdaftar',
        ]);

        try {
            // Build alamat_lengkap
            $alamatParts = array_filter([
                $request->alamat_jalan,
                $request->kelurahan ? "Kel. {$request->kelurahan}" : '',
                $request->kecamatan ? "Kec. {$request->kecamatan}" : '',
                $request->kota,
                $request->provinsi,
            ]);
            $alamatLengkap = implode(', ', $alamatParts);

            \DB::table('satuan_pendidikan')->insert([
                'npsn' => $request->npsn,
                'nama' => $request->nama,
                'bentuk_pendidikan' => $request->bentuk_pendidikan,
                'status_sp' => $request->status_sp,
                'alamat_jalan' => $request->alamat_jalan,
                'kelurahan' => $request->kelurahan,
                'kecamatan' => $request->kecamatan,
                'kota' => $request->kota,
                'provinsi' => $request->provinsi,
                'alamat_lengkap' => $alamatLengkap,
                'is_local' => 1, // Manual entry is local
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->route('satpen.index')
                ->with('success', "Satuan Pendidikan {$request->nama} berhasil ditambahkan");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menambahkan data: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $satpen = \DB::table('satuan_pendidikan')->where('id', $id)->first();

        if (!$satpen) {
            abort(404);
        }

        return view('satpen.edit', compact('satpen'));
    }

    /**
     * Update school data
     */
    public function update(Request $request, $id)
    {
        $satpen = \DB::table('satuan_pendidikan')->where('id', $id)->first();

        if (!$satpen) {
            abort(404);
        }

        $request->validate([
            'npsn' => "required|string|size:8|unique:satuan_pendidikan,npsn,{$id}",
            'nama' => 'required|string|max:150',
            'bentuk_pendidikan' => 'required|in:SMP,SMP SPK,SMP Terbuka',
            'status_sp' => 'required|in:Negeri,Swasta',
            'alamat_jalan' => 'nullable|string',
            'kelurahan' => 'nullable|string|max:50',
            'kecamatan' => 'nullable|string|max:50',
            'kota' => 'required|string|max:50',
            'provinsi' => 'required|string|max:50',
        ], [
            'npsn.size' => 'NPSN harus 8 digit',
            'npsn.unique' => 'NPSN sudah terdaftar',
        ]);

        try {
            // Build alamat_lengkap
            $alamatParts = array_filter([
                $request->alamat_jalan,
                $request->kelurahan ? "Kel. {$request->kelurahan}" : '',
                $request->kecamatan ? "Kec. {$request->kecamatan}" : '',
                $request->kota,
                $request->provinsi,
            ]);
            $alamatLengkap = implode(', ', $alamatParts);

            \DB::table('satuan_pendidikan')->where('id', $id)->update([
                'npsn' => $request->npsn,
                'nama' => $request->nama,
                'bentuk_pendidikan' => $request->bentuk_pendidikan,
                'status_sp' => $request->status_sp,
                'alamat_jalan' => $request->alamat_jalan,
                'kelurahan' => $request->kelurahan,
                'kecamatan' => $request->kecamatan,
                'kota' => $request->kota,
                'provinsi' => $request->provinsi,
                'alamat_lengkap' => $alamatLengkap,
                'is_local' => $request->is_local ?? 0,
                'updated_at' => now(),
            ]);

            return redirect()->route('satpen.index')
                ->with('success', "Satuan Pendidikan {$request->nama} berhasil diperbarui");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage())->withInput();
        }
    }
}
>>>>>>> 9466a07 (Upload project pertama kali)
