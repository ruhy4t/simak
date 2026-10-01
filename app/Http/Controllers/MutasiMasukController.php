<?php

namespace App\Http\Controllers;

use App\Models\SatuanPendidikan;
use App\Services\MutasiService;
use App\Services\FileUploadService;
use App\Services\PdfService;
use App\Services\ImageConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MutasiMasukController extends Controller
{
    private const DRAFT_EMPTY_DATE = '1900-01-02';

    protected MutasiService $mutasiService;
    protected FileUploadService $fileUploadService;
    protected PdfService $pdfService;
    protected ImageConversionService $imageService;

    public function __construct(
        MutasiService $mutasiService,
        FileUploadService $fileUploadService,
        PdfService $pdfService,
        ImageConversionService $imageService
    ) {
        $this->mutasiService = $mutasiService;
        $this->fileUploadService = $fileUploadService;
        $this->pdfService = $pdfService;
        $this->imageService = $imageService;
    }

    /**
     * Sanitize string input - delegated to MutasiService
     */
    private function sanitize($input): ?string
    {
        return $this->mutasiService->sanitize($input);
    }

    /**
     * Sanitize filename - delegated to MutasiService
     */
    private function sanitizeFilename($filename): string
    {
        return $this->mutasiService->sanitizeFilename($filename);
    }

    /**
     * Validate and sanitize NPSN - delegated to MutasiService
     */
    private function sanitizeNpsn($npsn): ?string
    {
        return $this->mutasiService->sanitizeNpsn($npsn);
    }

    /**
     * Validate and sanitize NISN - delegated to MutasiService
     */
    private function sanitizeNisn($nisn): ?string
    {
        return $this->mutasiService->sanitizeNisn($nisn);
    }

    public function uploadArsip(Request $request, $id)
    {
        $request->validate([
            'file_arsip_scan' => 'required|file|mimes:pdf|max:2048', // Max 2MB
        ]);

        $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            return back()->with('error', 'Data tidak ditemukan.');
        }

        if ($request->hasFile('file_arsip_scan')) {
            $path = $this->fileUploadService->uploadArsip($request->file('file_arsip_scan'), $id, 'masuk');

            DB::table('mutasi_masuk')->where('id_mutasi', $id)->update([
                'file_arsip_scan' => $path,
                'updated_at' => now(),
            ]);

            \App\Models\AuditLog::log('ARSIP_UPLOADED', auth()->user()->username, "Uploaded archive for {$id}");

            return back()->with('success', 'Arsip digital berhasil diunggah.');
        }

        return back()->with('error', 'Gagal mengunggah file.');
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        try {
            // Use Eloquent Model for relationships
            $query = \App\Models\MutasiMasuk::query()
                ->with('siswa:id,id_mutasi,nama_murid,nisn,npsn_sekolah_asal,nama_sekolah_asal,kabupaten_kota_asal');

            $this->supportsSubmissionTimeline()
                ? $query->latestSubmissionFirst()
                : $query->orderByDesc('id_mutasi');

            // Role-based filtering
            if ($user->role === 'Operator') {
                // STRICT: Operator only sees their own proposals
                $query->where('pengusul', $user->username);
            } elseif ($user->role === 'Sekolah') {
                // Sekolah sees ALL proposals for their school (Relaxed Rule)
                // For Mutasi Masuk: query by npsn_sekolah_tujuan
                $query->where('npsn_sekolah_tujuan', $user->npsn);
            } elseif (in_array($user->role, ['Dinas', 'SuperAdmin'])) {
                // Dinas cannot see 'Draft' from Operator/Sekolah
                // Only show rows where status IS NOT 'Draft' OR created by themselves
                $query->where(function ($q) use ($user) {
                    $q->where('status', '!=', 'Draft')
                        ->orWhere('pengusul', $user->username);
                });
            }

            if ($request->filled('search')) {
                // Sanitize search input
                $search = '%' . $this->sanitize($request->search) . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('nomor_surat', 'like', $search)
                        ->orWhere('nama_sekolah_tujuan', 'like', $search)
                        ->orWhereHas('siswa', function ($q) use ($search) {
                            $q->where('nama_murid', 'like', $search);
                        });
                });
            }

            if ($request->filled('status')) {
                // Only allow valid status values
                $validStatuses = [
                    'Draft',
                    'Menunggu Verifikasi Dinas',
                    'Disetujui Dinas',
                    'Perlu Perbaikan',
                    'Proses TTD',
                    'Selesai',
                ];
                if (in_array($request->status, $validStatuses)) {
                    $query->where('status', $request->status);
                }
            }

            $mutasiList = $query->paginate(10)->withQueryString();

            return view('mutasi.masuk.index', compact('mutasiList'));
        } catch (\Exception $e) {
            Log::error('Mutasi Masuk Index Error: ' . $e->getMessage());
            return view('mutasi.masuk.index', ['mutasiList' => collect([])]);
        }
    }

    public function create()
    {
        $user = Auth::user();

        $alamatSekolahTujuan = '';
        $namaSekolahTujuan = $user->nama_sekolah ?? '';
        $npsnSekolahTujuan = $user->npsn ?? '';

        if (!empty($user->npsn)) {
            try {
                $satpen = DB::table('satuan_pendidikan')
                    ->where('npsn', $user->npsn)
                    ->first();

                if ($satpen) {
                    if (empty($namaSekolahTujuan)) {
                        $namaSekolahTujuan = $satpen->nama ?? '';
                    }
                    $alamatSekolahTujuan = $satpen->alamat_lengkap ?? implode(', ', array_filter([
                        $satpen->alamat_jalan ?? '',
                        $satpen->kelurahan ? "Kel. {$satpen->kelurahan}" : '',
                        $satpen->kecamatan ? "Kec. {$satpen->kecamatan}" : '',
                        $satpen->kota ?? '',
                        $satpen->provinsi ?? '',
                    ]));
                }
            } catch (\Exception $e) {
                Log::error('Error fetching satuan_pendidikan: ' . $e->getMessage());
            }
        }

        return view('mutasi.masuk.create', compact('user', 'alamatSekolahTujuan', 'namaSekolahTujuan', 'npsnSekolahTujuan'));
    }

    /**
     * Cancel and Permanently Delete Submission
    * Only allowed for pending or revision-required submissions.
     */
    public function cancel(Request $request, string $id)
    {
        $user = Auth::user();

        // 1. Transaction to ensure atomicity
        return DB::transaction(function () use ($id, $user) {
            // 2. Double Check / Locking
            $mutasi = DB::table('mutasi_masuk')
                ->where('id_mutasi', $id)
                ->lockForUpdate()
                ->first();

            if (!$mutasi) {
                abort(404);
            }

            // Authorization:
            if ($user->role === 'Operator') {
                if ($mutasi->pengusul !== $user->username) {
                    abort(403, 'Anda tidak berhak menghapus usulan ini (bukan pengusul asli).');
                }
            } elseif ($user->role === 'Sekolah') {
                // Sekolah matches NPSN or Username
                if ($mutasi->npsn_sekolah_tujuan !== $user->npsn && $mutasi->pengusul !== $user->username) {
                    abort(403, 'Anda tidak berhak menghapus usulan ini.');
                }
            } elseif (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
                abort(403);
            }

            // 3. Status Check - Allow cancel for pending or revision records
            if (!in_array($mutasi->status, ['Menunggu Verifikasi Dinas', 'Perlu Perbaikan'])) {
                return back()->with('error', 'Gagal menghapus! Status usulan tidak mengizinkan pembatalan. Silakan refresh halaman.');
            }

            // 4. Remove header and student files from the active storage disk.
            $headerFiles = ['file_surat_permohonan', 'file_arsip_scan'];
            // 5. Collect all student file references before deleting any records.
            $siswaList = DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->get();
            $fileColumns = [
                'file_surat_pindah',
                'file_surat_dapodik',
                'file_rekomendasi',
                'file_ijazah_sd',
                'file_rapor',
                'file_surat_penyaluran',
                'file_rekam_didik'
            ];

            $filePaths = array_map(fn ($column) => $mutasi->$column ?? null, $headerFiles);
            foreach ($siswaList as $siswa) {
                foreach ($fileColumns as $col) {
                    $filePaths[] = $siswa->$col ?? null;
                }
            }

            try {
                $this->fileUploadService->deleteStoredFiles($filePaths);
            } catch (\Throwable $e) {
                Log::error('Permanent mutasi masuk deletion stopped because files could not be removed.', [
                    'mutasi_id' => $id,
                    'exception' => $e,
                ]);

                return back()->with('error', 'Usulan belum dihapus karena ada berkas yang gagal dihapus. Silakan coba kembali.');
            }

            // 6. Delete Students Records
            DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->delete();

            // 7. Delete Header Record
            DB::table('mutasi_masuk')->where('id_mutasi', $id)->delete();

            return redirect()->route('mutasi.masuk.index')
                ->with('success', 'Usulan mutasi masuk dan data murid berhasil dihapus permanen.');
        });
    }

    public function store(Request $request)
    {
        $incomingStudents = $request->input('siswa', []);
        if (is_array($incomingStudents)) {
            foreach ($incomingStudents as $index => &$student) {
                if (is_array($student) && $request->boolean("siswa.{$index}.asal_luar_negeri")) {
                    $student['provinsi_asal'] = 'Luar Negeri';
                    $student['kabupaten_kota_asal'] = 'Luar Negeri';
                }
            }
            unset($student);
            $request->merge(['siswa' => $incomingStudents]);
        }
        $user = Auth::user();
        $jumlahSiswa = (int) $request->jumlah_siswa;
        $isDinasEntry = $request->has('is_dinas_entry');

        // Check Schedule (Only for non-Dinas/SuperAdmin)
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            $today = now();

            // 1. Check Holiday
            if (\App\Models\Holiday::isHoliday($today->format('Y-m-d'))) {
                return back()->with('error', 'Maaf, layanan mutasi tutup pada hari libur nasional.');
            }

            // 2. Check Allowed Days
            $allowedDays = \App\Models\Setting::getAllowedDays(); // Returns [1,2,3,4,5] etc.
            if (!empty($allowedDays) && !in_array($today->dayOfWeek, $allowedDays)) {
                return back()->with('error', 'Maaf, layanan mutasi tidak beroperasi hari ini.');
            }

            // 3. Check Allowed Hours
            if (\App\Models\Setting::isHourRestrictionEnabled()) {
                $startTime = \App\Models\Setting::get('working_hours_start', '08:00');
                $endTime = \App\Models\Setting::get('working_hours_end', '15:00');
                $currentTime = $today->format('H:i');

                if ($currentTime < $startTime || $currentTime > $endTime) {
                    return back()->with('error', "Maaf, layanan mutasi hanya dapat diakses pada pukul $startTime - $endTime WIB.");
                }
            }
        }

        // Validate jumlah_siswa (Limit Removed, now just > 0)
        if ($jumlahSiswa < 1) {
            return back()->withErrors(['jumlah_siswa' => 'Jumlah murid minimal 1'])->withInput();
        }

        // Base validation with custom messages
        $rules = [
            'draft_id' => 'nullable|string|max:20',
            'jumlah_siswa' => 'required|integer|min:1', // Removed max:5
            'nomor_surat' => 'required|string|max:50',
            'tanggal_surat' => 'required|date|before_or_equal:today|after:1900-01-01',
            'npsn_sekolah_tujuan' => 'required|string|size:8',
            'nama_sekolah_tujuan' => 'required|string|max:150',
            'alamat_sekolah_tujuan' => 'required|string|max:500',
        ];

        if (!$isDinasEntry) {
            // Allow File OR String (Async Upload path)
            // If hasFile -> strict file rules
            if ($request->hasFile('file_surat_permohonan')) {
                $rules['file_surat_permohonan'] = 'required|file|mimes:pdf|max:1024';
            } elseif ($request->filled('file_surat_permohonan') && is_string($request->input('file_surat_permohonan'))) {
                $rules['file_surat_permohonan'] = 'required|string';
            } else {
                $rules['file_surat_permohonan'] = 'required'; // Will fail
            }
        }

        $messages = [
            // Header Document
            'file_surat_permohonan.required' => 'File Surat Permohonan wajib diunggah.',
            'file_surat_permohonan.file' => 'File Surat Permohonan harus berupa file.',
            'file_surat_permohonan.mimes' => 'File Surat Permohonan harus berformat PDF.',
            'file_surat_permohonan.max' => 'File Surat Permohonan maksimal 1MB. Silakan kompres file PDF Anda.',

            // Header Data
            'jumlah_siswa.required' => 'Jumlah murid wajib diisi.',
            'jumlah_siswa.integer' => 'Jumlah murid harus berupa angka.',
            'jumlah_siswa.min' => 'Jumlah murid minimal 1.',
            'jumlah_siswa.max' => 'Jumlah murid maksimal 5 per pengajuan.',
            'nomor_surat.required' => 'Nomor Surat Permohonan wajib diisi.',
            'nomor_surat.max' => 'Nomor Surat maksimal 50 karakter.',
            'tanggal_surat.required' => 'Tanggal Surat Permohonan wajib diisi.',
            'tanggal_surat.date' => 'Format Tanggal Surat tidak valid.',
            'tanggal_surat.before_or_equal' => 'Tanggal Surat tidak boleh lebih dari hari ini.',
            'tanggal_surat.after' => 'Tanggal Surat tidak valid (terlalu lampau).',

            // School Destination
            'npsn_sekolah_tujuan.required' => 'NPSN Sekolah Tujuan wajib diisi.',
            'npsn_sekolah_tujuan.size' => 'NPSN Sekolah Tujuan harus tepat 8 karakter. Contoh: 20270134.',
            'nama_sekolah_tujuan.required' => 'Nama Sekolah Tujuan wajib diisi.',
            'nama_sekolah_tujuan.max' => 'Nama Sekolah Tujuan maksimal 150 karakter.',
            'alamat_sekolah_tujuan.required' => 'Alamat Sekolah Tujuan wajib diisi.',
            'alamat_sekolah_tujuan.max' => 'Alamat Sekolah Tujuan maksimal 500 karakter.',
        ];

        // Per-siswa validation
        for ($i = 0; $i < $jumlahSiswa; $i++) {
            $siswaNum = $i + 1;

            $rules["siswa.{$i}.nama_murid"] = 'required|string|max:100';
            $rules["siswa.{$i}.nisn"] = [$request->boolean("siswa.{$i}.asal_luar_negeri") ? 'nullable' : 'required', 'string', 'regex:/^\d{10}$/'];
            $rules["siswa.{$i}.nis"] = 'required|string|max:20';
            $rules["siswa.{$i}.tempat_lahir"] = 'required|string|max:50';
            $rules["siswa.{$i}.tanggal_lahir"] = 'required|date|before:today|after:1900-01-01';
            $rules["siswa.{$i}.jenis_kelamin"] = 'required|in:L,P';
            $rules["siswa.{$i}.kelas"] = 'required|in:VII,VIII,IX';
            $rules["siswa.{$i}.tanggal_diterima"] = 'required|date|before_or_equal:today|after:1900-01-01';
            $rules["siswa.{$i}.nama_sekolah_asal"] = 'required|string|max:150';
            $rules["siswa.{$i}.alamat_sekolah_asal"] = 'required|string|max:500';
            $rules["siswa.{$i}.provinsi_asal"] = 'required|string|max:100';
            $rules["siswa.{$i}.kabupaten_kota_asal"] = 'required|string|max:100';

            // Check if from foreign school
            $luarNegeri = $request->boolean("siswa.{$i}.asal_luar_negeri");

            // NPSN Validation
            if ($luarNegeri) {
                $rules["siswa.{$i}.npsn_sekolah_asal"] = 'nullable|string|max:8';
            } else {
                $rules["siswa.{$i}.npsn_sekolah_asal"] = 'nullable|string|max:8';
            }

            // File Validation Helper
            // Checks if input is a File (upload) OR a String (temp path from async upload)
            $addFileRule = function ($field, $isRequired, $mimeTypes = 'pdf', $maxSize = 2048) use ($rules, $i, $request) {
                $fileKey = "siswa.{$i}.{$field}";

                // If it's a file upload (Legacy/Fallback)
                if ($request->hasFile($fileKey)) {
                    // For 'mimes', we need to separate extensions from mime types if needed, 
                    // but standard 'mimes:pdf' works for extensions.
                    return $isRequired ? "required|file|mimes:{$mimeTypes}|max:{$maxSize}" : "nullable|file|mimes:{$mimeTypes}|max:{$maxSize}";
                }

                // If it's a string path (Async Upload)
                if (is_string($request->input($fileKey)) && !empty($request->input($fileKey))) {
                    return $isRequired ? 'required|string' : 'nullable|string';
                }

                // Default if nothing present
                return $isRequired ? 'required' : 'nullable';
            };

            // File validation: skip jika is_dinas_entry
            if (!$isDinasEntry) {
                // Determine requirements based on Logic
                $reqPindah = !$luarNegeri;
                $reqDapodik = !$luarNegeri;
                $reqRekom = !$luarNegeri;
                $reqPenyaluran = $luarNegeri;

                // Rekam Didik conditional
                $hasNisn = !empty($request->input("siswa.{$i}.nisn"));
                $reqRekamDidik = $hasNisn && !$luarNegeri;

                // Apply Rules
                $rules["siswa.{$i}.file_surat_pindah"] = $addFileRule('file_surat_pindah', $reqPindah, 'pdf', 1024);
                $rules["siswa.{$i}.file_surat_dapodik"] = $addFileRule('file_surat_dapodik', $reqDapodik, 'pdf', 1024);
                $rules["siswa.{$i}.file_ijazah_sd"] = $addFileRule('file_ijazah_sd', true, 'pdf', 1024);
                $rules["siswa.{$i}.file_rapor"] = $addFileRule('file_rapor', true, 'pdf', 2048);
                $rules["siswa.{$i}.file_rekomendasi"] = $addFileRule('file_rekomendasi', $reqRekom, 'pdf', 1024);
                $rules["siswa.{$i}.file_surat_penyaluran"] = $addFileRule('file_surat_penyaluran', $reqPenyaluran, 'pdf', 1024);
                $rules["siswa.{$i}.file_rekam_didik"] = $addFileRule('file_rekam_didik', $reqRekamDidik, 'jpeg,jpg,png', 1024);
            }

            // Per-siswa validation messages
            $messages["siswa.{$i}.nama_murid.required"] = "Nama Murid #{$siswaNum} wajib diisi.";
            // ... (keep generic messages, they apply to both file and string presence)
            $messages["siswa.{$i}.file_rapor.required"] = "Rapor Murid #{$siswaNum} wajib diunggah.";
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            $draft = null;
            if ($request->filled('draft_id')) {
                $draft = DB::table('mutasi_masuk')
                    ->where('id_mutasi', $request->input('draft_id'))
                    ->lockForUpdate()
                    ->first();

                if (! $draft || $draft->status !== 'Draft'
                    || ($draft->pengusul !== $user->username && ! in_array($user->role, ['Dinas', 'SuperAdmin']))) {
                    DB::rollBack();

                    return back()->with('error', 'Draft tidak ditemukan, sudah dikirim, atau tidak dapat Anda akses.')->withInput();
                }
            }

            $idMutasi = $draft?->id_mutasi ?? $this->mutasiService->generateMutasiMasukId();

            // Upload surat permohonan
            $fileSuratPermohonan = null;
            if ($request->hasFile('file_surat_permohonan')) {
                $fileSuratPermohonan = $this->fileUploadService->uploadSuratPermohonan($request, $idMutasi);
            } elseif ($request->filled('file_surat_permohonan') && is_string($request->input('file_surat_permohonan'))) {
                $tempPath = $request->input('file_surat_permohonan');
                if (str_contains($tempPath, 'temp_uploads/')) {
                    $ext = pathinfo($tempPath, PATHINFO_EXTENSION);
                    $newFilename = "surat_permohonan_{$idMutasi}.{$ext}";
                    // Destination: dokumen/mutasi_masuk/Y/m/d/filename
                    $destPath = "dokumen/mutasi_masuk/" . date('Y/m/d') . "/{$newFilename}";

                    $isCloud = $this->fileUploadService->isCloudStorage();
                    $sourceDisk = $isCloud ? 'b2' : 'local';
                    $destDisk = $isCloud ? 'b2' : 'private_docs';
                    $relativeDestPath = "mutasi_masuk/" . date('Y/m/d') . "/{$newFilename}";

                    try {
                        if (Storage::disk($sourceDisk)->exists($tempPath)) {
                            Storage::disk($destDisk)->put($relativeDestPath, Storage::disk($sourceDisk)->get($tempPath));
                            $fileSuratPermohonan = $relativeDestPath;
                            // Delete temp file to prevent reuse
                            try {
                                Storage::disk($sourceDisk)->delete($tempPath);
                                Log::info("Deleted temp file after store: {$tempPath}");
                            } catch (\Exception $delEx) {
                                Log::warning("Could not delete temp file: {$tempPath}");
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to move temp header file (Store): " . $e->getMessage());
                    }
                } else {
                    // ACCEPT EXISTING PERSISTENT PATH (Resume Draft)
                    $fileSuratPermohonan = $tempPath;
                }
            }

            // Sanitize and insert header
            // Auto-approve if submitted by Dinas/SuperAdmin (they are the verifiers)
            $initialStatus = in_array($user->role, ['Dinas', 'SuperAdmin'])
                ? 'Disetujui Dinas'
                : 'Menunggu Verifikasi Dinas';

            $headerData = [
                'id_mutasi' => $idMutasi,
                'tanggal_pengajuan' => now(),
                ...($this->supportsSubmissionTimeline() ? ['last_submitted_at' => now()] : []),
                'jumlah_siswa' => $jumlahSiswa,
                'nomor_surat' => $this->sanitize($request->nomor_surat),
                'tanggal_surat' => $request->tanggal_surat,
                'file_surat_permohonan' => $fileSuratPermohonan,
                'npsn_sekolah_tujuan' => $this->sanitizeNpsn($request->npsn_sekolah_tujuan),
                'nama_sekolah_tujuan' => $this->sanitize($request->nama_sekolah_tujuan),
                'alamat_sekolah_tujuan' => $this->sanitize($request->alamat_sekolah_tujuan),
                'status' => $initialStatus,
                'pengusul' => $draft?->pengusul ?? $user->username,
                'is_dinas_entry' => $isDinasEntry ? 1 : 0,
                'created_at' => $draft?->created_at ?? now(),
                'updated_at' => now(),
            ];

            if ($draft) {
                DB::table('mutasi_masuk')->where('id_mutasi', $idMutasi)->update($headerData);
                // The submitted form contains the complete student list. Replace
                // draft rows within the same transaction, keeping the mutation ID.
                DB::table('mutasi_masuk_siswa')->where('id_mutasi', $idMutasi)->delete();
            } else {
                DB::table('mutasi_masuk')->insert($headerData);
            }

            // Insert per murid with sanitization
            $siswaData = $request->input('siswa', []);
            $uploadPathKey = $this->fileUploadService->getDateBasedPath('masuk');

            foreach ($siswaData as $idx => $siswa) {
                // 1. Upload standard PDF files using service (Handles "File" objects)
                $filePaths = $this->fileUploadService->uploadMutasiMasukSiswaFiles($request, $idMutasi, $idx);

                // 2. Handle Async Uploads (Strings) for Standard Files
                // Manually move files if they are strings (Fast Uploads)
                $standardFields = [
                    'file_surat_pindah',
                    'file_surat_dapodik',
                    'file_rekomendasi',
                    'file_ijazah_sd',
                    'file_rapor',
                    'file_surat_penyaluran'
                ];

                $isCloud = $this->fileUploadService->isCloudStorage();
                $sourceDisk = $isCloud ? 'b2' : 'local';
                $destDisk = $isCloud ? 'b2' : 'private_docs';

                foreach ($standardFields as $field) {
                    $key = "siswa.{$idx}.{$field}";
                    $val = $request->input($key);

                    // If service didn't process it (no file object) AND we have a string path
                    if (!isset($filePaths[$field]) && is_string($val) && !empty($val)) {
                        if (str_contains($val, 'temp_uploads/')) {
                            $ext = pathinfo($val, PATHINFO_EXTENSION);
                            $newFilename = "{$idMutasi}_siswa{$idx}_{$field}.{$ext}";
                            $relativeDestPath = $uploadPathKey . '/' . $newFilename;

                            try {
                                if (Storage::disk($sourceDisk)->exists($val)) {
                                    Storage::disk($destDisk)->put($relativeDestPath, Storage::disk($sourceDisk)->get($val));
                                    $filePaths[$field] = $relativeDestPath;
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to move temp student file ($field) in Store: " . $e->getMessage());
                            }
                        } else {
                            // PERSISTENT PATH (Resume Draft): Use existing
                            $filePaths[$field] = $val;
                        }
                    }
                }

                // Handle file_rekam_didik (Image/PDF) separately
                $field = 'file_rekam_didik';
                $fileKey = "siswa.{$idx}.{$field}";

                $file = $request->file($fileKey);
                if (!$file) {
                    $file = $request->input($fileKey);
                }

                if ($file) {
                    // CASE 1: Temp File Path (String)
                    if (is_string($file)) {
                        if (str_contains($file, 'temp_uploads/')) {
                            // Just use the service to move it. 
                            // Note: We skip the WebP conversion logic for temp files to simplify, 
                            // or we could implement it in service if really needed. 
                            // For now, simple move is safer.
                            $filePaths[$field] = $this->fileUploadService->uploadImage(
                                $file,
                                $idMutasi,
                                "siswa{$idx}_{$field}",
                                $uploadPathKey
                            );
                        } else {
                            // PERSISTENT PATH (Resume Draft)
                            $filePaths[$field] = $file;
                        }
                    }
                    // CASE 2: UploadedFile Object (Standard)
                    elseif ($file instanceof \Illuminate\Http\UploadedFile) {
                        $mimeType = $file->getMimeType();

                        // Path resolution moved to inside if block using temp dir

                        if (in_array($mimeType, ['image/jpeg', 'image/png'])) {
                            // ATTEMPT Convert to WebP (Only if GD is available)
                            $useWebP = extension_loaded('gd');
                            $conversionSuccess = false;
                            // Use /tmp directory for Vercel/Production safety
                            $tempDir = sys_get_temp_dir();
                            $tempFilename = $this->sanitizeFilename($idMutasi) . "_siswa{$idx}_{$field}.webp";
                            $tempPath = $tempDir . '/' . $tempFilename;

                            if ($useWebP) {
                                // OPTIMIZATION: Disable Server-side WebP conversion for Vercel Performance
                                // $conversionSuccess = $this->imageService->convertToWebP($file, $tempPath);
                                $conversionSuccess = false; // Force skip conversion
                            }

                            if ($conversionSuccess && file_exists($tempPath)) {
                                // Manual upload of the converted file
                                $filename = $tempFilename;
                                $destinationPath = $uploadPathKey . '/' . $filename;

                                // Use the configured disk from service
                                $disk = $this->fileUploadService->isCloudStorage() ? 'b2' : 'private_docs';

                                try {
                                    // Use 'putFileAs' or 'put' with contents
                                    \Illuminate\Support\Facades\Storage::disk($disk)->put($destinationPath, file_get_contents($tempPath));
                                    $filePaths[$field] = $destinationPath;

                                    // Clean up temp file
                                    @unlink($tempPath);
                                } catch (\Exception $e) {
                                    Log::error("Failed to upload converted WebP: " . $e->getMessage());
                                    $conversionSuccess = false; // Trigger fallback
                                }
                            }

                            // FALLBACK if conversion failed or upload failed
                            if (!$conversionSuccess) {
                                // FALLBACK: Upload original file if GD missing or Conversion Failed
                                // Use the new uploadImage method in service
                                $filePaths[$field] = $this->fileUploadService->uploadImage(
                                    $file,
                                    $idMutasi,
                                    "siswa{$idx}_{$field}",
                                    $uploadPathKey
                                );
                            }
                        } elseif ($mimeType === 'application/pdf') {
                            // Use service for PDF
                            $filePaths[$field] = $this->fileUploadService->uploadPdf(
                                $file,
                                $idMutasi,
                                "siswa{$idx}_{$field}",
                                $this->fileUploadService->getDateBasedPath('masuk')
                            );
                        } else {
                            throw new \Exception("File Rekam Didik murid #" . ($idx + 1) . " harus berformat PDF, JPEG, atau PNG.");
                        }
                    }
                }

                DB::table('mutasi_masuk_siswa')->insert([
                    'id_mutasi' => $idMutasi,
                    'nama_murid' => $this->sanitize($siswa['nama_murid']),
                    'nisn' => $this->sanitizeNisn($siswa['nisn'] ?? null),
                    'nis' => $this->sanitize($siswa['nis'] ?? null),
                    'tempat_lahir' => $this->sanitize($siswa['tempat_lahir']),
                    'tanggal_lahir' => $siswa['tanggal_lahir'],
                    'jenis_kelamin' => $siswa['jenis_kelamin'],
                    'kelas' => $siswa['kelas'],
                    'tanggal_diterima' => $siswa['tanggal_diterima'],
                    'npsn_sekolah_asal' => $this->sanitizeNpsn($siswa['npsn_sekolah_asal'] ?? null),
                    'nama_sekolah_asal' => $this->sanitize($siswa['nama_sekolah_asal']),
                    'alamat_sekolah_asal' => $this->sanitize($siswa['alamat_sekolah_asal']),
                    'provinsi_asal' => $this->sanitize($siswa['provinsi_asal']),
                    'kabupaten_kota_asal' => $this->sanitize($siswa['kabupaten_kota_asal']),
                    'npsn_ditemukan' => ($siswa['npsn_ditemukan'] ?? '0') === '1',
                    'asal_luar_negeri' => $request->boolean("siswa.{$idx}.asal_luar_negeri"),
                    'file_surat_pindah' => $filePaths['file_surat_pindah'] ?? null,
                    'file_surat_dapodik' => $filePaths['file_surat_dapodik'] ?? null,
                    'file_rekomendasi' => $filePaths['file_rekomendasi'] ?? null,
                    'file_ijazah_sd' => $filePaths['file_ijazah_sd'] ?? null,
                    'file_rapor' => $filePaths['file_rapor'] ?? null,
                    'file_surat_penyaluran' => $filePaths['file_surat_penyaluran'] ?? null,
                    'file_rekam_didik' => $filePaths['file_rekam_didik'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            \App\Services\MutationStatisticsCache::invalidate();

            // NOTE: Auto-save sekolah ke database hanya dilakukan saat status "Selesai" oleh Dinas
            // via maybeStoreNewSchool() di completeLetter()

            // Notify Dinas users about new submission (wrapped in try-catch to not affect save)
            try {
                $newMutasi = DB::table('mutasi_masuk')->where('id_mutasi', $idMutasi)->first();
                // Skip notification if submitter is Dinas/SuperAdmin (they already know about their own submission)
                if ($newMutasi && !in_array($user->role, ['Dinas', 'SuperAdmin'])) {
                    \App\Helpers\NotificationHelper::notifyDinasNewSubmission($newMutasi, 'masuk');
                }
            } catch (\Exception $e) {
                Log::error("Telegram notification failed: " . $e->getMessage());
            }

            Log::info("Mutasi Masuk created: {$idMutasi} by {$user->username}");

            return redirect()->route('mutasi.masuk.index')
                ->with('success', "Mutasi Masuk {$idMutasi} berhasil dibuat ({$jumlahSiswa} murid)");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Mutasi Masuk Error: " . $e->getMessage());
            return back()->with('error', $this->safeErrorMessage('Gagal menyimpan data mutasi masuk', $e))->withInput();
        }
    }

    public function show(string $id)
    {
        // Sanitize ID
        $id = $this->sanitize($id);

        $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();

        if (!$mutasi) {
            abort(404);
        }

        // Authorization check
        $user = Auth::user();
        if ($user->role === 'Operator') {
            // STRICT: Operator only matches username
            if ($mutasi->pengusul !== $user->username) {
                abort(403, 'Anda tidak memiliki akses ke data ini (Bukan Pengusul).');
            }
        } elseif ($user->role === 'Sekolah') {
            // Sekolah matches NPSN or Username (Relaxed)
            if ($mutasi->npsn_sekolah_tujuan !== $user->npsn && $mutasi->pengusul !== $user->username) {
                abort(403, 'Anda tidak memiliki akses ke data ini (Bukan Data Sekolah Anda).');
            }
        } elseif (in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            // Dinas cannot view Drafts from others
            $isOwnDraft = in_array($mutasi->pengusul, [$user->username]);
            if ($mutasi->status === 'Draft' && !$isOwnDraft) {
                abort(403, 'Dinas tidak dapat melihat Draft usulan.');
            }
        } else {
            abort(403);
        }

        $siswaList = DB::table('mutasi_masuk_siswa')
            ->where('id_mutasi', $id)
            ->get();

        return view('mutasi.masuk.show', compact('mutasi', 'siswaList'));
    }

    public function verify(Request $request, string $id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403, 'Hanya Dinas yang dapat memverifikasi.');
        }

        // Validate input
        $request->validate([
            'catatan_berkas' => 'nullable|string|max:2000',
            'action' => 'required|in:approve,revision',
        ]);

        $id = $this->sanitize($id);

        $action = $request->input('action');
        $status = $action === 'approve' ? 'Disetujui Dinas' : 'Perlu Perbaikan';
        $verified = DB::transaction(function () use ($request, $id, $status) {
            $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->lockForUpdate()->first();
            if (!$mutasi) {
                abort(404);
            }

            if ($mutasi->status !== 'Menunggu Verifikasi Dinas') {
                return false;
            }

            // Save verification status for each document per murid
            $siswaList = DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->get(['id']);
            $verifikasiInput = $request->input('verifikasi', []);

            foreach ($siswaList as $idx => $siswa) {
                $siswaVerif = $verifikasiInput[$siswa->id] ?? [];

                $verifData = [
                    'verif_surat_pindah' => isset($siswaVerif["siswa_{$idx}_pindah"]) ? 1 : 0,
                    'verif_surat_dapodik' => isset($siswaVerif["siswa_{$idx}_dapodik"]) ? 1 : 0,
                    'verif_rekomendasi' => isset($siswaVerif["siswa_{$idx}_rekom"]) ? 1 : 0,
                    'verif_ijazah_sd' => isset($siswaVerif["siswa_{$idx}_ijazah"]) ? 1 : 0,
                    'verif_rapor' => isset($siswaVerif["siswa_{$idx}_rapor"]) ? 1 : 0,
                    'verif_surat_penyaluran' => isset($siswaVerif["siswa_{$idx}_penyaluran"]) ? 1 : 0,
                    'verif_rekam_didik' => isset($siswaVerif["siswa_{$idx}_rekam_didik"]) ? 1 : 0,
                    'updated_at' => now(),
                ];

                DB::table('mutasi_masuk_siswa')
                    ->where('id', $siswa->id)
                    ->update($verifData);
            }

            DB::table('mutasi_masuk')->where('id_mutasi', $id)->update([
                'catatan_berkas' => $this->sanitize($request->catatan_berkas),
                'status' => $status,
                'updated_at' => now(),
            ]);

            return true;
        });

        if (!$verified) {
            return back()->with('error', 'Status usulan sudah berubah. Silakan muat ulang halaman sebelum memverifikasi.');
        }
        \App\Services\MutationStatisticsCache::invalidate();

        // Send Telegram notification to pengusul (wrapped in try-catch)
        try {
            $updatedMutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
            if ($updatedMutasi) {
                \App\Helpers\NotificationHelper::notifyPengusulOnStatusChange(
                    $updatedMutasi,
                    'Menunggu Verifikasi Dinas',
                    $status,
                    'masuk'
                );
            }
        } catch (\Exception $e) {
            Log::error("Telegram notification failed: " . $e->getMessage());
        }

        Log::info("Mutasi Masuk {$id} verified by {$user->username}: {$status}");

        $message = $action === 'approve'
            ? "✅ Berkas disetujui! Silakan input nomor dan tanggal surat izin."
            : "📝 Data dikembalikan ke Operator/Sekolah untuk perbaikan.";

        return redirect()->route('mutasi.masuk.show', $id)->with('success', $message);
    }

    /**
     * Issue letter - input nomor dan tanggal surat izin, status -> Proses TTD
     */
    public function issueLetter(Request $request, string $id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403, 'Hanya Dinas yang dapat menerbitkan surat izin.');
        }

        $request->validate([
            'nomor_surat_izin' => 'nullable|string|max:50',
            'tanggal_surat_izin' => 'required|date',
        ]);

        $id = $this->sanitize($id);

        $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        if ($mutasi->status !== 'Disetujui Dinas') {
            return back()->with('error', 'Data harus disetujui terlebih dahulu.');
        }
        $previousStatus = $mutasi->status;

        $issued = DB::table('mutasi_masuk')->where('id_mutasi', $id)
            ->where('status', 'Disetujui Dinas')->update([
            'nomor_surat_izin' => $request->filled('nomor_surat_izin') ? $this->sanitize($request->nomor_surat_izin) : null,
            'tanggal_surat_izin' => $request->tanggal_surat_izin,
            'status' => 'Proses TTD',
            'updated_at' => now(),
        ]);

        if (!$issued) {
            return back()->with('error', 'Status usulan sudah berubah. Silakan muat ulang halaman sebelum menyiapkan surat.');
        }
        \App\Services\MutationStatisticsCache::invalidate();

        // Send Telegram notification to pengusul about Proses TTD status (wrapped in try-catch)
        try {
            $updatedMutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
            if ($updatedMutasi) {
                \App\Helpers\NotificationHelper::notifyPengusulOnStatusChange(
                    $updatedMutasi,
                    $previousStatus,
                    'Proses TTD',
                    'masuk'
                );
            }
        } catch (\Exception $e) {
            Log::error("Telegram notification failed: " . $e->getMessage());
        }

        // Auto-save logic moved to completeLetter

        Log::info("Surat Izin Mutasi Masuk {$id} siap TTD oleh {$user->username}");

        return redirect()->route('mutasi.masuk.show', $id)
            ->with('success', 'Surat izin siap dicetak dan ditandatangani. Nomor surat dapat diisi setelah tanda tangan selesai.');
    }

    /**
     * Complete letter - konfirmasi TTD selesai, status -> Selesai
     */
    public function completeLetter(Request $request, string $id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403, 'Hanya Dinas yang dapat mengkonfirmasi penandatanganan.');
        }

        $request->validate([
            'nomor_surat_izin' => 'required|string|max:50',
            'tanggal_selesai' => 'required|date',
        ]);

        $id = $this->sanitize($id);

        $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        if ($mutasi->status !== 'Proses TTD') {
            return back()->with('error', 'Status harus Proses TTD untuk mengkonfirmasi.');
        }
        $previousStatus = $mutasi->status;

        DB::table('mutasi_masuk')->where('id_mutasi', $id)->update([
            'nomor_surat_izin' => $this->sanitize($request->nomor_surat_izin),
            'tanggal_selesai' => $request->tanggal_selesai,
            'status' => 'Selesai',
            'updated_at' => now(),
        ]);
        $mutasi->nomor_surat_izin = $this->sanitize($request->nomor_surat_izin);
        $mutasi->tanggal_selesai = $request->tanggal_selesai;
        $mutasi->status = 'Selesai';
        \App\Services\MutationStatisticsCache::invalidate();

        // Build student list for message
        $siswaList = DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->get();
        $namaSiswa = $siswaList->pluck('nama_murid')->implode(', ');
        $mutasiWithSiswa = (object) array_merge((array) $mutasi, ['nama_siswa' => $namaSiswa]);

        // Auto-save New School(s) (User Request) - Save upon completion
        foreach ($siswaList as $student) {
            \App\Http\Controllers\SatuanPendidikanController::maybeStoreNewSchool([
                'npsn' => $student->npsn_sekolah_asal,
                'nama' => $student->nama_sekolah_asal,
                'alamat' => $student->alamat_sekolah_asal,
                'kota' => $student->kabupaten_kota_asal,
                'provinsi' => $student->provinsi_asal,
            ]);
        }

        // Send Telegram notification (wrapped in try-catch)
        try {
            \App\Helpers\NotificationHelper::notifyPengusulOnStatusChange(
                $mutasiWithSiswa,
                $previousStatus,
                'Selesai',
                'masuk'
            );
        } catch (\Exception $e) {
            Log::error("Telegram notification failed: " . $e->getMessage());
        }

        Log::info("Surat Izin Mutasi Masuk {$id} selesai oleh {$user->username}");

        $successMessage = "Surat Izin Mutasi Masuk selesai.\n\nNomor: {$request->nomor_surat_izin}\nTanggal Selesai: " . \Carbon\Carbon::parse($request->tanggal_selesai)->translatedFormat('d F Y');

        return redirect()->route('mutasi.masuk.show', $id)->with('success', $successMessage);
    }

    public function printLetter(Request $request, string $id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403, 'Hanya Dinas yang dapat mencetak surat.');
        }

        $id = $this->sanitize($id);

        $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        if (!$this->pdfService->canPrint($mutasi->status)) {
            return back()->with('error', 'Surat hanya dapat dicetak setelah status Proses TTD atau Selesai.');
        }

        // Use PdfService for data mapping
        $mutasiData = $this->pdfService->prepareMutasiMasukData($mutasi);
        $siswaList = $this->pdfService->getMutasiMasukSiswaList($id);
        $penandatangan = $this->pdfService->getActivePenandatangan();

        $mode = 'manual';
        $view = view('surat.izin-mutasi-masuk', [
            'mutasi' => $mutasiData,
            'siswaList' => $siswaList,
            'penandatangan' => $penandatangan,
            'mode' => $mode,
            'format' => $request->query('format'),
        ]);

        if ($request->query('format') === 'word') {
            $wordService = app(\App\Services\WordDocumentService::class);
            $filename = $wordService->mutasiMasukFilename($mutasiData, $siswaList->count());
            $path = $wordService->createMutasiMasuk($mutasiData, $siswaList, $penandatangan);

            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ])->deleteFileAfterSend(true);
        }

        return $view;
    }

    /**
     * Edit form for Dinas/SuperAdmin to modify mutasi data
     */
    public function edit(string $id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403, 'Hanya Dinas yang dapat mengedit data.');
        }

        $id = $this->sanitize($id);

        $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        $siswaList = DB::table('mutasi_masuk_siswa')
            ->where('id_mutasi', $id)
            ->get();

        return view('mutasi.masuk.edit', compact('mutasi', 'siswaList'));
    }

    /**
     * Update mutasi data - resets status to Menunggu Verifikasi Dinas
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403, 'Hanya Dinas yang dapat mengedit data.');
        }

        $id = $this->sanitize($id);

        $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        $request->validate([
            'nomor_surat' => 'required|string|max:50',
            'nama_sekolah_tujuan' => 'required|string|max:150',
            'npsn_sekolah_tujuan' => 'required|string|size:8',
        ]);

        // Update header data
        $updateData = [
            'nomor_surat' => $this->sanitize($request->nomor_surat),
            'tanggal_surat' => $request->tanggal_surat ?? $mutasi->tanggal_surat,
            'npsn_sekolah_tujuan' => $this->sanitizeNpsn($request->npsn_sekolah_tujuan),
            'nama_sekolah_tujuan' => $this->sanitize($request->nama_sekolah_tujuan),
            'alamat_sekolah_tujuan' => $this->sanitize($request->alamat_sekolah_tujuan) ?? $mutasi->alamat_sekolah_tujuan,
            'status' => 'Menunggu Verifikasi Dinas',
            'nomor_surat_izin' => null,
            'tanggal_surat_izin' => null,
            'updated_at' => now(),
        ];

        DB::table('mutasi_masuk')->where('id_mutasi', $id)->update($updateData);

        Log::info("Mutasi Masuk {$id} diupdate oleh {$user->username}, status reset ke Menunggu Verifikasi");

        return redirect()->route('mutasi.masuk.show', $id)
            ->with('success', '✅ Data berhasil diperbarui. Status dikembalikan ke Menunggu Verifikasi Dinas.');
    }

    /**
     * Revision Edit - Operator/Sekolah can edit when status is Perlu Perbaikan
     * Security: Only allowed if user is the pengusul or destination school
     */
    public function revisionEdit(string $id)
    {
        $user = Auth::user();

        $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        // Security: Strictly only the original proposer/pengusul can edit (case-insensitive)
        if (strtolower($mutasi->pengusul) !== strtolower($user->username)) {
            abort(403, 'Hanya akun pengusul yang dapat memperbaiki data ini.');
        }

        // Check Schedule (Only for non-Dinas/SuperAdmin)
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            $today = now();

            // Common Schedule Retrieval
            $dayNames = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
            $allowedDays = \App\Models\Setting::getAllowedDays();
            $allowedDayNames = !empty($allowedDays) ? implode(', ', array_map(fn($d) => $dayNames[$d] ?? '', $allowedDays)) : 'Setiap Hari';

            $startTime = \App\Models\Setting::get('working_hours_start', '08:00');
            $endTime = \App\Models\Setting::get('working_hours_end', '15:00');
            $blockData = [
                'days' => $allowedDayNames,
                'hours' => "{$startTime} - {$endTime} WIB"
            ];

            // 1. Check Holiday
            if (\App\Models\Holiday::isHoliday($today->format('Y-m-d'))) {
                return redirect()->route('mutasi.masuk.index')->with('schedule_block', $blockData);
            }

            // 2. Check Allowed Days
            if (!empty($allowedDays) && !in_array($today->dayOfWeekIso, $allowedDays)) {
                return redirect()->route('mutasi.masuk.index')->with('schedule_block', $blockData);
            }

            // 3. Check Allowed Hours
            if (\App\Models\Setting::isHourRestrictionEnabled()) {
                $currentTime = $today->format('H:i');
                if ($currentTime < $startTime || $currentTime > $endTime) {
                    return redirect()->route('mutasi.masuk.index')->with('schedule_block', $blockData);
                }
            }
        }

        // Allow edit when status is Perlu Perbaikan OR Dibatalkan
        if (!in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan'])) {
            return back()->with('error', 'Data hanya dapat diperbaiki jika status adalah Perlu Perbaikan atau Dibatalkan.');
        }

        $siswaList = DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->get();

        return view('mutasi.masuk.revision-edit', compact('mutasi', 'siswaList'));
    }

    public function revision(string $id)
    {
        $user = Auth::user();
        $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();

        if (!$mutasi)
            abort(404);

        if ($mutasi->pengusul !== $user->username) {
            abort(403);
        }

        if (!in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan'])) {
            return back()->with('error', 'Status tidak valid.');
        }

        $siswaList = DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->get();

        return view('mutasi.masuk.revision-edit', compact('mutasi', 'siswaList'));
    }

    /**
     * Revision Resubmit - Submit corrected data back to Dinas
     * Security: Only allowed if user is the pengusul and status is Perlu Perbaikan or Dibatalkan
     */
    public function revisionResubmit(Request $request, string $id)
    {
        $user = Auth::user();
        $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();

        if (!$mutasi) {
            abort(404);
        }

        // Security: Strictly only the original proposer/pengusul can resubmit
        if ($mutasi->pengusul !== $user->username) {
            abort(403, 'Hanya akun pengusul yang dapat mengirim ulang data ini.');
        }

        // Check Schedule (Only for non-Dinas/SuperAdmin)
        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            $today = now();

            // Common Schedule Retrieval
            $dayNames = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];
            $allowedDays = \App\Models\Setting::getAllowedDays();
            $allowedDayNames = !empty($allowedDays) ? implode(', ', array_map(fn($d) => $dayNames[$d] ?? '', $allowedDays)) : 'Setiap Hari';

            $startTime = \App\Models\Setting::get('working_hours_start', '08:00');
            $endTime = \App\Models\Setting::get('working_hours_end', '15:00');
            $blockData = [
                'days' => $allowedDayNames,
                'hours' => "{$startTime} - {$endTime} WIB"
            ];

            // 1. Check Holiday
            if (\App\Models\Holiday::isHoliday($today->format('Y-m-d'))) {
                return redirect()->route('mutasi.masuk.index')->with('schedule_block', $blockData);
            }

            // 2. Check Allowed Days
            if (!empty($allowedDays) && !in_array($today->dayOfWeekIso, $allowedDays)) {
                return redirect()->route('mutasi.masuk.index')->with('schedule_block', $blockData);
            }

            // 3. Check Allowed Hours
            if (\App\Models\Setting::isHourRestrictionEnabled()) {
                $currentTime = $today->format('H:i');
                if ($currentTime < $startTime || $currentTime > $endTime) {
                    return redirect()->route('mutasi.masuk.index')->with('schedule_block', $blockData);
                }
            }
        }

        if (!in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan'])) {
            return back()->with('error', 'Status harus Perlu Perbaikan atau Dibatalkan.');
        }

        // Validate Inputs with comprehensive messages
        $rules = [
            'nomor_surat' => 'required|string|max:50',
            'tanggal_surat' => 'required|date|before_or_equal:today',
            'npsn_sekolah_tujuan' => 'required|string|size:8',
            'nama_sekolah_tujuan' => 'required|string|max:150',
            'alamat_sekolah_tujuan' => 'required|string|max:500',
        ];

        $messages = [
            'nomor_surat.required' => 'Nomor Surat Permohonan wajib diisi.',
            'nomor_surat.max' => 'Nomor Surat maksimal 50 karakter.',
            'tanggal_surat.required' => 'Tanggal Surat Permohonan wajib diisi.',
            'tanggal_surat.date' => 'Format Tanggal Surat tidak valid.',
            'tanggal_surat.before_or_equal' => 'Tanggal Surat tidak boleh lebih dari hari ini.',
            'npsn_sekolah_tujuan.required' => 'NPSN Sekolah Tujuan wajib diisi.',
            'npsn_sekolah_tujuan.size' => 'NPSN Sekolah Tujuan harus tepat 8 karakter. Contoh: 20270134.',
            'nama_sekolah_tujuan.required' => 'Nama Sekolah Tujuan wajib diisi.',
            'nama_sekolah_tujuan.max' => 'Nama Sekolah Tujuan maksimal 150 karakter.',
            'alamat_sekolah_tujuan.required' => 'Alamat Sekolah Tujuan wajib diisi.',
            'alamat_sekolah_tujuan.max' => 'Alamat Sekolah Tujuan maksimal 500 karakter.',
        ];

        // File Validation Setup
        $isFileRequired = ($mutasi->status === 'Dibatalkan') ? 'required' : 'nullable';

        if ($request->hasFile('file_surat_permohonan')) {
            $rules['file_surat_permohonan'] = 'required|file|mimes:pdf|max:1024';
            $messages['file_surat_permohonan.required'] = 'File Surat Permohonan wajib diunggah.';
            $messages['file_surat_permohonan.mimes'] = 'File Surat Permohonan harus berformat PDF.';
            $messages['file_surat_permohonan.max'] = 'File Surat Permohonan maksimal 1MB.';
        } else if ($isFileRequired === 'required' && empty($mutasi->file_surat_permohonan)) {
            $rules['file_surat_permohonan'] = 'required|file|mimes:pdf|max:1024';
            $messages['file_surat_permohonan.required'] = 'File Surat Permohonan wajib diunggah (karena status sebelumnya Dibatalkan).';
            $messages['file_surat_permohonan.mimes'] = 'File Surat Permohonan harus berformat PDF.';
            $messages['file_surat_permohonan.max'] = 'File Surat Permohonan maksimal 1MB.';
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            // 1. Update Header
            $updateData = [
                'nomor_surat' => $this->sanitize($request->nomor_surat),
                'tanggal_surat' => $request->tanggal_surat,
                'npsn_sekolah_tujuan' => $this->sanitizeNpsn($request->npsn_sekolah_tujuan),
                'nama_sekolah_tujuan' => $this->sanitize($request->nama_sekolah_tujuan),
                'alamat_sekolah_tujuan' => $this->sanitize($request->alamat_sekolah_tujuan),
                'status' => 'Menunggu Verifikasi Dinas', // Reset Status
                'catatan_berkas' => null, // Clear notes
                'updated_at' => now(),
            ];

            if ($this->supportsSubmissionTimeline()) {
                $updateData['last_submitted_at'] = now();
                $updateData['revision_count'] = DB::raw('revision_count + 1');
            }

            // Helper for deletion
            $deleteOldFile = function ($oldPath) {
                if (empty($oldPath))
                    return;

                // Try Secure Path
                $securePath = storage_path('app/dokumen_rahasia/' . $oldPath);
                if (!file_exists($securePath) && str_starts_with($oldPath, 'dokumen/')) {
                    $securePath = storage_path('app/dokumen_rahasia/' . substr($oldPath, 8));
                }

                // Try Public Path (Legacy)
                $publicPath = public_path('storage/' . $oldPath);

                if (file_exists($securePath)) {
                    @unlink($securePath);
                } elseif (file_exists($publicPath)) {
                    @unlink($publicPath);
                }
            };

            // Handle new Surat Permohonan (Secure Upload)
            if ($request->hasFile('file_surat_permohonan')) {
                // Delete old
                $deleteOldFile($mutasi->file_surat_permohonan);

                // Upload new secure
                $newPath = $this->fileUploadService->uploadSuratPermohonan($request, $id);
                $updateData['file_surat_permohonan'] = $newPath;
            }

            DB::table('mutasi_masuk')->where('id_mutasi', $id)->update($updateData);

            // 2. Update Students
            $siswaData = $request->input('siswa', []);
            // Need relative date path for direct usage if needed, but services handle it.
            $uploadPathKey = $this->fileUploadService->getRelativeDateBasedPath('masuk');

            foreach ($siswaData as $siswaId => $data) {
                // Ensure murid exists & belongs to this mutasi
                $existingSiswa = DB::table('mutasi_masuk_siswa')->where('id', $siswaId)->where('id_mutasi', $id)->first();
                if (!$existingSiswa)
                    continue;

                $siswaUpdate = [
                    'nama_murid' => $this->sanitize($data['nama_murid']),
                    'nisn' => $this->sanitizeNisn($data['nisn']),
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'kelas' => $data['kelas'],
                    'tanggal_diterima' => $data['tanggal_diterima'],
                    'updated_at' => now()
                ];

                // Handle PDF files (Standard)
                $fileFields = ['file_surat_pindah', 'file_surat_dapodik', 'file_rekomendasi', 'file_ijazah_sd', 'file_rapor', 'file_surat_penyaluran'];

                foreach ($fileFields as $field) {
                    $inputKey = "siswa.{$siswaId}.{$field}";
                    if ($request->hasFile($inputKey)) {
                        // Delete old
                        $deleteOldFile($existingSiswa->$field);

                        // Upload new secure
                        // Note: fileUploadService->uploadPdf expects UploadedFile
                        $file = $request->file($inputKey);
                        // Using a manual call to uploadPdf to handle the specific naming convention for existing murid ID
                        // Naming: {idMutasi}_siswa{idx}_{field} OR we can stick to {idMutasi}_{siswaID}_{field} for revision validity
                        // The original uploader uses index. Here we have ID. Let's use ID to be safe and specific.

                        $subDir = $this->fileUploadService->getDateBasedPath('masuk');
                        $newPath = $this->fileUploadService->uploadPdf(
                            $file,
                            $id,
                            "siswa_{$siswaId}_{$field}_rev", // Uniform naming
                            $subDir
                        );
                        $siswaUpdate[$field] = $newPath;
                    }
                }

                // Handle Rekam Didik (Image/PDF)
                $rdKey = "siswa.{$siswaId}.file_rekam_didik";
                if ($request->hasFile($rdKey)) {
                    // Delete old
                    $deleteOldFile($existingSiswa->file_rekam_didik);

                    $file = $request->file($rdKey);
                    $mimeType = $file->getMimeType();
                    if (in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'])) {
                        // Use /tmp for Vercel/Production safety
                        $tempDir = sys_get_temp_dir();
                        $tempFilename = $this->sanitizeFilename($id) . "_siswa_{$siswaId}_rekam_rev.webp";
                        $tempPath = $tempDir . '/' . $tempFilename;
                        $conversionSuccess = false;

                        if ($this->imageService->convertToWebP($file, $tempPath)) {
                            $conversionSuccess = true;
                        }

                        if ($conversionSuccess && file_exists($tempPath)) {
                            // Manual upload
                            $filename = $tempFilename;
                            $destinationPath = $uploadPathKey . '/' . $filename;
                            $disk = $this->fileUploadService->isCloudStorage() ? 'b2' : 'private_docs';

                            try {
                                \Illuminate\Support\Facades\Storage::disk($disk)->put($destinationPath, file_get_contents($tempPath));
                                $siswaUpdate['file_rekam_didik'] = $destinationPath;
                                @unlink($tempPath);
                            } catch (\Exception $e) {
                                Log::error("Failed to upload converted WebP (Rev): " . $e->getMessage());
                                $conversionSuccess = false;
                            }
                        }

                        if (!$conversionSuccess) {
                            // Fallback: Upload Original
                            $siswaUpdate['file_rekam_didik'] = $this->fileUploadService->uploadImage(
                                $file,
                                $id,
                                "siswa_{$siswaId}_rekam_rev",
                                $uploadPathKey
                            );
                        }
                    } elseif ($mimeType === 'application/pdf') {
                        $subDir = $this->fileUploadService->getDateBasedPath('masuk');
                        $newPath = $this->fileUploadService->uploadPdf(
                            $file,
                            $id,
                            "siswa_{$siswaId}_rekam_didik_rev",
                            $subDir
                        );
                        $siswaUpdate['file_rekam_didik'] = $newPath;
                    }
                }

                DB::table('mutasi_masuk_siswa')
                    ->where('id', $siswaId)
                    ->update($siswaUpdate);
            }

            DB::commit();

            // Notify Dinas
            $updatedMutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
            if ($updatedMutasi) {
                \App\Helpers\NotificationHelper::notifyDinasNewSubmission($updatedMutasi, 'masuk');
            }

            return redirect()->route('mutasi.masuk.show', $id)->with('success', 'Data perbaikan berhasil dikirim ulang (File lama telah dibersihkan).');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan perbaikan: ' . $e->getMessage());
        }
    }

    public function lookupNpsn(string $npsn)
    {
        // Sanitize NPSN - only digits
        $npsn = preg_replace('/[^0-9]/', '', $npsn);

        if (strlen($npsn) !== 8) {
            return response()->json([
                'success' => false,
                'found' => false,
                'message' => 'NPSN tidak valid (harus 8 digit)',
                'data' => null
            ]);
        }

        try {
            $satpen = DB::table('satuan_pendidikan')
                ->where('npsn', $npsn)
                ->first();

            if ($satpen) {
                // Determine full address
                $alamat = $satpen->alamat_lengkap;
                if (empty($alamat)) {
                    $parts = array_filter([
                        $satpen->alamat_jalan ?? '',
                        $satpen->kelurahan ? "Kel. {$satpen->kelurahan}" : '',
                        $satpen->kecamatan ? "Kec. {$satpen->kecamatan}" : '',
                        $satpen->kota ?? '',
                        $satpen->provinsi ?? '',
                    ]);
                    $alamat = implode(', ', $parts);
                }

                return response()->json([
                    'success' => true,
                    'found' => true,
                    'data' => [
                        'npsn' => $satpen->npsn,
                        'nama' => $satpen->nama,
                        'alamat_lengkap' => $alamat,
                        'kota' => $satpen->kota,
                        'provinsi' => $satpen->provinsi,
                    ]
                ]);
            }

            return response()->json([
                'success' => true, // Request success, but data not found
                'found' => false,
                'message' => 'NPSN tidak ditemukan',
                'data' => null
            ]);
        } catch (\Exception $e) {
            Log::error('NPSN Lookup Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'found' => false,
                'message' => 'Terjadi kesalahan sistem',
                'data' => null
            ]);
        }
    }


    // ======================================================================
    // DRAFT API METHODS (Auto-Save Feature)
    // ======================================================================

    /**
     * Create a new draft (header only).
     * Called when user starts filling the form.
     */
    public function createDraft(Request $request)
    {
        $user = Auth::user();

        // Minimal validation for draft creation
        $validator = Validator::make($request->all(), [
            'jumlah_siswa' => 'required|integer|min:1|max:20',
            'npsn_sekolah_tujuan' => 'required|string|size:8',
            'nama_sekolah_tujuan' => 'required|string|max:150',
            'alamat_sekolah_tujuan' => 'required|string|max:500',
            'file_surat_permohonan' => $request->hasFile('file_surat_permohonan')
                ? 'sometimes|file|mimes:pdf|max:1024'
                : 'sometimes|string|max:255|starts_with:temp_uploads/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $idMutasi = $this->mutasiService->generateMutasiMasukId();

            $fileSuratPermohonan = null;
            if ($request->hasFile('file_surat_permohonan') || $request->filled('file_surat_permohonan')) {
                // uploadSuratPermohonan menangani UploadedFile maupun path upload
                // sementara, memvalidasi kepemilikan, lalu mempromosikannya.
                $fileSuratPermohonan = $this->fileUploadService->uploadSuratPermohonan($request, $idMutasi);
            }

            // Insert header as Draft
            DB::table('mutasi_masuk')->insert([
                'id_mutasi' => $idMutasi,
                'tanggal_pengajuan' => now(),
                'jumlah_siswa' => (int) $request->jumlah_siswa,
                'nomor_surat' => $this->sanitize($request->nomor_surat ?? ''),
                'tanggal_surat' => $request->tanggal_surat ?: self::DRAFT_EMPTY_DATE,
                'file_surat_permohonan' => $fileSuratPermohonan,
                'npsn_sekolah_tujuan' => $this->sanitizeNpsn($request->npsn_sekolah_tujuan),
                'nama_sekolah_tujuan' => $this->sanitize($request->nama_sekolah_tujuan),
                'alamat_sekolah_tujuan' => $this->sanitize($request->alamat_sekolah_tujuan),
                'status' => 'Draft',
                'pengusul' => $user->username,
                // Mode entri Dinas adalah atribut otorisasi. Jangan percaya flag
                // dari browser karena dapat dimanipulasi oleh pengguna biasa.
                'is_dinas_entry' => in_array($user->role, ['Dinas', 'SuperAdmin'], true) ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("Draft created: {$idMutasi} by {$user->username}");

            return response()->json([
                'success' => true,
                'message' => 'Draft berhasil dibuat',
                'id_mutasi' => $idMutasi,
                // Frontend perlu mengetahui bahwa upload sementara sudah
                // dipromosikan agar path lama tidak dikirim untuk kedua kalinya.
                'file_surat_permohonan' => $fileSuratPermohonan,
            ]);

        } catch (\Exception $e) {
            Log::error("Create Draft Error: " . $e->getMessage());
            return $this->safeJsonError('Gagal membuat draft', $e);
        }
    }

    /**
     * Update draft header (e.g. jumlah_siswa) via AJAX
     */
    public function updateDraftHeader(Request $request, string $id)
    {
        $user = Auth::user();
        $draft = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();

        if (!$draft || $draft->status !== 'Draft') {
            return response()->json(['success' => false, 'message' => 'Draft tidak valid'], 404);
        }

        if ($draft->pengusul !== $user->username && !in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $validator = Validator::make($request->all(), [
            'jumlah_siswa' => 'sometimes|integer|min:1|max:20',
            'nomor_surat' => 'sometimes|nullable|string|max:50',
            'tanggal_surat' => 'sometimes|nullable|date',
            'npsn_sekolah_tujuan' => 'sometimes|string|size:8',
            'nama_sekolah_tujuan' => 'sometimes|string|max:150',
            'alamat_sekolah_tujuan' => 'sometimes|string|max:500',
            'file_surat_permohonan' => $request->hasFile('file_surat_permohonan')
                ? 'sometimes|file|mimes:pdf|max:1024'
                : 'sometimes|string|max:255|starts_with:temp_uploads/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updateData = [];
            if ($request->has('jumlah_siswa')) {
                $updateData['jumlah_siswa'] = (int) $request->jumlah_siswa;
            }
            // Add other header fields if needed
            if ($request->has('nomor_surat'))
                $updateData['nomor_surat'] = $this->sanitize($request->nomor_surat);
            if ($request->has('tanggal_surat'))
                $updateData['tanggal_surat'] = $request->tanggal_surat ?: self::DRAFT_EMPTY_DATE;
            if ($request->has('npsn_sekolah_tujuan'))
                $updateData['npsn_sekolah_tujuan'] = $this->sanitizeNpsn($request->npsn_sekolah_tujuan);
            if ($request->has('nama_sekolah_tujuan'))
                $updateData['nama_sekolah_tujuan'] = $this->sanitize($request->nama_sekolah_tujuan);
            if ($request->has('alamat_sekolah_tujuan'))
                $updateData['alamat_sekolah_tujuan'] = $this->sanitize($request->alamat_sekolah_tujuan);

            // Handle Header File Update
            if ($request->hasFile('file_surat_permohonan') || $request->filled('file_surat_permohonan')) {
                $updateData['file_surat_permohonan'] = $this->fileUploadService->uploadSuratPermohonan($request, $id);
            }

            if (!empty($updateData)) {
                $updateData['updated_at'] = now();
                DB::table('mutasi_masuk')->where('id_mutasi', $id)->update($updateData);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error("Update Draft Header Error: " . $e->getMessage());
            return $this->safeJsonError('Gagal memperbarui draft', $e);
        }
    }

    /**
     * Add or update a single student to an existing draft.
     * Called via AJAX when user switches tabs.
     */
    public function addStudentToDraft(Request $request, string $id)
    {
        $user = Auth::user();

        // Verify draft exists and belongs to user
        $draft = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
        if (!$draft) {
            return response()->json(['success' => false, 'message' => 'Draft tidak ditemukan'], 404);
        }
        if ($draft->status !== 'Draft') {
            return response()->json(['success' => false, 'message' => 'Hanya draft yang bisa diubah'], 400);
        }
        if ($draft->pengusul !== $user->username && !in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        if ($request->boolean('asal_luar_negeri')) {
            $request->merge([
                'provinsi_asal' => 'Luar Negeri',
                'kabupaten_kota_asal' => 'Luar Negeri',
            ]);
        }

        $studentRules = [
            'student_index' => 'required|integer|min:0|max:19',
            'nama_murid' => 'required|string|max:100',
            'nisn' => [$request->boolean('asal_luar_negeri') ? 'nullable' : 'required', 'string', 'regex:/^\d{10}$/'],
            'nis' => 'nullable|string|max:20',
            'tempat_lahir' => 'required|string|max:50',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'kelas' => 'required|in:VII,VIII,IX',
            'tanggal_diterima' => 'required|date',
            'npsn_sekolah_asal' => 'nullable|string|size:8',
            'nama_sekolah_asal' => 'required|string|max:150',
            'alamat_sekolah_asal' => 'required|string|max:1000',
            'provinsi_asal' => 'required|string|max:100',
            'kabupaten_kota_asal' => 'required|string|max:100',
            'npsn_ditemukan' => 'nullable|boolean',
            'asal_luar_negeri' => 'nullable|boolean',
        ];

        foreach (['file_surat_pindah', 'file_surat_dapodik', 'file_rekomendasi', 'file_ijazah_sd', 'file_surat_penyaluran'] as $field) {
            $studentRules[$field] = $request->hasFile($field)
                ? 'nullable|file|mimes:pdf|max:1024'
                : 'nullable|string|max:255';
        }
        $studentRules['file_rapor'] = $request->hasFile('file_rapor')
            ? 'nullable|file|mimes:pdf|max:2048'
            : 'nullable|string|max:255';
        $studentRules['file_rekam_didik'] = $request->hasFile('file_rekam_didik')
            ? 'nullable|file|mimes:pdf,jpg,jpeg,png|max:1024'
            : 'nullable|string|max:255';

        $validator = Validator::make($request->all(), $studentRules, [
            'required' => ':attribute wajib diisi untuk menyimpan data murid ke draft.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'in' => ':attribute yang dipilih tidak valid.',
            'regex' => ':attribute harus terdiri dari 10 digit angka.',
            'size' => ':attribute harus terdiri dari :size karakter.',
            'max.string' => ':attribute terlalu panjang (maksimal :max karakter).',
            'max.file' => 'Ukuran :attribute melebihi batas yang diizinkan.',
            'mimes' => 'Format :attribute harus berupa :values.',
        ], [
            'student_index' => 'Urutan murid',
            'nama_murid' => 'Nama murid',
            'nisn' => 'NISN',
            'nis' => 'NIS/NIPD',
            'tempat_lahir' => 'Tempat lahir',
            'tanggal_lahir' => 'Tanggal lahir',
            'jenis_kelamin' => 'Jenis kelamin',
            'kelas' => 'Kelas',
            'tanggal_diterima' => 'Tanggal diterima di sekolah',
            'npsn_sekolah_asal' => 'NPSN sekolah asal',
            'nama_sekolah_asal' => 'Nama sekolah asal',
            'alamat_sekolah_asal' => 'Alamat sekolah asal',
            'provinsi_asal' => 'Provinsi asal',
            'kabupaten_kota_asal' => 'Kabupaten/kota asal',
            'file_surat_pindah' => 'Surat pindah',
            'file_surat_dapodik' => 'Surat mutasi Dapodik/EMIS',
            'file_rekomendasi' => 'Rekomendasi Dinas/Kemenag',
            'file_ijazah_sd' => 'Ijazah SD',
            'file_rapor' => 'Rapor',
            'file_surat_penyaluran' => 'Surat penyaluran',
            'file_rekam_didik' => 'Rekam didik',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data murid belum lengkap atau belum valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $studentIndex = (int) $request->input('student_index', 0);
        $requestNisn = $this->sanitizeNisn($request->nisn);

        try {
            // FIX: Safer student lookup - use NISN as primary identifier instead of unreliable skip()
            // This prevents file paths being saved to wrong student when database order differs from form order
            $existingStudent = null;

            // PRIORITY 1: Find by NISN (most reliable - NISN is unique per student)
            if (!empty($requestNisn)) {
                $existingStudent = DB::table('mutasi_masuk_siswa')
                    ->where('id_mutasi', $id)
                    ->where('nisn', $requestNisn)
                    ->first();

                if ($existingStudent) {
                    Log::info("Found existing student by NISN: {$requestNisn} for draft {$id}");
                }
            }

            // PRIORITY 2: Fallback to index-based lookup ONLY if NISN not found and this is a new student
            if (!$existingStudent) {
                // Get all students for this draft
                $allStudents = DB::table('mutasi_masuk_siswa')
                    ->where('id_mutasi', $id)
                    ->orderBy('id')
                    ->get();

                // If student count matches or exceeds the index, get by position
                if ($allStudents->count() > $studentIndex) {
                    $potentialStudent = $allStudents->get($studentIndex);

                    // SAFETY CHECK: Only use if NISN is empty (new record) or matches
                    if ($potentialStudent) {
                        if (empty($potentialStudent->nisn) || empty($requestNisn) || $potentialStudent->nisn === $requestNisn) {
                            $existingStudent = $potentialStudent;
                        } else {
                            // NISN mismatch - likely a bug, log warning and treat as new insert
                            Log::warning("NISN mismatch in addStudentToDraft: db={$potentialStudent->nisn}, request={$requestNisn}. Treating as new record.");
                        }
                    }
                }
            }

            // Generate unique identifier for file naming (use DB id if exists, otherwise index + timestamp)
            $studentFileId = $existingStudent ? $existingStudent->id : "new{$studentIndex}_" . time();

            // Helper for deletion - supports both local and B2 storage
            $isCloud = $this->fileUploadService->isCloudStorage();
            $deleteOldFile = function ($oldPath) use ($isCloud) {
                if (empty($oldPath))
                    return;

                try {
                    if ($isCloud) {
                        // B2 Cloud Storage
                        $disk = str_contains($oldPath, 'temp_uploads/') ? 'b2' : 'b2';
                        if (Storage::disk($disk)->exists($oldPath)) {
                            Storage::disk($disk)->delete($oldPath);
                            Log::info("Deleted old B2 file: {$oldPath}");
                        }
                    } else {
                        // Local Storage
                        // Try Secure Path
                        $securePath = storage_path('app/dokumen_rahasia/' . ltrim($oldPath, '/'));
                        // Fix for inconsistent path storage (some have 'dokumen/' prefix in DB)
                        if (!file_exists($securePath) && str_starts_with($oldPath, 'dokumen/')) {
                            $securePath = storage_path('app/dokumen_rahasia/' . substr($oldPath, 8));
                        }

                        if (file_exists($securePath)) {
                            @unlink($securePath);
                            Log::info("Deleted old local file: {$oldPath}");
                        }

                        // Also try temp_uploads for local
                        if (str_contains($oldPath, 'temp_uploads/')) {
                            $tempPath = storage_path('app/' . $oldPath);
                            if (file_exists($tempPath)) {
                                @unlink($tempPath);
                                Log::info("Deleted temp file: {$oldPath}");
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to delete old file {$oldPath}: " . $e->getMessage());
                }
            };

            // Build file paths array and DELETE old files if replaced
            $filePaths = [];
            $uploadPathKey = $this->fileUploadService->getDateBasedPath('masuk');
            $isDinasEntry = (bool) $draft->is_dinas_entry;

            // Upload files if provided (not Dinas entry)
            if (!$isDinasEntry) {
                // Determine Storage Settings
                $isCloud = $this->fileUploadService->isCloudStorage();
                $destDisk = $isCloud ? 'b2' : 'private_docs'; // private_docs = storage/app/dokumen_rahasia

                $fileFields = [
                    'file_surat_pindah',
                    'file_surat_dapodik',
                    'file_rekomendasi',
                    'file_ijazah_sd',
                    'file_rapor',
                    'file_surat_penyaluran'
                ];

                foreach ($fileFields as $field) {
                    // Check for Standard Upload
                    if ($request->hasFile($field)) {
                        // DELETE OLD FILE IF EXISTS
                        if ($existingStudent && !empty($existingStudent->$field)) {
                            $deleteOldFile($existingStudent->$field);
                        }

                        $file = $request->file($field);
                        $filePaths[$field] = $this->fileUploadService->uploadPdf(
                            $file,
                            $id,
                            "s{$studentFileId}_{$field}",
                            $uploadPathKey
                        );
                    }
                    // Check for Async/Temp Upload (String Path)
                    elseif ($request->filled($field) && is_string($request->input($field))) {
                        $tempPath = $request->input($field);
                        if (str_contains($tempPath, 'temp_uploads/')) {
                            // DELETE OLD FILE IF EXISTS
                            if ($existingStudent && !empty($existingStudent->$field)) {
                                $deleteOldFile($existingStudent->$field);
                            }

                            $filePaths[$field] = $this->fileUploadService->uploadPdf(
                                $tempPath,
                                $id,
                                "s{$studentFileId}_{$field}",
                                $uploadPathKey
                            );
                        }
                    }
                }

                // Handle Rekam Didik (Image/PDF)
                // Similar logic for Rekam Didik which supports images
                $field = 'file_rekam_didik';
                if ($request->hasFile($field)) {
                    // DELETE OLD FILE
                    if ($existingStudent && !empty($existingStudent->file_rekam_didik)) {
                        $deleteOldFile($existingStudent->file_rekam_didik);
                    }

                    $file = $request->file($field);
                    $mimeType = $file->getMimeType();

                    if (in_array($mimeType, ['image/jpeg', 'image/png'])) {
                        // Try WebP conversion
                        $tempDir = sys_get_temp_dir();
                        $tempFilename = $this->sanitizeFilename($id) . "_s{$studentFileId}_file_rekam_didik.webp";
                        $tempPath = $tempDir . '/' . $tempFilename;
                        $conversionSuccess = false;

                        if (extension_loaded('gd')) {
                            $conversionSuccess = $this->imageService->convertToWebP($file, $tempPath);
                        }

                        if ($conversionSuccess && file_exists($tempPath)) {
                            $destinationPath = $uploadPathKey . '/' . $tempFilename;
                            $disk = $destDisk; // Use the same Dest Disk
                            \Illuminate\Support\Facades\Storage::disk($disk)->put($destinationPath, file_get_contents($tempPath));
                            $filePaths['file_rekam_didik'] = $destinationPath;
                            @unlink($tempPath);
                        } else {
                            $filePaths['file_rekam_didik'] = $this->fileUploadService->uploadImage(
                                $file,
                                $id,
                                "s{$studentFileId}_file_rekam_didik",
                                $uploadPathKey
                            );
                        }
                    } elseif ($mimeType === 'application/pdf') {
                        $filePaths['file_rekam_didik'] = $this->fileUploadService->uploadPdf(
                            $file,
                            $id,
                            "s{$studentFileId}_file_rekam_didik",
                            $uploadPathKey
                        );
                    }
                } elseif ($request->filled($field) && is_string($request->input($field))) {
                    $tempPath = $request->input($field);
                    if (str_contains($tempPath, 'temp_uploads/')) {
                        if ($existingStudent && !empty($existingStudent->file_rekam_didik)) {
                            $deleteOldFile($existingStudent->file_rekam_didik);
                        }

                        $extension = strtolower(pathinfo($tempPath, PATHINFO_EXTENSION));
                        $filePaths[$field] = $extension === 'pdf'
                            ? $this->fileUploadService->uploadPdf(
                                $tempPath,
                                $id,
                                "s{$studentFileId}_{$field}",
                                $uploadPathKey
                            )
                            : $this->fileUploadService->uploadImage(
                                $tempPath,
                                $id,
                                "s{$studentFileId}_{$field}",
                                $uploadPathKey
                            );
                    }
                }
            }

            $studentData = [
                'id_mutasi' => $id,
                'nama_murid' => $this->sanitize($request->nama_murid),
                'nisn' => $this->sanitizeNisn($request->nisn),
                'nis' => $this->sanitize($request->nis ?? null),
                'tempat_lahir' => $this->sanitize($request->tempat_lahir),
                'tanggal_lahir' => $request->tanggal_lahir,
                'jenis_kelamin' => $request->jenis_kelamin,
                'kelas' => $request->kelas,
                'tanggal_diterima' => $request->tanggal_diterima,
                'npsn_sekolah_asal' => $this->sanitizeNpsn($request->npsn_sekolah_asal ?? null),
                'nama_sekolah_asal' => $this->sanitize($request->nama_sekolah_asal),
                'alamat_sekolah_asal' => $this->sanitize($request->alamat_sekolah_asal),
                'provinsi_asal' => $this->sanitize($request->provinsi_asal),
                'kabupaten_kota_asal' => $this->sanitize($request->kabupaten_kota_asal),
                'npsn_ditemukan' => ($request->npsn_ditemukan ?? '0') === '1',
                'asal_luar_negeri' => $request->boolean('asal_luar_negeri'),
                // Dinas confirmation checkboxes
                'has_surat_pindah' => $request->boolean('has_surat_pindah'),
                'has_surat_dapodik' => $request->boolean('has_surat_dapodik'),
                'has_rekomendasi' => $request->boolean('has_rekomendasi'),
                'has_ijazah_sd' => $request->boolean('has_ijazah_sd'),
                'has_rapor' => $request->boolean('has_rapor'),
                'has_surat_penyaluran' => $request->boolean('has_surat_penyaluran'),
                'updated_at' => now(),
            ];

            // Merge file paths
            foreach ($filePaths as $key => $path) {
                $studentData[$key] = $path;
            }

            if ($existingStudent) {
                // Update existing
                DB::table('mutasi_masuk_siswa')->where('id', $existingStudent->id)->update($studentData);
                $actionMsg = 'diupdate';
            } else {
                // Insert new
                $studentData['created_at'] = now();
                DB::table('mutasi_masuk_siswa')->insert($studentData);
                $actionMsg = 'disimpan';
            }

            // Only update timestamp, NOT jumlah_siswa (preserve user's intended count)
            DB::table('mutasi_masuk')->where('id_mutasi', $id)->update([
                'updated_at' => now()
            ]);

            $actualCount = DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->count();
            Log::info("Student {$actionMsg} to draft {$id}: index {$studentIndex}");

            return response()->json([
                'success' => true,
                'message' => 'Murid #' . ($studentIndex + 1) . ' berhasil ' . $actionMsg,
                'student_count' => $actualCount
            ]);

        } catch (\Exception $e) {
            Log::error("Add Student to Draft Error: " . $e->getMessage());
            return $this->safeJsonError('Gagal menyimpan murid', $e);
        }
    }

    /**
     * Finalize a draft - submit it for verification.
     */
    public function finalizeDraft(Request $request, string $id)
    {
        $user = Auth::user();

        $draft = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
        if (!$draft) {
            return response()->json(['success' => false, 'message' => 'Draft tidak ditemukan'], 404);
        }
        if ($draft->status !== 'Draft') {
            return response()->json(['success' => false, 'message' => 'Usulan sudah disubmit sebelumnya'], 400);
        }
        if ($draft->pengusul !== $user->username && !in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        // Verify at least one student exists
        $studentCount = DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->count();
        if ($studentCount < 1) {
            return response()->json(['success' => false, 'message' => 'Minimal 1 murid harus diisi'], 400);
        }

        try {
            // ============================================================
            // TEMP FILE CLEANUP: Move temp files to permanent and delete temp
            // ============================================================
            $uploadPathKey = $this->fileUploadService->getDateBasedPath('masuk');
            $draftOwnerId = DB::table('users')->where('username', $draft->pengusul)->value('id');
            if (! $draftOwnerId) {
                throw new \RuntimeException('Pemilik draft tidak ditemukan.');
            }

            // Validate ownership and metadata before promoting each temporary upload.
            $moveAndCleanTemp = function ($tempPath, $filenamePrefix) use ($uploadPathKey, $id, $draftOwnerId) {
                if (empty($tempPath) || !str_contains($tempPath, 'temp_uploads/')) {
                    return $tempPath; // Not a temp file, return as-is
                }

                $ext = pathinfo($tempPath, PATHINFO_EXTENSION);
                $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
                $newFilename = "{$safeId}_{$filenamePrefix}.{$ext}";
                $permanentPath = $uploadPathKey . '/' . $newFilename;

                return $this->fileUploadService->promoteTemporaryUpload(
                    $tempPath,
                    $permanentPath,
                    ownerId: $draftOwnerId,
                );
            };

            // Process header file (surat_permohonan)
            if ($draft->file_surat_permohonan && str_contains($draft->file_surat_permohonan, 'temp_uploads/')) {
                $newPath = $moveAndCleanTemp($draft->file_surat_permohonan, 'surat_permohonan');
                if ($newPath !== $draft->file_surat_permohonan) {
                    DB::table('mutasi_masuk')->where('id_mutasi', $id)->update(['file_surat_permohonan' => $newPath]);
                }
            }

            // Process student files
            $students = DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->get();
            $fileColumns = [
                'file_surat_pindah',
                'file_surat_dapodik',
                'file_rekomendasi',
                'file_ijazah_sd',
                'file_rapor',
                'file_surat_penyaluran',
                'file_rekam_didik'
            ];

            foreach ($students as $index => $student) {
                $updateData = [];
                foreach ($fileColumns as $col) {
                    if (!empty($student->$col) && str_contains($student->$col, 'temp_uploads/')) {
                        $newPath = $moveAndCleanTemp($student->$col, "siswa{$index}_{$col}");
                        if ($newPath !== $student->$col) {
                            $updateData[$col] = $newPath;
                        }
                    }
                }
                if (!empty($updateData)) {
                    DB::table('mutasi_masuk_siswa')->where('id', $student->id)->update($updateData);
                }
            }

            // Update status
            $newStatus = in_array($user->role, ['Dinas', 'SuperAdmin'])
                ? 'Disetujui Dinas'
                : 'Menunggu Verifikasi Dinas';

            $headerUpdate = [
                'status' => $newStatus,
                'jumlah_siswa' => $studentCount,
                'tanggal_pengajuan' => now(),
                'updated_at' => now()
            ];

            if ($this->supportsSubmissionTimeline()) {
                $headerUpdate['last_submitted_at'] = now();
            }

            DB::table('mutasi_masuk')->where('id_mutasi', $id)->update($headerUpdate);

            // Notify Dinas (if not Dinas submitter)
            if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
                try {
                    $updatedDraft = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
                    \App\Helpers\NotificationHelper::notifyDinasNewSubmission($updatedDraft, 'masuk');
                } catch (\Exception $e) {
                    Log::error("Telegram notification failed: " . $e->getMessage());
                }
            }

            Log::info("Draft finalized: {$id} by {$user->username}");

            return response()->json([
                'success' => true,
                'message' => "Mutasi Masuk {$id} berhasil dikirim ({$studentCount} murid)",
                'redirect' => route('mutasi.masuk.index')
            ]);

        } catch (\Exception $e) {
            Log::error("Finalize Draft Error: " . $e->getMessage());
            return $this->safeJsonError('Gagal mengirim pengajuan', $e);
        }
    }

    /**
     * Prefill a mutasi masuk student from a completed outgoing mutation whose
     * origin is an eligible Kota Bogor school.
     */
    public function lookupStudentByNisn(string $nisn)
    {
        $nisn = preg_replace('/[^0-9]/', '', $nisn);

        if (strlen($nisn) !== 10) {
            return response()->json([
                'success' => false,
                'found' => false,
                'message' => 'NISN tidak valid (harus 10 digit).',
                'data' => null,
            ], 422);
        }

        try {
            $query = DB::table('mutasi_keluar as mk')
                ->join('satuan_pendidikan as sp', 'sp.npsn', '=', 'mk.npsn_sekolah_asal')
                ->where('mk.nisn', $nisn)
                ->where('mk.status', 'Selesai')
                ->where('sp.is_local', true)
                ->whereRaw('UPPER(TRIM(sp.kota)) = ?', ['KOTA BOGOR']);

            if (Schema::hasColumn('mutasi_keluar', 'deleted_at')) {
                $query->whereNull('mk.deleted_at');
            }

            $student = $query
                ->select([
                    'mk.id_mutasi',
                    'mk.status',
                    'mk.nama_murid',
                    'mk.nisn',
                    'mk.nis',
                    'mk.tempat_lahir',
                    'mk.tanggal_lahir',
                    'mk.jenis_kelamin',
                    'mk.kelas',
                    'mk.npsn_sekolah_asal',
                    'sp.nama as nama_sekolah_asal',
                    'sp.alamat_lengkap as alamat_sekolah_asal',
                    'sp.alamat_jalan',
                    'sp.kelurahan',
                    'sp.kecamatan',
                    'sp.kota as kabupaten_kota_asal',
                    'sp.provinsi as provinsi_asal',
                ])
                ->orderByDesc('mk.tanggal_pengajuan')
                ->orderByDesc('mk.created_at')
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => true,
                    'found' => false,
                    'message' => 'NISN belum ditemukan pada mutasi keluar Kota Bogor yang berstatus Selesai.',
                    'data' => null,
                ]);
            }

            $address = $student->alamat_sekolah_asal;
            if (empty($address)) {
                $address = implode(', ', array_filter([
                    $student->alamat_jalan,
                    $student->kelurahan ? "Kel. {$student->kelurahan}" : null,
                    $student->kecamatan ? "Kec. {$student->kecamatan}" : null,
                    $student->kabupaten_kota_asal,
                    $student->provinsi_asal,
                ]));
            }

            return response()->json([
                'success' => true,
                'found' => true,
                'message' => 'Data murid ditemukan dan dapat digunakan sebagai prefill.',
                'data' => [
                    'source_id' => $student->id_mutasi,
                    'source_status' => $student->status,
                    'nama_murid' => $student->nama_murid,
                    'nisn' => $student->nisn,
                    'nis' => $student->nis,
                    'tempat_lahir' => $student->tempat_lahir,
                    'tanggal_lahir' => $student->tanggal_lahir,
                    'jenis_kelamin' => $student->jenis_kelamin,
                    'kelas' => $student->kelas,
                    'npsn_sekolah_asal' => $student->npsn_sekolah_asal,
                    'nama_sekolah_asal' => $student->nama_sekolah_asal,
                    'alamat_sekolah_asal' => $address,
                    'kabupaten_kota_asal' => $student->kabupaten_kota_asal,
                    'provinsi_asal' => $student->provinsi_asal,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('NISN Prefill Lookup Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'found' => false,
                'message' => 'Pencarian data murid sedang bermasalah. Silakan isi data secara manual.',
                'data' => null,
            ], 500);
        }
    }

    private function supportsSubmissionTimeline(): bool
    {
        return Schema::hasColumns('mutasi_masuk', ['last_submitted_at', 'revision_count']);
    }

    /**
     * Resume editing a draft - load data back to form.
     */
    public function resumeDraft(string $id)
    {
        $user = Auth::user();

        $draft = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
        if (!$draft) {
            return redirect()->route('mutasi.masuk.index')->with('error', 'Draft tidak ditemukan');
        }
        if ($draft->status !== 'Draft') {
            return redirect()->route('mutasi.masuk.show', $id);
        }
        if ($draft->pengusul !== $user->username && !in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            return redirect()->route('mutasi.masuk.index')->with('error', 'Akses ditolak');
        }

        if ($draft->tanggal_surat === self::DRAFT_EMPTY_DATE) {
            $draft->tanggal_surat = null;
        }

        // Load students
        $students = DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->orderBy('id')->get();

        // Get school info
        $namaSekolahTujuan = $draft->nama_sekolah_tujuan;
        $npsnSekolahTujuan = $draft->npsn_sekolah_tujuan;
        $alamatSekolahTujuan = $draft->alamat_sekolah_tujuan;

        return view('mutasi.masuk.create', compact(
            'draft',
            'students',
            'namaSekolahTujuan',
            'npsnSekolahTujuan',
            'alamatSekolahTujuan'
        ));
    }

    /**
     * Delete a Draft - Only allowed for status 'Draft'
     * Deletes header, all students, and all uploaded files
     */
    public function deleteDraft(Request $request, string $id)
    {
        $user = Auth::user();

        // Only Operator, Sekolah can delete their own drafts
        if (!in_array($user->role, ['Operator', 'Sekolah', 'Dinas', 'SuperAdmin'])) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus draft.');
        }

        return DB::transaction(function () use ($id, $user) {
            $mutasi = DB::table('mutasi_masuk')
                ->where('id_mutasi', $id)
                ->lockForUpdate()
                ->first();

            if (!$mutasi) {
                return back()->with('error', 'Draft tidak ditemukan.');
            }

            // Authorization check
            if ($user->role === 'Operator' || $user->role === 'Sekolah') {
                if ($mutasi->pengusul !== $user->username && $mutasi->npsn_sekolah_tujuan !== $user->npsn) {
                    abort(403, 'Anda tidak berhak menghapus draft ini.');
                }
            }

            // Only allow deleting Draft status
            if ($mutasi->status !== 'Draft') {
                return back()->with('error', 'Hanya usulan dengan status Draft yang dapat dihapus. Gunakan tombol Batalkan untuk status lain.');
            }

            // Delete header files
            $headerFiles = ['file_surat_permohonan', 'file_arsip_scan'];
            // Delete student files
            $siswaList = DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->get();
            $fileColumns = [
                'file_surat_pindah',
                'file_surat_dapodik',
                'file_rekomendasi',
                'file_ijazah_sd',
                'file_rapor',
                'file_surat_penyaluran',
                'file_rekam_didik'
            ];

            $filePaths = array_map(fn ($column) => $mutasi->$column ?? null, $headerFiles);
            foreach ($siswaList as $siswa) {
                foreach ($fileColumns as $col) {
                    $filePaths[] = $siswa->$col ?? null;
                }
            }

            try {
                $this->fileUploadService->deleteStoredFiles($filePaths);
            } catch (\Throwable $e) {
                Log::error('Permanent incoming draft deletion stopped because files could not be removed.', [
                    'mutasi_id' => $id,
                    'exception' => $e,
                ]);

                return back()->with('error', 'Draft belum dihapus karena ada berkas yang gagal dihapus. Silakan coba kembali.');
            }

            // Delete student records
            DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->delete();

            // Delete header record
            DB::table('mutasi_masuk')->where('id_mutasi', $id)->delete();

            Log::info("Draft {$id} deleted by {$user->username}");

            return redirect()->route('mutasi.masuk.index')
                ->with('success', 'Draft berhasil dihapus beserta semua file yang sudah diunggah.');
        });
    }
}
