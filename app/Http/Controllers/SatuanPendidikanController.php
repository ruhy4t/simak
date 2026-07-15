<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\SatuanPendidikan;
use App\Services\RegionCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SatuanPendidikanController extends Controller
{
    protected $regionCache;

    public function __construct(RegionCacheService $regionCache)
    {
        $this->regionCache = $regionCache;
    }
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
        $result = $this->regionCache->getProvinces();

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json(['success' => false, 'data' => [], 'message' => $result['message'] ?? 'Gagal memuat data provinsi']);
    }

    /**
     * Get regencies (Local DB -> Local JSON -> External API Fallback)
     */
    public function getRegencies($provinceId)
    {
        $result = $this->regionCache->getRegencies($provinceId);

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json(['success' => false, 'data' => [], 'message' => $result['message'] ?? 'Gagal memuat data kabupaten']);
    }

    /**
     * Get districts (Kecamatan) by regency ID from local database
     * 
     * @param string $regencyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDistricts($regencyId)
    {
        $result = $this->regionCache->getDistricts($regencyId);
        return response()->json($result);
    }

    /**
     * Get villages (Kelurahan/Desa) by district ID from local database
     * 
     * @param string $districtId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVillages($districtId)
    {
        $result = $this->regionCache->getVillages($districtId);
        return response()->json($result);
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

        $activeAcademicYear = null;
        if (Schema::hasTable('academic_years') && Schema::hasTable('satuan_pendidikan_student_counts')) {
            $activeAcademicYear = AcademicYear::query()->where('is_active', true)->first();

            if ($activeAcademicYear) {
                $annualCounts = DB::table('satuan_pendidikan_student_counts')
                    ->where('satuan_pendidikan_id', $satpen->id)
                    ->where('academic_year_id', $activeAcademicYear->id)
                    ->get()
                    ->keyBy('grade');

                foreach ([7, 8, 9] as $grade) {
                    $satpen->{"jumlah_siswa_{$grade}_l"} = (int) ($annualCounts->get($grade)->male_count ?? 0);
                    $satpen->{"jumlah_siswa_{$grade}_p"} = (int) ($annualCounts->get($grade)->female_count ?? 0);
                }
            }
        }

        return view('satpen.edit', compact('satpen', 'activeAcademicYear'));
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

        $rules = [
            'npsn' => "required|string|size:8|unique:satuan_pendidikan,npsn,{$id}",
            'nama' => 'required|string|max:150',
            'bentuk_pendidikan' => 'required|in:SMP,SMP SPK,SMP Terbuka',
            'status_sp' => 'required|in:Negeri,Swasta',
            'alamat_jalan' => 'nullable|string',
            'kelurahan' => 'nullable|string|max:50',
            'kecamatan' => 'nullable|string|max:50',
            'kota' => 'required|string|max:50',
            'provinsi' => 'required|string|max:50',
            // New fields for school profile
            'kepala_sekolah_nama' => 'nullable|string|max:150',
            'kepala_sekolah_nip' => 'nullable|string|max:50',
            'jumlah_siswa_7_l' => 'nullable|integer|min:0',
            'jumlah_siswa_7_p' => 'nullable|integer|min:0',
            'jumlah_siswa_8_l' => 'nullable|integer|min:0',
            'jumlah_siswa_8_p' => 'nullable|integer|min:0',
            'jumlah_siswa_9_l' => 'nullable|integer|min:0',
            'jumlah_siswa_9_p' => 'nullable|integer|min:0',
            'kop_surat' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        if (Schema::hasTable('academic_years')) {
            $rules['academic_year_id'] = 'nullable|integer|exists:academic_years,id';
        }

        $request->validate($rules, [
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

            $updateData = [
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
                // New profile fields
                'kepala_sekolah_nama' => $request->kepala_sekolah_nama,
                'kepala_sekolah_nip' => $request->kepala_sekolah_nip,
                'jumlah_siswa_7_l' => $request->jumlah_siswa_7_l ?? 0,
                'jumlah_siswa_7_p' => $request->jumlah_siswa_7_p ?? 0,
                'jumlah_siswa_8_l' => $request->jumlah_siswa_8_l ?? 0,
                'jumlah_siswa_8_p' => $request->jumlah_siswa_8_p ?? 0,
                'jumlah_siswa_9_l' => $request->jumlah_siswa_9_l ?? 0,
                'jumlah_siswa_9_p' => $request->jumlah_siswa_9_p ?? 0,
                'jumlah_siswa_awal_7' => (int) ($request->jumlah_siswa_7_l ?? 0) + (int) ($request->jumlah_siswa_7_p ?? 0),
                'jumlah_siswa_awal_8' => (int) ($request->jumlah_siswa_8_l ?? 0) + (int) ($request->jumlah_siswa_8_p ?? 0),
                'jumlah_siswa_awal_9' => (int) ($request->jumlah_siswa_9_l ?? 0) + (int) ($request->jumlah_siswa_9_p ?? 0),
                'updated_at' => now(),
            ];

            // Handle Kop Surat file upload
            if ($request->hasFile('kop_surat')) {
                // Delete old file if exists
                if ($satpen->kop_surat_path && Storage::disk('public')->exists($satpen->kop_surat_path)) {
                    Storage::disk('public')->delete($satpen->kop_surat_path);
                }

                // Upload new file
                $path = $request->file('kop_surat')->store('uploads/kop_surat', 'public');
                $updateData['kop_surat_path'] = $path;
            }

            DB::transaction(function () use ($id, $request, $updateData) {
                DB::table('satuan_pendidikan')->where('id', $id)->update($updateData);

                if ($request->filled('academic_year_id') && Schema::hasTable('satuan_pendidikan_student_counts')) {
                    $now = now();
                    $rows = [];
                    foreach ([7, 8, 9] as $grade) {
                        $rows[] = [
                            'satuan_pendidikan_id' => (int) $id,
                            'academic_year_id' => $request->integer('academic_year_id'),
                            'grade' => $grade,
                            'male_count' => $request->integer("jumlah_siswa_{$grade}_l"),
                            'female_count' => $request->integer("jumlah_siswa_{$grade}_p"),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    DB::table('satuan_pendidikan_student_counts')->upsert(
                        $rows,
                        ['satuan_pendidikan_id', 'academic_year_id', 'grade'],
                        ['male_count', 'female_count', 'updated_at']
                    );
                }
            });

            return redirect()->route('satpen.index')
                ->with('success', "Satuan Pendidikan {$request->nama} berhasil diperbarui");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage())->withInput();
        }
    }
    /**
     * Edit School Profile (For Sekolah/Operator Role)
     */
    public function editProfile(Request $request)
    {
        $user = Auth::user();

        // Ensure only Sekolah/Operator can access
        if (!in_array($user->role, ['Sekolah', 'Operator'])) {
            abort(403, 'Unauthorized. Only Sekolah/Operator can edit profile.');
        }

        if (!$user->sekolah) {
            abort(404, 'Data sekolah tidak ditemukan untuk akun ini.');
        }

        $satpen = $user->sekolah;
        $academicYears = collect();
        $selectedAcademicYear = null;
        $studentCounts = $this->legacyStudentCounts($satpen);

        if (Schema::hasTable('academic_years') && Schema::hasTable('satuan_pendidikan_student_counts')) {
            $academicYears = AcademicYear::query()->orderByDesc('name')->get();
            $selectedAcademicYear = $academicYears->firstWhere('name', $request->query('academic_year'))
                ?? $academicYears->firstWhere('is_active', true)
                ?? $academicYears->first();

            if ($selectedAcademicYear) {
                $annualCounts = $satpen->studentCounts()
                    ->where('academic_year_id', $selectedAcademicYear->id)
                    ->get()
                    ->keyBy('grade');

                foreach ([7, 8, 9] as $grade) {
                    if ($annualCounts->has($grade)) {
                        $studentCounts[$grade] = [
                            'male' => $annualCounts[$grade]->male_count,
                            'female' => $annualCounts[$grade]->female_count,
                        ];
                    } elseif ($selectedAcademicYear->name !== '2025/2026') {
                        $studentCounts[$grade] = ['male' => 0, 'female' => 0];
                    }
                }
            }
        }

        return view('satpen.profile', compact(
            'satpen',
            'academicYears',
            'selectedAcademicYear',
            'studentCounts'
        ));
    }

    /**
     * Update School Profile (Kop Surat & Principal Info)
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['Sekolah', 'Operator'])) {
            abort(403);
        }

        $satpen = $user->sekolah;

        if (!$satpen) {
            abort(404);
        }

        $annualTablesReady = Schema::hasTable('academic_years')
            && Schema::hasTable('satuan_pendidikan_student_counts');

        $rules = [
            'kop_surat' => 'nullable|image|mimes:jpg,jpeg|max:2048', // Max 2MB
            'kepala_sekolah_nama' => 'nullable|string|max:150',
            'kepala_sekolah_nip' => 'nullable|string|max:50',
            // Schools only enter the new grade 7 intake. Grade 8/9 are
            // promoted automatically and can only be corrected by Dinas.
            'jumlah_siswa_7_l' => 'nullable|integer|min:0',
            'jumlah_siswa_7_p' => 'nullable|integer|min:0',
        ];

        if ($annualTablesReady) {
            $rules['academic_year_id'] = 'required|integer|exists:academic_years,id';
        }

        $request->validate($rules);

        try {
            $studentValues = [];
            foreach ([7] as $grade) {
                $studentValues[$grade] = [
                    'male' => (int) ($request->input("jumlah_siswa_{$grade}_l") ?? 0),
                    'female' => (int) ($request->input("jumlah_siswa_{$grade}_p") ?? 0),
                ];
            }

            $academicYear = $annualTablesReady
                ? AcademicYear::findOrFail($request->integer('academic_year_id'))
                : null;
            $canEditStudentCounts = !$academicYear || !$academicYear->isClosed();

            $updateData = [
                'kepala_sekolah_nama' => $request->kepala_sekolah_nama,
                'kepala_sekolah_nip' => $request->kepala_sekolah_nip,
                'updated_at' => now(),
            ];

            // Keep legacy columns synchronized with the active year so existing
            // reports continue to produce the same shape and values.
            if ($canEditStudentCounts && (!$academicYear || $academicYear->is_active)) {
                foreach ([7] as $grade) {
                    $updateData["jumlah_siswa_{$grade}_l"] = $studentValues[$grade]['male'];
                    $updateData["jumlah_siswa_{$grade}_p"] = $studentValues[$grade]['female'];
                }
            }

            // Handle File Upload
            if ($request->hasFile('kop_surat')) {
                // Delete old file if exists
                if ($satpen->kop_surat_path && Storage::disk('public')->exists($satpen->kop_surat_path)) {
                    Storage::disk('public')->delete($satpen->kop_surat_path);
                }

                // Upload new file
                // Store in public/uploads/kop_surat
                $path = $request->file('kop_surat')->store('uploads/kop_surat', 'public');
                $updateData['kop_surat_path'] = $path;
            }

            // Using Eloquent update to trigger Model events (boot/saving)
            // Use find() then update() to ensure model events fire
            $satpenModel = \App\Models\SatuanPendidikan::find($satpen->id);
            if ($satpenModel) {
                DB::transaction(function () use (
                    $satpenModel,
                    $updateData,
                    $academicYear,
                    $canEditStudentCounts,
                    $studentValues
                ) {
                    $satpenModel->update($updateData);

                    if ($academicYear && $canEditStudentCounts) {
                        foreach ([7] as $grade) {
                            $satpenModel->studentCounts()->updateOrCreate(
                                [
                                    'academic_year_id' => $academicYear->id,
                                    'grade' => $grade,
                                ],
                                [
                                    'male_count' => $studentValues[$grade]['male'],
                                    'female_count' => $studentValues[$grade]['female'],
                                ]
                            );
                        }
                    }
                });
            } else {
                // Fallback if somehow not found by ID (unlikely)
                \DB::table('satuan_pendidikan')->where('id', $satpen->id)->update($updateData);
            }

            $redirect = $academicYear
                ? redirect()->route('sekolah.profile', ['academic_year' => $academicYear->name])
                : back();

            return $redirect->with('success', 'Profil sekolah dan data siswa berhasil diperbarui.');

        } catch (\Exception $e) {
            \Log::error('Update Profile Failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal memperbarui profil: ' . $e->getMessage());
        }
    }

    private function legacyStudentCounts(SatuanPendidikan $satpen): array
    {
        $counts = [];

        foreach ([7, 8, 9] as $grade) {
            $counts[$grade] = [
                'male' => (int) ($satpen->{"jumlah_siswa_{$grade}_l"} ?? 0),
                'female' => (int) ($satpen->{"jumlah_siswa_{$grade}_p"} ?? 0),
            ];
        }

        return $counts;
    }
}
