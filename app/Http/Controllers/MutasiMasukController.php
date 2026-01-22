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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MutasiMasukController extends Controller
{
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
            $query = \App\Models\MutasiMasuk::query() // Use Model instead of DB table
                ->with('siswa:id,id_mutasi,nama_murid,nisn,nama_sekolah_asal,npsn_sekolah_asal,kabupaten_kota_asal')
                ->orderBy('tanggal_pengajuan', 'desc');

            // Role-based filtering
            if ($user->role === 'Operator') {
                $query->where('pengusul', $user->username);
            } elseif ($user->role === 'Sekolah') {
                $query->where('npsn_sekolah_tujuan', $user->npsn);
            } elseif (in_array($user->role, ['Dinas', 'SuperAdmin'])) {
                // Eager load siswa for Popover details
                $query->with('siswa:id,id_mutasi,nama_murid,nisn,nama_sekolah_asal');
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
                $validStatuses = ['Menunggu Verifikasi Dinas', 'Disetujui Dinas', 'Perlu Perbaikan', 'Selesai'];
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
     * Only allowed if status is 'Menunggu Verifikasi Dinas'
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

            // Authorization: Only owner can cancel
            $isOwner = ($user->role === 'Operator' && $mutasi->pengusul === $user->username)
                || ($user->role === 'Sekolah' && $mutasi->npsn_sekolah_tujuan === $user->npsn);

            if (!$isOwner) {
                abort(403, 'Anda tidak berhak menghapus usulan ini.');
            }

            // 3. Strict Status Check
            if ($mutasi->status !== 'Menunggu Verifikasi Dinas') {
                return back()->with('error', 'Gagal menghapus! Status usulan telah berubah (sedang diproses oleh Dinas). Silakan refresh halaman.');
            }

            // 4. Delete Header Files (from dokumen_rahasia, not public/storage)
            $headerFiles = ['file_surat_permohonan', 'file_arsip_scan'];
            foreach ($headerFiles as $col) {
                if (!empty($mutasi->$col)) {
                    $relativePath = ltrim($mutasi->$col, '/');
                    // Fix for inconsistent path storage
                    if (strpos($relativePath, 'dokumen/') === 0) {
                        $relativePath = substr($relativePath, 8);
                    }

                    $fullPath = storage_path('app/dokumen_rahasia/' . $relativePath);

                    if (file_exists($fullPath)) {
                        try {
                            unlink($fullPath);
                            Log::info("Deleted header file on cancel: " . $mutasi->$col);
                        } catch (\Exception $e) {
                            Log::error("Failed to delete header file: " . $mutasi->$col);
                        }
                    }
                }
            }

            // 5. Delete Student Files
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

            foreach ($siswaList as $siswa) {
                foreach ($fileColumns as $col) {
                    if (!empty($siswa->$col)) {
                        $relativePath = ltrim($siswa->$col, '/');
                        // Fix for inconsistent path storage: some WebP files might have 'dokumen/' prefix in DB
                        // but physically exist directly under dokumen_rahasia/mutasi_masuk/...
                        if (strpos($relativePath, 'dokumen/') === 0) {
                            $relativePath = substr($relativePath, 8); // Remove 'dokumen/'
                        }

                        $fullPath = storage_path('app/dokumen_rahasia/' . $relativePath);

                        if (file_exists($fullPath)) {
                            try {
                                unlink($fullPath);
                                Log::info("Deleted student file on cancel: " . $siswa->$col);
                            } catch (\Exception $e) {
                                Log::error("Failed to delete student file: " . $siswa->$col);
                            }
                        }
                    }
                }
            }

            // 6. Delete Students Records
            DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->delete();

            // 7. Delete Header Record
            DB::table('mutasi_masuk')->where('id_mutasi', $id)->delete();

            // 8. Notify Dinas about cancellation
            try {
                \App\Helpers\NotificationHelper::notifyDinasCancelledSubmission($mutasi, 'masuk', 'Dibatalkan oleh pengusul');
            } catch (\Exception $e) {
                Log::error("Telegram notification failed on cancel: " . $e->getMessage());
            }

            return redirect()->route('mutasi.masuk.index')
                ->with('success', 'Usulan mutasi masuk dan data siswa berhasil dihapus permanen.');
        });
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $jumlahSiswa = (int) $request->jumlah_siswa;
        $isDinasEntry = $request->has('is_dinas_entry');

        // Validate jumlah_siswa first
        if ($jumlahSiswa < 1 || $jumlahSiswa > 20) {
            return back()->withErrors(['jumlah_siswa' => 'Jumlah siswa harus antara 1-20'])->withInput();
        }


        // Base validation with custom messages
        $rules = [
            'jumlah_siswa' => 'required|integer|min:1|max:20',
            'nomor_surat' => 'required|string|max:50',
            'tanggal_surat' => 'required|date|before_or_equal:today|after:1900-01-01',
            'npsn_sekolah_tujuan' => 'required|string|size:8',
            'nama_sekolah_tujuan' => 'required|string|max:150',
            'alamat_sekolah_tujuan' => 'required|string|max:500',
        ];

        if (!$isDinasEntry) {
            $rules['file_surat_permohonan'] = 'required|file|mimes:pdf|max:1024';
        }

        $messages = [
            // Header Document
            'file_surat_permohonan.required' => 'File Surat Permohonan wajib diunggah.',
            'file_surat_permohonan.file' => 'File Surat Permohonan harus berupa file.',
            'file_surat_permohonan.mimes' => 'File Surat Permohonan harus berformat PDF.',
            'file_surat_permohonan.max' => 'File Surat Permohonan maksimal 1MB. Silakan kompres file PDF Anda.',

            // Header Data
            'jumlah_siswa.required' => 'Jumlah siswa wajib diisi.',
            'jumlah_siswa.integer' => 'Jumlah siswa harus berupa angka.',
            'jumlah_siswa.min' => 'Jumlah siswa minimal 1.',
            'jumlah_siswa.max' => 'Jumlah siswa maksimal 20 per pengajuan.',
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
            $rules["siswa.{$i}.nisn"] = 'required|string|size:10';
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

            // Check if from foreign school - NPSN is optional for foreign schools
            $luarNegeri = $request->input("siswa.{$i}.asal_luar_negeri") === '1';

            // NPSN: optional for foreign schools, otherwise allow alphanumeric (for PKBM with P prefix)
            if ($luarNegeri) {
                $rules["siswa.{$i}.npsn_sekolah_asal"] = 'nullable|string|max:8';
            } else {
                // For domestic schools: allow alphanumeric (P prefix for PKBM) - 8 chars max
                $rules["siswa.{$i}.npsn_sekolah_asal"] = 'nullable|string|max:8';
            }

            // File validation: skip jika is_dinas_entry
            if (!$isDinasEntry) {
                // Surat Pindah: optional for foreign schools
                if ($luarNegeri) {
                    $rules["siswa.{$i}.file_surat_pindah"] = 'nullable|file|mimes:pdf|max:1024';
                    $rules["siswa.{$i}.file_surat_dapodik"] = 'nullable|file|mimes:pdf|max:1024';
                } else {
                    $rules["siswa.{$i}.file_surat_pindah"] = 'required|file|mimes:pdf|max:1024';
                    $rules["siswa.{$i}.file_surat_dapodik"] = 'required|file|mimes:pdf|max:1024';
                }

                $rules["siswa.{$i}.file_ijazah_sd"] = 'required|file|mimes:pdf|max:1024';
                $rules["siswa.{$i}.file_rapor"] = 'required|file|mimes:pdf|max:2048';

                // Conditional: Rekomendasi WAJIB untuk semua siswa dalam negeri
                // EXCEPTION: Jika asal dari Luar Negeri, Rekomendasi TIDAK WAJIB

                if ($luarNegeri) {
                    $rules["siswa.{$i}.file_rekomendasi"] = 'nullable|file|mimes:pdf|max:1024';
                } else {
                    // Semua siswa dalam negeri (Kota Bogor + Luar Kota): Rekomendasi WAJIB
                    $rules["siswa.{$i}.file_rekomendasi"] = 'required|file|mimes:pdf|max:1024';
                }

                // Conditional: Surat Penyaluran wajib jika luar negeri
                if ($luarNegeri) {
                    $rules["siswa.{$i}.file_surat_penyaluran"] = 'required|file|mimes:pdf|max:1024';
                } else {
                    $rules["siswa.{$i}.file_surat_penyaluran"] = 'nullable|file|mimes:pdf|max:1024';
                }

                // Rekam Didik: Wajib jika NISN ada (PDF, JPEG, atau PNG)
                $hasNisn = !empty($request->input("siswa.{$i}.nisn"));
                if ($hasNisn) {
                    if ($luarNegeri) {
                        $rules["siswa.{$i}.file_rekam_didik"] = 'nullable|file|mimes:jpeg,jpg,png|max:1024';
                    } else {
                        $rules["siswa.{$i}.file_rekam_didik"] = 'required|file|mimes:jpeg,jpg,png|max:1024';
                    }
                } else {
                    $rules["siswa.{$i}.file_rekam_didik"] = 'nullable|file|mimes:jpeg,jpg,png|max:1024';
                }
            }

            // Per-siswa validation messages - comprehensive
            $messages["siswa.{$i}.nama_murid.required"] = "Nama Siswa #{$siswaNum} wajib diisi.";
            $messages["siswa.{$i}.nama_murid.max"] = "Nama Siswa #{$siswaNum} maksimal 100 karakter.";
            $messages["siswa.{$i}.nisn.required"] = "NISN Siswa #{$siswaNum} wajib diisi.";
            $messages["siswa.{$i}.nisn.size"] = "NISN Siswa #{$siswaNum} harus tepat 10 digit angka. Contoh: 0012345678.";
            $messages["siswa.{$i}.nisn.string"] = "NISN Siswa #{$siswaNum} harus berupa angka.";
            $messages["siswa.{$i}.nis.required"] = "NIS/NIPD Siswa #{$siswaNum} wajib diisi.";
            $messages["siswa.{$i}.nis.max"] = "NIS/NIPD Siswa #{$siswaNum} maksimal 20 karakter.";
            $messages["siswa.{$i}.tempat_lahir.required"] = "Tempat Lahir Siswa #{$siswaNum} wajib diisi.";
            $messages["siswa.{$i}.tempat_lahir.max"] = "Tempat Lahir Siswa #{$siswaNum} maksimal 50 karakter.";
            $messages["siswa.{$i}.tanggal_lahir.required"] = "Tanggal Lahir Siswa #{$siswaNum} wajib diisi.";
            $messages["siswa.{$i}.tanggal_lahir.date"] = "Format Tanggal Lahir Siswa #{$siswaNum} tidak valid.";
            $messages["siswa.{$i}.tanggal_lahir.before"] = "Tanggal Lahir Siswa #{$siswaNum} harus sebelum hari ini.";
            $messages["siswa.{$i}.tanggal_lahir.after"] = "Tanggal Lahir Siswa #{$siswaNum} tidak valid (terlalu lampau).";
            $messages["siswa.{$i}.jenis_kelamin.required"] = "Jenis Kelamin Siswa #{$siswaNum} wajib dipilih.";
            $messages["siswa.{$i}.jenis_kelamin.in"] = "Jenis Kelamin Siswa #{$siswaNum} harus Laki-laki (L) atau Perempuan (P).";
            $messages["siswa.{$i}.kelas.required"] = "Kelas Siswa #{$siswaNum} wajib dipilih.";
            $messages["siswa.{$i}.kelas.in"] = "Kelas Siswa #{$siswaNum} harus VII, VIII, atau IX.";
            $messages["siswa.{$i}.tanggal_diterima.required"] = "Tanggal Diterima Siswa #{$siswaNum} wajib diisi.";
            $messages["siswa.{$i}.tanggal_diterima.date"] = "Format Tanggal Diterima Siswa #{$siswaNum} tidak valid.";
            $messages["siswa.{$i}.tanggal_diterima.before_or_equal"] = "Tanggal Diterima Siswa #{$siswaNum} tidak boleh lebih dari hari ini.";
            $messages["siswa.{$i}.nama_sekolah_asal.required"] = "Nama Sekolah Asal Siswa #{$siswaNum} wajib diisi.";
            $messages["siswa.{$i}.nama_sekolah_asal.max"] = "Nama Sekolah Asal Siswa #{$siswaNum} maksimal 150 karakter.";
            $messages["siswa.{$i}.alamat_sekolah_asal.required"] = "Alamat Sekolah Asal Siswa #{$siswaNum} wajib diisi.";
            $messages["siswa.{$i}.alamat_sekolah_asal.max"] = "Alamat Sekolah Asal Siswa #{$siswaNum} maksimal 500 karakter.";
            $messages["siswa.{$i}.provinsi_asal.required"] = "Provinsi Asal Siswa #{$siswaNum} wajib dipilih.";
            $messages["siswa.{$i}.kabupaten_kota_asal.required"] = "Kabupaten/Kota Asal Siswa #{$siswaNum} wajib dipilih.";

            // File messages
            $messages["siswa.{$i}.file_surat_pindah.required"] = "Surat Keterangan Pindah Siswa #{$siswaNum} wajib diunggah.";
            $messages["siswa.{$i}.file_surat_pindah.mimes"] = "Surat Keterangan Pindah Siswa #{$siswaNum} harus berformat PDF.";
            $messages["siswa.{$i}.file_surat_pindah.max"] = "Surat Keterangan Pindah Siswa #{$siswaNum} maksimal 1MB.";
            $messages["siswa.{$i}.file_surat_dapodik.required"] = "Surat Keterangan Dapodik Siswa #{$siswaNum} wajib diunggah.";
            $messages["siswa.{$i}.file_surat_dapodik.mimes"] = "Surat Keterangan Dapodik Siswa #{$siswaNum} harus berformat PDF.";
            $messages["siswa.{$i}.file_surat_dapodik.max"] = "Surat Keterangan Dapodik Siswa #{$siswaNum} maksimal 1MB.";
            $messages["siswa.{$i}.file_ijazah_sd.required"] = "Ijazah SD Siswa #{$siswaNum} wajib diunggah.";
            $messages["siswa.{$i}.file_ijazah_sd.mimes"] = "Ijazah SD Siswa #{$siswaNum} harus berformat PDF.";
            $messages["siswa.{$i}.file_ijazah_sd.max"] = "Ijazah SD Siswa #{$siswaNum} maksimal 1MB.";
            $messages["siswa.{$i}.file_rapor.required"] = "Rapor Siswa #{$siswaNum} wajib diunggah.";
            $messages["siswa.{$i}.file_rapor.mimes"] = "Rapor Siswa #{$siswaNum} harus berformat PDF.";
            $messages["siswa.{$i}.file_rapor.max"] = "Rapor Siswa #{$siswaNum} maksimal 2MB. Silakan kompres file PDF Anda.";
            $messages["siswa.{$i}.file_rekomendasi.required"] = "Surat Rekomendasi Siswa #{$siswaNum} wajib diunggah (wajib untuk siswa dalam negeri).";
            $messages["siswa.{$i}.file_rekomendasi.mimes"] = "Surat Rekomendasi Siswa #{$siswaNum} harus berformat PDF.";
            $messages["siswa.{$i}.file_rekomendasi.max"] = "Surat Rekomendasi Siswa #{$siswaNum} maksimal 1MB.";
            $messages["siswa.{$i}.file_surat_penyaluran.required"] = "Surat Penyaluran Siswa #{$siswaNum} wajib diunggah (wajib untuk siswa dari luar negeri).";
            $messages["siswa.{$i}.file_surat_penyaluran.mimes"] = "Surat Penyaluran Siswa #{$siswaNum} harus berformat PDF.";
            $messages["siswa.{$i}.file_surat_penyaluran.max"] = "Surat Penyaluran Siswa #{$siswaNum} maksimal 1MB.";
            $messages["siswa.{$i}.file_rekam_didik.required"] = "Rekam Didik Siswa #{$siswaNum} wajib diunggah (karena siswa memiliki NISN). Koordinasi dengan bagian Dapodik Dinas Pendidikan Kota Bogor untuk screenshot Rekam Didik.";
            $messages["siswa.{$i}.file_rekam_didik.mimes"] = "Rekam Didik Siswa #{$siswaNum} harus berformat JPEG atau PNG.";
            $messages["siswa.{$i}.file_rekam_didik.max"] = "Rekam Didik Siswa #{$siswaNum} maksimal 1MB.";
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            DB::beginTransaction();

            // Generate unique ID with timestamp for extra uniqueness
            $count = DB::table('mutasi_masuk')->count() + 1;
            $idMutasi = 'MM' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);

            // Ensure unique
            while (DB::table('mutasi_masuk')->where('id_mutasi', $idMutasi)->exists()) {
                $count++;
                $idMutasi = 'MM' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);
            }

            // Upload surat permohonan
            $fileSuratPermohonan = $this->fileUploadService->uploadSuratPermohonan($request, $idMutasi);

            // Sanitize and insert header
            // Auto-approve if submitted by Dinas/SuperAdmin (they are the verifiers)
            $initialStatus = in_array($user->role, ['Dinas', 'SuperAdmin'])
                ? 'Disetujui Dinas'
                : 'Menunggu Verifikasi Dinas';

            DB::table('mutasi_masuk')->insert([
                'id_mutasi' => $idMutasi,
                'tanggal_pengajuan' => now(),
                'jumlah_siswa' => $jumlahSiswa,
                'nomor_surat' => $this->sanitize($request->nomor_surat),
                'tanggal_surat' => $request->tanggal_surat,
                'file_surat_permohonan' => $fileSuratPermohonan,
                'npsn_sekolah_tujuan' => $this->sanitizeNpsn($request->npsn_sekolah_tujuan),
                'nama_sekolah_tujuan' => $this->sanitize($request->nama_sekolah_tujuan),
                'alamat_sekolah_tujuan' => $this->sanitize($request->alamat_sekolah_tujuan),
                'status' => $initialStatus,
                'pengusul' => $user->username,
                'is_dinas_entry' => $isDinasEntry ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert per siswa with sanitization
            $siswaData = $request->input('siswa', []);
            // FIX: Use getDateBasedPath (returns 'mutasi_masuk/...') instead of getRelativeDateBasedPath ('dokumen/mutasi_masuk/...')
            // This ensures DB path matches physical path structure under dokumen_rahasia root
            $uploadPathKey = $this->fileUploadService->getDateBasedPath('masuk');

            foreach ($siswaData as $idx => $siswa) {
                // Upload standard PDF files using service
                $filePaths = $this->fileUploadService->uploadMutasiMasukSiswaFiles($request, $idMutasi, $idx);

                // Handle file_rekam_didik (Image/PDF) separately
                $field = 'file_rekam_didik';
                $fileKey = "siswa.{$idx}.{$field}";

                if ($request->hasFile($fileKey)) {
                    $file = $request->file($fileKey);
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
                            $conversionSuccess = $this->imageService->convertToWebP($file, $tempPath);
                        }

                        if ($conversionSuccess && file_exists($tempPath)) {
                            // Manual upload of the converted file
                            $filename = $tempFilename;
                            $destinationPath = $uploadPathKey . '/' . $filename;

                            // Use the configured disk from service
                            $disk = $this->fileUploadService->isCloudStorage() ? 'b2' : 'private_docs';

                            try {
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
                        throw new \Exception("File Rekam Didik siswa #" . ($idx + 1) . " harus berformat PDF, JPEG, atau PNG.");
                    }
                }

                DB::table('mutasi_masuk_siswa')->insert([
                    'id_mutasi' => $idMutasi,
                    'nama_murid' => $this->sanitize($siswa['nama_murid']),
                    'nisn' => $this->sanitizeNisn($siswa['nisn']),
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
                    'asal_luar_negeri' => ($siswa['asal_luar_negeri'] ?? '0') === '1',
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
                ->with('success', "Mutasi Masuk {$idMutasi} berhasil dibuat ({$jumlahSiswa} siswa)");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Mutasi Masuk Error: " . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
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
            if ($mutasi->pengusul !== $user->username) {
                abort(403, 'Anda tidak memiliki akses ke data ini.');
            }
        } elseif ($user->role === 'Sekolah') {
            if ($mutasi->npsn_sekolah_tujuan !== $user->npsn) {
                abort(403, 'Anda tidak memiliki akses ke data ini.');
            }
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

        $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        $action = $request->input('action');
        $status = $action === 'approve' ? 'Disetujui Dinas' : 'Perlu Perbaikan';

        // Save verification status for each document per siswa
        $siswaList = DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->get();
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

        // Send Telegram notification to pengusul (wrapped in try-catch)
        try {
            $updatedMutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
            if ($updatedMutasi) {
                \App\Helpers\NotificationHelper::notifyPengusul($updatedMutasi, $status, 'masuk');
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
            'nomor_surat_izin' => 'required|string|max:50',
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

        DB::table('mutasi_masuk')->where('id_mutasi', $id)->update([
            'nomor_surat_izin' => $this->sanitize($request->nomor_surat_izin),
            'tanggal_surat_izin' => $request->tanggal_surat_izin,
            'status' => 'Proses TTD',
            'updated_at' => now(),
        ]);

        // Send Telegram notification to pengusul about Proses TTD status (wrapped in try-catch)
        try {
            $updatedMutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();
            if ($updatedMutasi) {
                \App\Helpers\NotificationHelper::notifyPengusul($updatedMutasi, 'Proses TTD', 'masuk');
            }
        } catch (\Exception $e) {
            Log::error("Telegram notification failed: " . $e->getMessage());
        }

        // Auto-save logic moved to completeLetter

        Log::info("Surat Izin Mutasi Masuk {$id} siap TTD oleh {$user->username}: {$request->nomor_surat_izin}");

        return redirect()->route('mutasi.masuk.show', $id)
            ->with('success', "📝 Surat Izin siap dicetak dan ditandatangani!\n\n📄 Nomor: {$request->nomor_surat_izin}\n📅 Tanggal: " . \Carbon\Carbon::parse($request->tanggal_surat_izin)->translatedFormat('d F Y') . "\n\n⏳ Status: Proses TTD");
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

        DB::table('mutasi_masuk')->where('id_mutasi', $id)->update([
            'tanggal_selesai' => $request->tanggal_selesai,
            'status' => 'Selesai',
            'updated_at' => now(),
        ]);

        // Get pengusul phone for notification
        $pengusul = DB::table('users')->where('username', $mutasi->pengusul)->first();
        $whatsappLink = null;

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
            \App\Helpers\NotificationHelper::notifyPengusul($mutasiWithSiswa, 'Selesai', 'masuk');
        } catch (\Exception $e) {
            Log::error("Telegram notification failed: " . $e->getMessage());
        }

        if ($pengusul && !empty($pengusul->no_kontak)) {
            $message = \App\Helpers\NotificationHelper::buildCompletionMessage($mutasiWithSiswa, 'masuk');
            $whatsappLink = \App\Helpers\NotificationHelper::generateWhatsAppLink($pengusul->no_kontak, $message);
        }

        Log::info("Surat Izin Mutasi Masuk {$id} selesai oleh {$user->username}");

        $successMessage = "🎉 Surat Izin Mutasi Masuk selesai!\n\n📄 Nomor: {$mutasi->nomor_surat_izin}\n📅 Tanggal Selesai: " . \Carbon\Carbon::parse($request->tanggal_selesai)->translatedFormat('d F Y');

        if ($whatsappLink) {
            session()->flash('whatsapp_link', $whatsappLink);
            session()->flash('pengusul_name', $pengusul->nama_pengguna ?? $mutasi->pengusul);
        }

        return redirect()->route('mutasi.masuk.show', $id)->with('success', $successMessage);
    }

    public function printLetter(string $id)
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

        return view('surat.izin-mutasi-masuk', [
            'mutasi' => $mutasiData,
            'siswaList' => $siswaList,
            'penandatangan' => $penandatangan,
        ]);
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
                // Ensure siswa exists & belongs to this mutasi
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
                        // Using a manual call to uploadPdf to handle the specific naming convention for existing siswa ID
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
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Generate unique ID
            $count = DB::table('mutasi_masuk')->count() + 1;
            $idMutasi = 'MM' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);
            while (DB::table('mutasi_masuk')->where('id_mutasi', $idMutasi)->exists()) {
                $count++;
                $idMutasi = 'MM' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);
            }

            // Upload surat permohonan if provided
            $fileSuratPermohonan = null;
            if ($request->hasFile('file_surat_permohonan')) {
                $fileSuratPermohonan = $this->fileUploadService->uploadSuratPermohonan($request, $idMutasi);
            }

            // Insert header as Draft
            DB::table('mutasi_masuk')->insert([
                'id_mutasi' => $idMutasi,
                'tanggal_pengajuan' => now(),
                'jumlah_siswa' => (int) $request->jumlah_siswa,
                'nomor_surat' => $this->sanitize($request->nomor_surat ?? ''),
                'tanggal_surat' => $request->tanggal_surat,
                'file_surat_permohonan' => $fileSuratPermohonan,
                'npsn_sekolah_tujuan' => $this->sanitizeNpsn($request->npsn_sekolah_tujuan),
                'nama_sekolah_tujuan' => $this->sanitize($request->nama_sekolah_tujuan),
                'alamat_sekolah_tujuan' => $this->sanitize($request->alamat_sekolah_tujuan),
                'status' => 'Draft',
                'pengusul' => $user->username,
                'is_dinas_entry' => $request->has('is_dinas_entry') ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("Draft created: {$idMutasi} by {$user->username}");

            return response()->json([
                'success' => true,
                'message' => 'Draft berhasil dibuat',
                'id_mutasi' => $idMutasi
            ]);

        } catch (\Exception $e) {
            Log::error("Create Draft Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat draft: ' . $e->getMessage()
            ], 500);
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

        $studentIndex = (int) $request->input('student_index', 0);

        try {
            // Build file paths array
            $filePaths = [];
            $uploadPathKey = $this->fileUploadService->getDateBasedPath('masuk');
            $isDinasEntry = (bool) $draft->is_dinas_entry;

            // Upload files if provided (not Dinas entry)
            if (!$isDinasEntry) {
                $fileFields = [
                    'file_surat_pindah',
                    'file_surat_dapodik',
                    'file_rekomendasi',
                    'file_ijazah_sd',
                    'file_rapor',
                    'file_surat_penyaluran'
                ];

                foreach ($fileFields as $field) {
                    if ($request->hasFile($field)) {
                        $file = $request->file($field);
                        $filePaths[$field] = $this->fileUploadService->uploadPdf(
                            $file,
                            $id,
                            "siswa{$studentIndex}_{$field}",
                            $uploadPathKey
                        );
                    }
                }

                // Handle Rekam Didik (Image/PDF)
                if ($request->hasFile('file_rekam_didik')) {
                    $file = $request->file('file_rekam_didik');
                    $mimeType = $file->getMimeType();

                    if (in_array($mimeType, ['image/jpeg', 'image/png'])) {
                        // Try WebP conversion
                        $tempDir = sys_get_temp_dir();
                        $tempFilename = $this->sanitizeFilename($id) . "_siswa{$studentIndex}_file_rekam_didik.webp";
                        $tempPath = $tempDir . '/' . $tempFilename;
                        $conversionSuccess = false;

                        if (extension_loaded('gd')) {
                            $conversionSuccess = $this->imageService->convertToWebP($file, $tempPath);
                        }

                        if ($conversionSuccess && file_exists($tempPath)) {
                            $destinationPath = $uploadPathKey . '/' . $tempFilename;
                            $disk = $this->fileUploadService->isCloudStorage() ? 'b2' : 'private_docs';
                            \Illuminate\Support\Facades\Storage::disk($disk)->put($destinationPath, file_get_contents($tempPath));
                            $filePaths['file_rekam_didik'] = $destinationPath;
                            @unlink($tempPath);
                        } else {
                            $filePaths['file_rekam_didik'] = $this->fileUploadService->uploadImage(
                                $file,
                                $id,
                                "siswa{$studentIndex}_file_rekam_didik",
                                $uploadPathKey
                            );
                        }
                    } elseif ($mimeType === 'application/pdf') {
                        $filePaths['file_rekam_didik'] = $this->fileUploadService->uploadPdf(
                            $file,
                            $id,
                            "siswa{$studentIndex}_file_rekam_didik",
                            $uploadPathKey
                        );
                    }
                }
            }

            // Check if student record already exists (update) or create new
            $existingStudent = DB::table('mutasi_masuk_siswa')
                ->where('id_mutasi', $id)
                ->whereRaw(
                    "nama_murid = ? OR id = (SELECT id FROM mutasi_masuk_siswa WHERE id_mutasi = ? ORDER BY id LIMIT 1 OFFSET ?)",
                    [$this->sanitize($request->nama_murid), $id, $studentIndex]
                )
                ->first();

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
                'asal_luar_negeri' => ($request->asal_luar_negeri ?? '0') === '1',
                'updated_at' => now(),
            ];

            // Merge file paths
            foreach ($filePaths as $key => $path) {
                $studentData[$key] = $path;
            }

            if ($existingStudent) {
                // Update existing
                DB::table('mutasi_masuk_siswa')->where('id', $existingStudent->id)->update($studentData);
            } else {
                // Insert new
                $studentData['created_at'] = now();
                DB::table('mutasi_masuk_siswa')->insert($studentData);
            }

            // Update jumlah_siswa count in header
            $actualCount = DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->count();
            DB::table('mutasi_masuk')->where('id_mutasi', $id)->update([
                'jumlah_siswa' => $actualCount,
                'updated_at' => now()
            ]);

            Log::info("Student saved to draft {$id}: index {$studentIndex}");

            return response()->json([
                'success' => true,
                'message' => 'Siswa #' . ($studentIndex + 1) . ' berhasil disimpan',
                'student_count' => $actualCount
            ]);

        } catch (\Exception $e) {
            Log::error("Add Student to Draft Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan siswa: ' . $e->getMessage()
            ], 500);
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
            return response()->json(['success' => false, 'message' => 'Minimal 1 siswa harus diisi'], 400);
        }

        try {
            // Update status
            $newStatus = in_array($user->role, ['Dinas', 'SuperAdmin'])
                ? 'Disetujui Dinas'
                : 'Menunggu Verifikasi Dinas';

            DB::table('mutasi_masuk')->where('id_mutasi', $id)->update([
                'status' => $newStatus,
                'jumlah_siswa' => $studentCount,
                'updated_at' => now()
            ]);

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
                'message' => "Mutasi Masuk {$id} berhasil dikirim ({$studentCount} siswa)",
                'redirect' => route('mutasi.masuk.index')
            ]);

        } catch (\Exception $e) {
            Log::error("Finalize Draft Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim: ' . $e->getMessage()
            ], 500);
        }
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
}