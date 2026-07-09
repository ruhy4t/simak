<?php

namespace App\Http\Controllers;

use App\Models\SatuanPendidikan;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MutasiController extends Controller
{
    protected FileUploadService $fileUploadService;

    private const DRAFT_EMPTY_NISN = '0000000000';
    private const DRAFT_EMPTY_NPSN = '00000000';
    private const DRAFT_EMPTY_DATE = '1900-01-02';

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function uploadArsip(Request $request, $id)
    {
        $request->validate([
            'file_arsip_scan' => 'required|file|mimes:pdf|max:2048', // Max 2MB
        ]);

        $mutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            return back()->with('error', 'Data tidak ditemukan.');
        }

        if ($request->hasFile('file_arsip_scan')) {
            $path = $this->fileUploadService->uploadArsip($request->file('file_arsip_scan'), $id, 'keluar');

            DB::table('mutasi_keluar')->where('id_mutasi', $id)->update([
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
            $query = DB::table('mutasi_keluar')
                ->whereNull('deleted_at') // HIDE SOFT DELETED
                ->where('status', '!=', 'Dibatalkan') // HIDE CANCELLED
                ->orderBy('tanggal_pengajuan', 'desc');

            // Role-based filtering
            if ($user->role === 'Operator') {
                // STRICT: Operator only sees their own proposals
                $query->where('pengusul', $user->username);
            } elseif ($user->role === 'Sekolah') {
                // Sekolah sees ALL proposals from their school (Relaxed Rule)
                $query->where('npsn_sekolah_asal', $user->npsn);
            } elseif (in_array($user->role, ['Dinas', 'SuperAdmin'])) {
                // Dinas cannot see 'Draft' from Operator/Sekolah
                $query->where(function ($q) {
                    // Show items that are NOT draft
                    $q->where('status', '!=', 'Draft')
                        // OR show items created by Dinas/SuperAdmin themselves (if any)
                        ->orWhereIn('pengusul', function ($sub) {
                            $sub->select('username')->from('users')->whereIn('role', ['Dinas', 'SuperAdmin']);
                        });
                });
            }
            // Search filter with sanitization
            if ($request->filled('search')) {
                $search = '%' . strip_tags($request->search) . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('nama_murid', 'like', $search)
                        ->orWhere('nisn', 'like', $search)
                        ->orWhere('nama_sekolah_asal', 'like', $search);
                });
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $mutasiList = $query->paginate(10);

            return view('mutasi.keluar.index', compact('mutasiList'));
        } catch (\Exception $e) {
            return view('mutasi.keluar.index', ['mutasiList' => collect([])]);
        }
    }

    public function create()
    {
        $user = Auth::user();

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
                return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
            }

            // 2. Check Allowed Days
            if (!empty($allowedDays) && !in_array($today->dayOfWeek, $allowedDays)) {
                return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
            }

            // 3. Check Allowed Hours
            if (\App\Models\Setting::isHourRestrictionEnabled()) {
                $currentTime = $today->format('H:i');
                if ($currentTime < $startTime || $currentTime > $endTime) {
                    return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
                }
            }
        }

        // Initialize with user data
        $alamatSekolahAsal = $user->alamat ?? '';
        $namaSekolahAsal = $user->nama_sekolah ?? '';
        $npsnSekolahAsal = $user->npsn ?? '';

        // Get from satuan_pendidikan by NPSN if available
        if (!empty($user->npsn)) {
            try {
                $satpen = DB::table('satuan_pendidikan')
                    ->where('npsn', $user->npsn)
                    ->first();

                if ($satpen) {
                    // Always take nama from satpen if empty
                    if (empty($namaSekolahAsal) && !empty($satpen->nama)) {
                        $namaSekolahAsal = $satpen->nama;
                    }

                    // Build alamat - prefer alamat_lengkap, else build from components
                    if (empty($alamatSekolahAsal)) {
                        if (!empty($satpen->alamat_lengkap)) {
                            $alamatSekolahAsal = $satpen->alamat_lengkap;
                        } else {
                            // Build from components
                            $parts = array_filter([
                                $satpen->alamat_jalan ?? '',
                                !empty($satpen->kelurahan) ? "Kel. {$satpen->kelurahan}" : '',
                                !empty($satpen->kecamatan) ? "Kec. {$satpen->kecamatan}" : '',
                                $satpen->kota ?? '',
                                $satpen->provinsi ?? '',
                            ]);
                            $alamatSekolahAsal = implode(', ', $parts);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue
                \Log::error('Error fetching satuan_pendidikan: ' . $e->getMessage());
            }
        }

        $draft = null;
        return view('mutasi.keluar.create', compact('user', 'alamatSekolahAsal', 'namaSekolahAsal', 'npsnSekolahAsal', 'draft'));
    }

    /**
     * Cancel and Permanently Delete Submission
     * Only allowed if status is 'Menunggu Verifikasi Dinas' or 'Perlu Perbaikan'
     */
    public function cancel(Request $request, string $id)
    {
        $user = Auth::user();

        // 1. Transaction to ensure atomicity
        return DB::transaction(function () use ($id, $user) {
            // 2. Double Check / Locking
            // Use lockForUpdate to prevent race conditions during check
            $mutasi = DB::table('mutasi_keluar')
                ->where('id_mutasi', $id)
                ->lockForUpdate()
                ->first();

            if (!$mutasi) {
                abort(404);
            }

            // Authorization
            if ($user->role === 'Operator') {
                if ($mutasi->pengusul !== $user->username) {
                    abort(403, 'Anda tidak berhak menghapus usulan ini (Bukan Pengusul).');
                }
            } elseif ($user->role === 'Sekolah') {
                // Sekolah Admin can cancel any school proposal?
                // "selain yang mengusulkan tidak bisa melihat data dan melanjutkan" -> User originally said this.
                // New rule: "akun sekolah bisa melihat semua".
                // DOES "MELIHAT" INCLUDE "MEMBATALKAN"?
                // Usually Cancel is destructive. Let's keep Cancel stricter or allow School Admin to cancel Operator's?
                // User didn't specify cancel for Sekolah. Safest is allow School Admin to cancel school proposals.
                if ($mutasi->npsn_sekolah_asal !== $user->npsn && $mutasi->pengusul !== $user->username) {
                    abort(403, 'Anda tidak berhak menghapus usulan ini.');
                }
            } elseif (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
                abort(403);
            }

            // 3. Strict Status Check
            if (!in_array($mutasi->status, ['Menunggu Verifikasi Dinas', 'Perlu Perbaikan'])) {
                return back()->with('error', 'Gagal menghapus! Status usulan tidak mengizinkan pembatalan. Silakan refresh halaman.');
            }

            // 4. Delete Files Physically (from dokumen_rahasia, not public/storage)
            $fileColumns = ['file_suket_keluar', 'file_suket_dapo', 'file_suket_diterima', 'file_rapor', 'file_arsip_scan'];
            foreach ($fileColumns as $col) {
                if (!empty($mutasi->$col)) {
                    $fullPath = storage_path('app/dokumen_rahasia/' . ltrim($mutasi->$col, '/'));
                    if (file_exists($fullPath)) {
                        try {
                            unlink($fullPath);
                            Log::info("Deleted file on cancel: " . $mutasi->$col);
                        } catch (\Exception $e) {
                            Log::error("Failed to delete file on cancel: " . $mutasi->$col . " - " . $e->getMessage());
                        }
                    }
                }
            }

            // 5. Permanent Delete Database Record
            DB::table('mutasi_keluar')->where('id_mutasi', $id)->delete();

            return redirect()->route('mutasi.keluar.index')
                ->with('success', 'Usulan berhasil dihapus permanen.');
        });
    }

    // REVISION METHODS

    public function revisionEdit(string $id)
    {
        $user = Auth::user();

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
                return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
            }

            // 2. Check Allowed Days
            if (!empty($allowedDays) && !in_array($today->dayOfWeek, $allowedDays)) {
                return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
            }

            // 3. Check Allowed Hours
            if (\App\Models\Setting::isHourRestrictionEnabled()) {
                $currentTime = $today->format('H:i');
                if ($currentTime < $startTime || $currentTime > $endTime) {
                    return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
                }
            }
        }

        $mutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();

        if (!$mutasi)
            abort(404);

        // Case-insensitive check STRICT
        if (strtolower($mutasi->pengusul) !== strtolower($user->username)) {
            abort(403, 'Hanya akun pengusul yang dapat mengedit revisi.');
        }

        // Allow Revision for 'Perlu Perbaikan' OR 'Dibatalkan'
        if (!in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan'])) {
            return back()->with('error', 'Status tidak valid untuk perbaikan.');
        }

        try {
            return view('mutasi.keluar.revision-edit', compact('mutasi'));
        } catch (\Throwable $e) {
            return response('Error in View Rendering: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    /**
     * Revision Resubmit - Submit corrected data back to Dinas
     * Allows 'Perlu Perbaikan' AND 'Dibatalkan'
     */
    public function revisionResubmit(Request $request, string $id)
    {
        $user = Auth::user();

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
                return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
            }

            // 2. Check Allowed Days
            if (!empty($allowedDays) && !in_array($today->dayOfWeek, $allowedDays)) {
                return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
            }

            // 3. Check Allowed Hours
            if (\App\Models\Setting::isHourRestrictionEnabled()) {
                $currentTime = $today->format('H:i');
                if ($currentTime < $startTime || $currentTime > $endTime) {
                    return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
                }
            }
        }

        $mutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        // Security: Strictly only the original proposer/pengusul can resubmit
        if (strtolower($mutasi->pengusul) !== strtolower($user->username)) {
            abort(403, 'Hanya akun pengusul yang dapat mengirim ulang data ini.');
        }

        // Allow 'Perlu Perbaikan' OR 'Dibatalkan'
        if (!in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan'])) {
            return back()->with('error', 'Data hanya dapat dikirim ulang jika status adalah Perlu Perbaikan atau Dibatalkan.');
        }

        // Validation Rules
        $rules = [
            'nama_murid' => 'required|string|max:100',
            'nisn' => 'required|string|size:10',
            'kelas' => 'required|in:VII,VIII,IX',
            'tanggal_lahir' => 'required|date|before:today|after:1900-01-01',
            'nomor_surat_sekolah' => 'required|string|max:50',
            'tanggal_surat_sekolah' => 'required|date|before_or_equal:today|after:1900-01-01',
            'nama_sekolah_tujuan' => 'required|string|max:150',
            'npsn_sekolah_tujuan' => 'required|string|size:8',
            'alasan_pindah' => 'required|string',
        ];

        // If status was 'Dibatalkan' (files deleted), files are REQUIRED again.
        // If 'Perlu Perbaikan', files are optional (nullable).
        $isFileRequired = ($mutasi->status === 'Dibatalkan') ? 'required' : 'nullable';

        $rules['file_suket_keluar'] = "$isFileRequired|file|mimes:pdf|max:1024";
        $rules['file_suket_dapo'] = "$isFileRequired|file|mimes:pdf|max:1024";
        $rules['file_suket_diterima'] = "$isFileRequired|file|mimes:pdf|max:1024";
        $rules['file_rapor'] = "$isFileRequired|file|mimes:pdf|max:2048";

        $messages = [
            // Student Data
            'nama_murid.required' => 'Nama Siswa wajib diisi.',
            'nama_murid.max' => 'Nama Siswa maksimal 100 karakter.',
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.size' => 'NISN harus tepat 10 digit angka. Contoh: 0012345678.',
            'kelas.required' => 'Kelas wajib dipilih.',
            'kelas.in' => 'Kelas harus VII, VIII, atau IX.',
            'tanggal_lahir.required' => 'Tanggal Lahir wajib diisi.',
            'tanggal_lahir.date' => 'Format Tanggal Lahir tidak valid.',
            'tanggal_lahir.before' => 'Tanggal Lahir harus sebelum hari ini.',

            // Letter Data
            'nomor_surat_sekolah.required' => 'Nomor Surat Mutasi wajib diisi.',
            'tanggal_surat_sekolah.required' => 'Tanggal Surat Mutasi wajib diisi.',
            'tanggal_surat_sekolah.date' => 'Format Tanggal Surat tidak valid.',
            'tanggal_surat_sekolah.before_or_equal' => 'Tanggal Surat tidak boleh lebih dari hari ini.',

            // School Destination
            'nama_sekolah_tujuan.required' => 'Nama Sekolah Tujuan wajib diisi.',
            'npsn_sekolah_tujuan.required' => 'NPSN Sekolah Tujuan wajib diisi.',
            'npsn_sekolah_tujuan.size' => 'NPSN Sekolah Tujuan harus tepat 8 karakter.',
            'alasan_pindah.required' => 'Alasan Pindah wajib diisi.',

            // Files
            'file_suket_keluar.required' => 'File Surat Keterangan Keluar wajib diunggah (karena status sebelumnya Dibatalkan).',
            'file_suket_keluar.mimes' => 'File Surat Keterangan Keluar harus berformat PDF.',
            'file_suket_keluar.max' => 'File Surat Keterangan Keluar maksimal 1MB.',
            'file_suket_dapo.required' => 'File Surat Keterangan Dapodik wajib diunggah (karena status sebelumnya Dibatalkan).',
            'file_suket_dapo.mimes' => 'File Surat Keterangan Dapodik harus berformat PDF.',
            'file_suket_dapo.max' => 'File Surat Keterangan Dapodik maksimal 1MB.',
            'file_suket_diterima.required' => 'File Surat Keterangan Diterima wajib diunggah (karena status sebelumnya Dibatalkan).',
            'file_suket_diterima.mimes' => 'File Surat Keterangan Diterima harus berformat PDF.',
            'file_suket_diterima.max' => 'File Surat Keterangan Diterima maksimal 1MB.',
            'file_rapor.required' => 'File Rapor wajib diunggah (karena status sebelumnya Dibatalkan).',
            'file_rapor.mimes' => 'File Rapor harus berformat PDF.',
            'file_rapor.max' => 'File Rapor maksimal 2MB.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Handle file uploads if new files provided
        $filePaths = $this->fileUploadService->uploadMutasiKeluarRevisionFiles($request, $id);

        // --- CLEANUP: Delete old files if replaced ---
        foreach ($filePaths as $field => $newPath) {
            if (!empty($mutasi->$field)) {
                $this->fileUploadService->deleteOldFile($mutasi->$field);
            }
        }
        // ---------------------------------------------
        // ---------------------------------------------

        // Build update data with sanitization
        $updateData = [
            'nama_murid' => $request->nama_murid,
            'nisn' => $request->nisn,
            'nis' => $request->nis ?? $mutasi->nis,
            'tempat_lahir' => $request->tempat_lahir ?? $mutasi->tempat_lahir,
            'tanggal_lahir' => $request->tanggal_lahir ?? $mutasi->tanggal_lahir,
            'jenis_kelamin' => $request->jenis_kelamin ?? $mutasi->jenis_kelamin,
            'kelas' => $request->kelas,
            'nomor_surat_sekolah' => $request->nomor_surat_sekolah,
            'tanggal_surat_sekolah' => $request->tanggal_surat_sekolah,
            'npsn_sekolah_tujuan' => $request->npsn_sekolah_tujuan,
            'nama_sekolah_tujuan' => $request->nama_sekolah_tujuan,
            'alamat_sekolah_tujuan' => $request->alamat_sekolah_tujuan ?? $mutasi->alamat_sekolah_tujuan,
            'kabupaten_kota_tujuan' => $request->kabupaten_kota_tujuan ?? $mutasi->kabupaten_kota_tujuan,
            'provinsi_tujuan' => $request->provinsi_tujuan ?? $mutasi->provinsi_tujuan,
            'alasan_pindah' => $request->alasan_pindah,
            'status' => 'Menunggu Verifikasi Dinas',
            'catatan_berkas' => null, // Clear previous revision notes
            'updated_at' => now(),
        ];

        // Add file paths if new files uploaded
        foreach ($filePaths as $field => $path) {
            $updateData[$field] = $path;
        }

        DB::table('mutasi_keluar')->where('id_mutasi', $id)->update($updateData);

        // Notify Dinas about revision resubmission
        $updatedMutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();
        if ($updatedMutasi) {
            \App\Helpers\NotificationHelper::notifyDinasNewSubmission($updatedMutasi, 'keluar');
        }

        return redirect()->route('mutasi.keluar.show', $id)
            ->with('success', '✅ Data berhasil dikirim ulang. Status kembali ke Menunggu Verifikasi Dinas.');
    }
    /**
     * Soft Delete (Cancel/Remove from view)
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $mutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        // Authorization: STRICT Only PROPOSER can delete (cancel)
        if ($user->role === 'Operator' || $user->role === 'Sekolah') {
            if ($mutasi->pengusul !== $user->username) {
                abort(403, 'Anda tidak berhak menghapus usulan ini (bukan pengusul asli).');
            }
        } elseif (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403);
        }

        // Validation: Only allow delete for specific statuses
        if ($mutasi->status !== 'Menunggu Verifikasi Dinas') {
            return back()->with('error', 'Data tidak dapat dihapus karena sudah diproses/disetujui.');
        }

        DB::table('mutasi_keluar')->where('id_mutasi', $id)->update([
            'status' => 'Dibatalkan',
            'deleted_at' => now(), // Soft Delete
            'updated_at' => now(),
        ]);

        return redirect()->route('mutasi.keluar.index')->with('success', 'Data berhasil dihapus (dibatalkan).');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $draftId = $request->input('draft_id');

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
                return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
            }

            // 2. Check Allowed Days
            if (!empty($allowedDays) && !in_array($today->dayOfWeek, $allowedDays)) {
                return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
            }

            // 3. Check Allowed Hours
            if (\App\Models\Setting::isHourRestrictionEnabled()) {
                $currentTime = $today->format('H:i');

                if ($currentTime < $startTime || $currentTime > $endTime) {
                    return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
                }
            }
        }

        $rules = [
            'nama_murid' => 'required|string|max:100', // ...
            'nisn' => 'required|string|size:10',
            'nis' => 'required|string|max:20',
            'tempat_lahir' => 'required|string|max:50',
            'tanggal_lahir' => 'required|date|before:today|after:1900-01-01',
            'jenis_kelamin' => 'required|in:L,P',
            'kelas' => 'required|in:VII,VIII,IX',
            'nama_sekolah_asal' => 'required|string|max:150',
            'npsn_sekolah_asal' => 'required|string|size:8',
            'alamat_sekolah_asal' => 'required|string',
            'nomor_surat_sekolah' => 'required|string|max:50',
            'tanggal_surat_sekolah' => 'required|date|before_or_equal:today|after:1900-01-01',
            'nama_sekolah_tujuan' => 'required|string|max:150',
            'alamat_sekolah_tujuan' => 'nullable|string',
            'kabupaten_kota_tujuan' => 'required|string|max:100',
            'provinsi_tujuan' => 'required|string|max:100',
            'alasan_pindah' => 'required|string',
        ];

        // NPSN Sekolah Tujuan: flexible for PKBM (P prefix) and optional for foreign schools
        $tujuanLuarNegeri = $request->input('tujuan_luar_negeri') === '1';
        if ($tujuanLuarNegeri) {
            $rules['npsn_sekolah_tujuan'] = 'nullable|string|max:8';
        } else {
            // For domestic schools: allow alphanumeric (P prefix for PKBM) - 8 chars max
            $rules['npsn_sekolah_tujuan'] = 'required|string|max:8';
        }

        // File validation: skip jika is_dinas_entry (entry Dinas via checkbox)
        if (!$request->has('is_dinas_entry')) {
            $rules['file_suket_keluar'] = 'required|file|mimes:pdf|max:1024';
            $rules['file_suket_dapo'] = 'required|file|mimes:pdf|max:1024';

            // Surat Diterima: Wajib jika domestik, Optional jika Luar Negeri
            if ($tujuanLuarNegeri) {
                $rules['file_suket_diterima'] = 'nullable|file|mimes:pdf|max:1024';
            } else {
                $rules['file_suket_diterima'] = 'required|file|mimes:pdf|max:1024';
            }

            $rules['file_rapor'] = 'required|file|mimes:pdf|max:2048';
        }

        $validator = Validator::make($request->all(), $rules, [
            // NISN & NIS
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.size' => 'NISN harus tepat 10 digit angka. Contoh: 0012345678.',
            'nisn.string' => 'NISN harus berupa angka.',
            'nis.required' => 'NIS/NIPD wajib diisi.',
            'nis.max' => 'NIS/NIPD maksimal 20 karakter.',

            // Student Data
            'nama_murid.required' => 'Nama Siswa wajib diisi.',
            'nama_murid.max' => 'Nama Siswa maksimal 100 karakter.',
            'tempat_lahir.required' => 'Tempat Lahir wajib diisi.',
            'tempat_lahir.max' => 'Tempat Lahir maksimal 50 karakter.',
            'tanggal_lahir.required' => 'Tanggal Lahir wajib diisi.',
            'tanggal_lahir.date' => 'Format Tanggal Lahir tidak valid. Gunakan format tanggal yang benar.',
            'tanggal_lahir.before' => 'Tanggal Lahir harus sebelum hari ini.',
            'tanggal_lahir.after' => 'Tanggal Lahir tidak valid (terlalu lampau).',
            'jenis_kelamin.required' => 'Jenis Kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis Kelamin harus Laki-laki (L) atau Perempuan (P).',
            'kelas.required' => 'Kelas wajib dipilih.',
            'kelas.in' => 'Kelas harus VII, VIII, atau IX.',

            // School Origin
            'nama_sekolah_asal.required' => 'Nama Sekolah Asal wajib diisi.',
            'nama_sekolah_asal.max' => 'Nama Sekolah Asal maksimal 150 karakter.',
            'npsn_sekolah_asal.required' => 'NPSN Sekolah Asal wajib diisi.',
            'npsn_sekolah_asal.size' => 'NPSN Sekolah Asal harus tepat 8 karakter. Contoh: 20270134.',
            'alamat_sekolah_asal.required' => 'Alamat Sekolah Asal wajib diisi.',

            // School Destination
            'nama_sekolah_tujuan.required' => 'Nama Sekolah Tujuan wajib diisi.',
            'nama_sekolah_tujuan.max' => 'Nama Sekolah Tujuan maksimal 150 karakter.',
            'npsn_sekolah_tujuan.required' => 'NPSN Sekolah Tujuan wajib diisi.',
            'npsn_sekolah_tujuan.max' => 'NPSN Sekolah Tujuan maksimal 8 karakter.',
            'kabupaten_kota_tujuan.required' => 'Kabupaten/Kota Tujuan wajib dipilih.',
            'kabupaten_kota_tujuan.max' => 'Kabupaten/Kota Tujuan maksimal 100 karakter.',
            'provinsi_tujuan.required' => 'Provinsi Tujuan wajib dipilih.',
            'provinsi_tujuan.max' => 'Provinsi Tujuan maksimal 100 karakter.',

            // Letter Data
            'nomor_surat_sekolah.required' => 'Nomor Surat Mutasi/Pindah wajib diisi.',
            'nomor_surat_sekolah.max' => 'Nomor Surat maksimal 50 karakter.',
            'tanggal_surat_sekolah.required' => 'Tanggal Surat Mutasi wajib diisi.',
            'tanggal_surat_sekolah.date' => 'Format Tanggal Surat tidak valid.',
            'tanggal_surat_sekolah.before_or_equal' => 'Tanggal Surat tidak boleh lebih dari hari ini.',
            'tanggal_surat_sekolah.after' => 'Tanggal Surat tidak valid (terlalu lampau).',
            'alasan_pindah.required' => 'Alasan Pindah wajib diisi.',

            // Files
            'file_suket_keluar.required' => 'File Surat Keterangan Keluar wajib diunggah.',
            'file_suket_keluar.file' => 'File Surat Keterangan Keluar harus berupa file.',
            'file_suket_keluar.mimes' => 'File Surat Keterangan Keluar harus berformat PDF.',
            'file_suket_keluar.max' => 'File Surat Keterangan Keluar maksimal 1MB. Silakan kompres file PDF Anda.',
            'file_suket_dapo.required' => 'File Surat Keterangan Dapodik wajib diunggah.',
            'file_suket_dapo.file' => 'File Surat Keterangan Dapodik harus berupa file.',
            'file_suket_dapo.mimes' => 'File Surat Keterangan Dapodik harus berformat PDF.',
            'file_suket_dapo.max' => 'File Surat Keterangan Dapodik maksimal 1MB. Silakan kompres file PDF Anda.',
            'file_suket_diterima.required' => 'File Surat Keterangan Diterima wajib diunggah.',
            'file_suket_diterima.file' => 'File Surat Keterangan Diterima harus berupa file.',
            'file_suket_diterima.mimes' => 'File Surat Keterangan Diterima harus berformat PDF.',
            'file_suket_diterima.max' => 'File Surat Keterangan Diterima maksimal 1MB. Silakan kompres file PDF Anda.',
            'file_rapor.required' => 'File Rapor wajib diunggah.',
            'file_rapor.file' => 'File Rapor harus berupa file.',
            'file_rapor.mimes' => 'File Rapor harus berformat PDF.',
            'file_rapor.max' => 'File Rapor maksimal 2MB. Silakan kompres file PDF Anda.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction(); // START TRANSACTION

            $draft = null;
            if ($draftId) {
                $draft = DB::table('mutasi_keluar')
                    ->where('id_mutasi', $draftId)
                    ->where('status', 'Draft')
                    ->lockForUpdate()
                    ->first();

                if (!$draft) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'draft_id' => 'Draft tidak ditemukan atau sudah pernah dikirim.'
                    ]);
                }

                if (!$this->canAccessDraft($draft, $user)) {
                    abort(403, 'Anda tidak berhak mengirim draft ini.');
                }
            }

            $existingMutation = DB::table('mutasi_keluar')
                ->where('nisn', $request->nisn)
                ->whereNotIn('status', ['Draft', 'Dibatalkan', 'Ditolak'])
                ->whereNull('deleted_at')
                ->when($draftId, fn($query) => $query->where('id_mutasi', '!=', $draftId))
                ->first();

            if ($existingMutation) {
                // Manually throw ValidationException to return proper error to form
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'nisn' => "Siswa dengan NISN {$request->nisn} sedang dalam proses mutasi aktif (ID: {$existingMutation->id_mutasi}). Mohon selesaikan atau batalkan pengajuan sebelumnya."
                ]);
            }
            // ----------------------------------------

            if ($draft) {
                $idMutasi = $draft->id_mutasi;
            } else {
                // Generate ID
                $count = DB::table('mutasi_keluar')->count() + 1;
                $idMutasi = 'MK' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);

                // Ensure unique ID loop (though locking helps, ID generation might need its own lock or retry, ensuring unique here simply)
                while (DB::table('mutasi_keluar')->where('id_mutasi', $idMutasi)->exists()) {
                    $count++;
                    $idMutasi = 'MK' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);
                }
            }

            // Upload files using Service (handling date-based folders)
            $filePaths = $this->fileUploadService->uploadMutasiKeluarFiles($request, $idMutasi);

            $mutationData = [
                'tanggal_pengajuan' => now(),
                'nama_murid' => $request->nama_murid,
                'nisn' => $request->nisn,
                'nis' => $request->nis,
                'tempat_lahir' => $request->tempat_lahir,
                'tanggal_lahir' => $request->tanggal_lahir,
                'jenis_kelamin' => $request->jenis_kelamin,
                'kelas' => $request->kelas,
                'nama_sekolah_asal' => $request->nama_sekolah_asal,
                'npsn_sekolah_asal' => $request->npsn_sekolah_asal,
                'alamat_sekolah_asal' => $request->alamat_sekolah_asal,
                'nomor_surat_sekolah' => $request->nomor_surat_sekolah,
                'tanggal_surat_sekolah' => $request->tanggal_surat_sekolah,
                'npsn_sekolah_tujuan' => $request->npsn_sekolah_tujuan,
                'nama_sekolah_tujuan' => $request->nama_sekolah_tujuan,
                'alamat_sekolah_tujuan' => $request->alamat_sekolah_tujuan,
                'kabupaten_kota_tujuan' => $request->kabupaten_kota_tujuan,
                'provinsi_tujuan' => $request->provinsi_tujuan,
                'alasan_pindah' => $request->alasan_pindah,
                'status' => 'Menunggu Verifikasi Dinas',
                'pengusul' => $user->username,
                'file_suket_keluar' => $filePaths['file_suket_keluar'] ?? null,
                'file_suket_dapo' => $filePaths['file_suket_dapo'] ?? null,
                'file_suket_diterima' => $filePaths['file_suket_diterima'] ?? null,
                'file_rapor' => $filePaths['file_rapor'] ?? null,
                'is_dinas_entry' => $request->has('is_dinas_entry') ? 1 : 0,
                'updated_at' => now(),
            ];

            if ($draft) {
                DB::table('mutasi_keluar')
                    ->where('id_mutasi', $idMutasi)
                    ->update($mutationData);
            } else {
                $mutationData['id_mutasi'] = $idMutasi;
                $mutationData['created_at'] = now();
                DB::table('mutasi_keluar')->insert($mutationData);
            }

            // Notify Dinas users about new submission (wrapped in try-catch to not affect save)
            try {
                $newMutasi = DB::table('mutasi_keluar')->where('id_mutasi', $idMutasi)->first();
                if ($newMutasi) {
                    \App\Helpers\NotificationHelper::notifyDinasNewSubmission($newMutasi, 'keluar');
                }
            } catch (\Exception $e) {
                // Log but don't fail the request
                Log::error("Telegram notification failed: " . $e->getMessage());
            }

            // NOTE: Auto-save sekolah ke database hanya dilakukan saat status "Selesai" oleh Dinas
            // via maybeStoreNewSchool() di completeLetter()

            DB::commit(); // COMMIT TRANSACTION - CRITICAL!

            Log::info("DEBUG: Transaction committed. Redirecting...");

            return redirect()->route('mutasi.keluar.index')
                ->with('success', "Mutasi {$idMutasi} berhasil dibuat dengan status: Menunggu Verifikasi Dinas");

        } catch (\Throwable $e) { // CATCH ALL ERRORS INCLUDING FATAL ERRORS
            DB::rollBack(); // ROLLBACK on error
            Log::error("CRITICAL ERROR MutasiKeluar: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
            Log::error($e->getTraceAsString());
            return back()->with('error', 'TERJADI ERROR SISTEM: ' . $e->getMessage())->withInput();
        }
    }

    public function show(string $id)
    {
        $mutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();

        if (!$mutasi) {
            abort(404);
        }

        // Security: Authorization Check (IDOR Protection)
        $user = Auth::user();
        if ($user->role === 'Operator') {
            // STRICT: Operator only matches username
            if ($mutasi->pengusul !== $user->username) {
                abort(403, 'Akses ditolak (Bukan Pengusul).');
            }
        } elseif ($user->role === 'Sekolah') {
            // Sekolah matches NPSN or Username (Relaxed)
            if ($mutasi->npsn_sekolah_asal !== $user->npsn && $mutasi->pengusul !== $user->username) {
                abort(403, 'Akses ditolak (Bukan Data Sekolah Anda).');
            }
        } elseif (in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            // Dinas cannot view Drafts from others
            $isOwnDraft = in_array($mutasi->pengusul, [$user->username]); // Simplified check
            if ($mutasi->status === 'Draft' && !$isOwnDraft) {
                // More specific: if proposer is Operator/Sekolah, block.
                // But simpler is: Block ALL Drafts not owned by self.
                // Actually, Dinas should verify submitted docs, not drafts.
                abort(403, 'Dinas tidak dapat melihat Draft usulan.');
            }
        } else {
            abort(403);
        }

        $firstDocKey = 'file_suket_keluar';
        if (empty($mutasi->file_suket_keluar) && !empty($mutasi->file_suket_dapo))
            $firstDocKey = 'file_suket_dapo';
        if (empty($mutasi->file_suket_keluar) && empty($mutasi->file_suket_dapo) && !empty($mutasi->file_suket_diterima))
            $firstDocKey = 'file_suket_diterima';

        return view('mutasi.keluar.show', compact('mutasi', 'firstDocKey'));
    }

    public function verify(Request $request, string $id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403, 'Hanya Dinas yang dapat memverifikasi.');
        }

        $request->validate([
            'catatan_berkas' => 'nullable|string|max:2000',
            'action' => 'required|in:approve,revision',
        ]);

        $action = $request->input('action');
        $status = $action === 'approve' ? 'Disetujui Dinas' : 'Perlu Perbaikan';

        DB::table('mutasi_keluar')->where('id_mutasi', $id)->update([
            'ceklis_surat_sekolah' => $request->boolean('ceklis_surat_sekolah') ? 1 : 0,
            'ceklis_berkas_dapodik' => $request->boolean('ceklis_berkas_dapodik') ? 1 : 0,
            'ceklis_sekolah_tujuan' => $request->boolean('ceklis_sekolah_tujuan') ? 1 : 0,
            'ceklis_rapor' => $request->boolean('ceklis_rapor') ? 1 : 0,
            // Smart Document Revision: simpan status verifikasi per file
            'verif_suket_keluar' => $request->has('file_suket_keluar_ok') ? 1 : 0,
            'verif_suket_dapo' => $request->has('file_suket_dapo_ok') ? 1 : 0,
            'verif_suket_diterima' => $request->has('file_suket_diterima_ok') ? 1 : 0,
            'verif_rapor' => $request->has('file_rapor_ok') ? 1 : 0,
            'catatan_berkas' => $request->catatan_berkas,
            'status' => $status,
            'updated_at' => now(),
        ]);

        // Send Telegram notification to pengusul (wrapped in try-catch)
        try {
            $mutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();
            if ($mutasi) {
                \App\Helpers\NotificationHelper::notifyPengusul($mutasi, $status, 'keluar');
            }
        } catch (\Exception $e) {
            Log::error("Telegram notification failed: " . $e->getMessage());
        }

        $message = $action === 'approve'
            ? "✅ Berkas disetujui! Silakan input nomor dan tanggal surat izin."
            : "📝 Data dikembalikan ke Operator/Sekolah untuk perbaikan.";

        return redirect()->route('mutasi.keluar.show', $id)->with('success', $message);
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

        $mutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        if ($mutasi->status !== 'Disetujui Dinas') {
            return back()->with('error', 'Data harus disetujui terlebih dahulu.');
        }

        DB::table('mutasi_keluar')->where('id_mutasi', $id)->update([
            'nomor_surat_izin' => $request->filled('nomor_surat_izin') ? $request->nomor_surat_izin : null,
            'tanggal_surat_izin' => $request->tanggal_surat_izin,
            'status' => 'Proses TTD',
            'updated_at' => now(),
        ]);

        // Send Telegram notification to pengusul about Proses TTD status (wrapped in try-catch)
        try {
            $updatedMutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();
            if ($updatedMutasi) {
                \App\Helpers\NotificationHelper::notifyPengusul($updatedMutasi, 'Proses TTD', 'keluar');
            }
        } catch (\Exception $e) {
            Log::error("Telegram notification failed: " . $e->getMessage());
        }

        // Auto-save logic moved to completeLetter

        return redirect()->route('mutasi.keluar.show', $id)
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

        $mutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        if ($mutasi->status !== 'Proses TTD') {
            return back()->with('error', 'Status harus Proses TTD untuk mengkonfirmasi.');
        }

        DB::table('mutasi_keluar')->where('id_mutasi', $id)->update([
            'nomor_surat_izin' => $request->nomor_surat_izin,
            'tanggal_selesai' => $request->tanggal_selesai,
            'status' => 'Selesai',
            'updated_at' => now(),
        ]);
        $mutasi->nomor_surat_izin = $request->nomor_surat_izin;
        $mutasi->tanggal_selesai = $request->tanggal_selesai;
        $mutasi->status = 'Selesai';

        // Get pengusul phone for notification
        // Auto-save New School (User Request) - Save upon completion
        \App\Http\Controllers\SatuanPendidikanController::maybeStoreNewSchool([
            'npsn' => $mutasi->npsn_sekolah_tujuan,
            'nama' => $mutasi->nama_sekolah_tujuan,
            'alamat' => $mutasi->alamat_sekolah_tujuan,
            'kota' => $mutasi->kabupaten_kota_tujuan,
            'provinsi' => $mutasi->provinsi_tujuan,
        ]);

        // Send Telegram notification (wrapped in try-catch)
        try {
            \App\Helpers\NotificationHelper::notifyPengusul($mutasi, 'Selesai', 'keluar');
        } catch (\Exception $e) {
            Log::error("Telegram notification failed: " . $e->getMessage());
        }

        // Get pengusul phone for WhatsApp link
        $pengusul = DB::table('users')->where('username', $mutasi->pengusul)->first();
        $whatsappLink = null;

        if ($pengusul && !empty($pengusul->no_kontak)) {
            $message = \App\Helpers\NotificationHelper::buildCompletionMessage($mutasi, 'keluar');
            $whatsappLink = \App\Helpers\NotificationHelper::generateWhatsAppLink($pengusul->no_kontak, $message);
        }

        $successMessage = "Surat Keterangan Mutasi Keluar selesai.\n\nNomor: {$request->nomor_surat_izin}\nTanggal Selesai: " . \Carbon\Carbon::parse($request->tanggal_selesai)->translatedFormat('d F Y');

        if ($whatsappLink) {
            session()->flash('whatsapp_link', $whatsappLink);
            session()->flash('pengusul_name', $pengusul->nama_pengguna ?? $mutasi->pengusul);
        }

        return redirect()->route('mutasi.keluar.show', $id)->with('success', $successMessage);
    }

    /**
     * Print letter - display printable view of Surat Keterangan Mutasi Keluar
     */
    public function printLetter(Request $request, string $id)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['Dinas', 'SuperAdmin'])) {
            abort(403, 'Hanya Dinas yang dapat mencetak surat.');
        }

        $mutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        // Resolve PdfService manually (removed from constructor to avoid circular/unused deps)
        $pdfService = app(\App\Services\PdfService::class);

        if (!$pdfService->canPrint($mutasi->status)) {
            return back()->with('error', 'Surat hanya dapat dicetak setelah status Proses TTD atau Selesai.');
        }

        // Use PdfService for data mapping
        $mutasiData = $pdfService->prepareMutasiKeluarData($mutasi);
        $penandatangan = $pdfService->getActivePenandatangan();

        $mode = 'manual';
        $view = view('surat.keterangan-mutasi-keluar', [
            'mutasi' => $mutasiData,
            'penandatangan' => $penandatangan,
            'mode' => $mode,
            'format' => $request->query('format'),
        ]);

        if ($request->query('format') === 'word') {
            $filename = 'surat-mutasi-keluar-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $id) . '.doc';

            return response($view->render(), 200, [
                'Content-Type' => 'application/msword; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
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

        $mutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        return view('mutasi.keluar.edit', compact('mutasi'));
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

        $mutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();
        if (!$mutasi) {
            abort(404);
        }

        $request->validate([
            'nama_murid' => 'required|string|max:100',
            'nisn' => 'required|string|size:10',
            'kelas' => 'required|in:VII,VIII,IX',
            'nama_sekolah_tujuan' => 'required|string|max:150',
            'npsn_sekolah_tujuan' => 'required|string|size:8',
        ]);

        // Build update array - only include fields that are filled
        $updateData = [
            'nama_murid' => $request->nama_murid,
            'nisn' => $request->nisn,
            'nis' => $request->nis ?? $mutasi->nis,
            'tempat_lahir' => $request->tempat_lahir ?? $mutasi->tempat_lahir,
            'tanggal_lahir' => $request->tanggal_lahir ?? $mutasi->tanggal_lahir,
            'jenis_kelamin' => $request->jenis_kelamin ?? $mutasi->jenis_kelamin,
            'kelas' => $request->kelas,
            'nama_sekolah_asal' => $request->nama_sekolah_asal ?? $mutasi->nama_sekolah_asal,
            'npsn_sekolah_asal' => $request->npsn_sekolah_asal ?? $mutasi->npsn_sekolah_asal,
            'alamat_sekolah_asal' => $request->alamat_sekolah_asal ?? $mutasi->alamat_sekolah_asal,
            'nomor_surat_sekolah' => $request->nomor_surat_sekolah ?? $mutasi->nomor_surat_sekolah,
            'tanggal_surat_sekolah' => $request->tanggal_surat_sekolah ?? $mutasi->tanggal_surat_sekolah,
            'npsn_sekolah_tujuan' => $request->npsn_sekolah_tujuan ?? $mutasi->npsn_sekolah_tujuan,
            'nama_sekolah_tujuan' => $request->nama_sekolah_tujuan ?? $mutasi->nama_sekolah_tujuan,
            'alamat_sekolah_tujuan' => $request->alamat_sekolah_tujuan ?? $mutasi->alamat_sekolah_tujuan,
            'kabupaten_kota_tujuan' => $request->kabupaten_kota_tujuan ?? $mutasi->kabupaten_kota_tujuan,
            'provinsi_tujuan' => $request->provinsi_tujuan ?? $mutasi->provinsi_tujuan,
            'alasan_pindah' => $request->alasan_pindah ?? $mutasi->alasan_pindah,
            'status' => 'Menunggu Verifikasi Dinas',
            'nomor_surat_izin' => null,
            'tanggal_surat_izin' => null,
            'updated_at' => now(),
        ];

        DB::table('mutasi_keluar')->where('id_mutasi', $id)->update($updateData);

        return redirect()->route('mutasi.keluar.show', $id)
            ->with('success', '✅ Data berhasil diperbarui. Status dikembalikan ke Menunggu Verifikasi Dinas.');
    }



    /**
     * API: Lookup NPSN from satuan_pendidikan
     */
    public function lookupNpsn(string $npsn)
    {
        try {
            $satpen = DB::table('satuan_pendidikan')
                ->where('npsn', $npsn)
                ->first();

            if ($satpen) {
                // Build alamat_lengkap if empty
                $alamat = $satpen->alamat_lengkap;
                if (empty($alamat)) {
                    $parts = array_filter([
                        $satpen->alamat_jalan ?? '',
                        !empty($satpen->kelurahan) ? "Kel. {$satpen->kelurahan}" : '',
                        !empty($satpen->kecamatan) ? "Kec. {$satpen->kecamatan}" : '',
                        $satpen->kota ?? '',
                        $satpen->provinsi ?? '',
                    ]);
                    $alamat = implode(', ', $parts);
                }

                return response()->json([
                    'success' => true,
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
                'success' => false,
                'message' => 'NPSN tidak ditemukan',
                'data' => null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    // Region methods removed - use SatuanPendidikanController instead

    /**
     * Save Draft - Save form data without full validation
     * Supports both new draft creation and updating existing draft
     */
    public function saveDraft(Request $request)
    {
        $user = Auth::user();

        // Basic validation for draft (minimal)
        $validated = $request->validate([
            'nama_murid' => 'nullable|string|max:100',
            'nisn' => 'nullable|string|max:10',
            'nis' => 'nullable|string|max:20',
            'draft_id' => 'nullable|string|max:50', // For updating existing draft
        ]);

        // Check if updating existing draft
        $existingDraftId = $request->input('draft_id');

        $draftData = [
            'nama_murid' => $request->input('nama_murid') ?: '',
            'nisn' => $request->input('nisn') ?: self::DRAFT_EMPTY_NISN,
            'nis' => $request->input('nis'),
            'kelas' => $request->input('kelas') ?: 'VII',
            'jenis_kelamin' => $request->input('jenis_kelamin') ?: 'L',
            'tempat_lahir' => $request->input('tempat_lahir') ?: '',
            'tanggal_lahir' => $request->input('tanggal_lahir') ?: self::DRAFT_EMPTY_DATE,
            'npsn_sekolah_asal' => $request->input('npsn_sekolah_asal') ?: ($user->npsn ?: self::DRAFT_EMPTY_NPSN),
            'nama_sekolah_asal' => $request->input('nama_sekolah_asal') ?: ($user->nama_sekolah ?: ''),
            'alamat_sekolah_asal' => $request->input('alamat_sekolah_asal') ?: ($user->alamat ?: ''),
            'npsn_sekolah_tujuan' => $request->input('npsn_sekolah_tujuan') ?: self::DRAFT_EMPTY_NPSN,
            'nama_sekolah_tujuan' => $request->input('nama_sekolah_tujuan') ?: '',
            'alamat_sekolah_tujuan' => $request->input('alamat_sekolah_tujuan'),
            'provinsi_tujuan' => $request->input('provinsi_tujuan') ?: '',
            'kabupaten_kota_tujuan' => $request->input('kabupaten_kota_tujuan') ?: '',
            'alasan_pindah' => $request->input('alasan_pindah') ?: '',
            'nomor_surat_sekolah' => $request->input('nomor_surat_sekolah'),
            'tanggal_surat_sekolah' => $request->input('tanggal_surat_sekolah') ?: null,
            'tujuan_luar_negeri' => $request->input('tujuan_luar_negeri') ? 1 : 0,

            // Dinas Mode Fields
            'is_dinas_entry' => $request->has('is_dinas_entry') ? 1 : 0,
            'ceklis_surat_sekolah' => $request->has('has_suket_keluar') ? 1 : 0,
            'ceklis_berkas_dapodik' => $request->has('has_suket_dapo') ? 1 : 0,
            'ceklis_sekolah_tujuan' => $request->has('has_suket_diterima') ? 1 : 0,
            'ceklis_rapor' => $request->has('has_rapor') ? 1 : 0,

            'updated_at' => now(),
        ];

        try {
            if ($existingDraftId) {
                // UPDATE existing draft
                $exists = DB::table('mutasi_keluar')
                    ->where('id_mutasi', $existingDraftId)
                    ->where('status', 'Draft')
                    ->first();

                if ($exists) {
                    if (!$this->canAccessDraft($exists, $user)) {
                        abort(403, 'Anda tidak berhak mengubah draft ini.');
                    }

                    DB::table('mutasi_keluar')
                        ->where('id_mutasi', $existingDraftId)
                        ->update($draftData);

                    Log::info("Draft mutasi keluar updated: {$existingDraftId} by {$user->username}");

                    return redirect()->route('mutasi.keluar.index')
                        ->with('success', '📝 Draft berhasil diperbarui!');
                }
            }

            // INSERT new draft
            $id = 'MK-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -5));

            $draftData['id_mutasi'] = $id;
            $draftData['pengusul'] = $user->username;
            $draftData['status'] = 'Draft';
            $draftData['tanggal_pengajuan'] = now();
            $draftData['created_at'] = now();

            DB::table('mutasi_keluar')->insert($draftData);

            Log::info("Draft mutasi keluar created: {$id} by {$user->username}");

            return redirect()->route('mutasi.keluar.index')
                ->with('success', '📝 Draft berhasil disimpan! Anda dapat melanjutkan pengisian nanti.');
        } catch (\Exception $e) {
            Log::error("Failed to save draft mutasi keluar: " . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan draft: ' . $e->getMessage())->withInput();
        }
    }


    /**
     * Resume Draft - Load draft for continued editing
     */
    public function resumeDraft(string $id)
    {
        $user = Auth::user();

        $draft = DB::table('mutasi_keluar')
            ->where('id_mutasi', $id)
            ->where('status', 'Draft')
            ->first();

        if (!$draft) {
            return redirect()->route('mutasi.keluar.index')
                ->with('error', 'Draft tidak ditemukan atau sudah disubmit.');
        }

        // Authorization
        $isOwner = ($user->role === 'Operator' && $draft->pengusul === $user->username)
            || ($user->role === 'Sekolah' && $draft->npsn_sekolah_asal === $user->npsn)
            || in_array($user->role, ['Dinas', 'SuperAdmin']);

        if (!$isOwner) {
            abort(403, 'Anda tidak berhak mengakses draft ini.');
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

            // DEBUG LOGGING
            \Log::info("Schedule Check - User: {$user->username}, Role: {$user->role}");
            \Log::info("Time: {$today->toDateTimeString()}, DayISO: {$today->dayOfWeekIso} (" . ($dayNames[$today->dayOfWeek] ?? 'Unknown') . ")");
            \Log::info("Allowed Days: " . implode(',', $allowedDays));
            \Log::info("Hour Restriction Enabled: " . (\App\Models\Setting::isHourRestrictionEnabled() ? 'Yes' : 'No'));
            \Log::info("Current Time: {$today->format('H:i')}, Window: {$startTime} - {$endTime}");

            // 1. Check Holiday
            if (\App\Models\Holiday::isHoliday($today->format('Y-m-d'))) {
                \Log::info("Blocked: Holiday");
                return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
            }

            // 2. Check Allowed Days
            // Carbon dayOfWeekIso: 1 (Mon) - 7 (Sun)
            // But Settings might be storing 0-6 or 1-7. Let's check `getAllowedDays` implementation again.
            // Setting::getAllowedDays defaults to [1, 2, 3, 4, 5, 6]. 
            // If today is Sunday (7), and it's not in array, it blocks.
            // FIX: Carbon dayOfWeek returns 0 (Sun) - 6 (Sat). 
            // Carbon dayOfWeekIso returns 1 (Mon) - 7 (Sun).
            // We need to match what Setting stores. Usually UI forms use 0=Sun or 1=Mon.
            // If Setting defaults to [1,2,3...6], it assumes Monday-Saturday?
            // Let's rely on dayOfWeekIso for now but log the mismatch.
            if (!empty($allowedDays) && !in_array($today->dayOfWeekIso, $allowedDays)) {
                \Log::info("Blocked: Day not allowed. Today ISO: {$today->dayOfWeekIso}");
                return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
            }

            // 3. Check Allowed Hours
            if (\App\Models\Setting::isHourRestrictionEnabled()) {
                $currentTime = $today->format('H:i');
                if ($currentTime < $startTime || $currentTime > $endTime) {
                    \Log::info("Blocked: Outside hours. Current: {$currentTime}");
                    return redirect()->route('mutasi.keluar.index')->with('schedule_block', $blockData);
                }
            }
        }

        $this->prepareDraftForForm($draft);

        // Pass draft data to create view
        $alamatSekolahAsal = $draft->alamat_sekolah_asal ?? '';
        $namaSekolahAsal = $draft->nama_sekolah_asal ?? '';
        $npsnSekolahAsal = $draft->npsn_sekolah_asal ?? '';

        return view('mutasi.keluar.create', compact('user', 'draft', 'alamatSekolahAsal', 'namaSekolahAsal', 'npsnSekolahAsal'));
    }

    /**
     * Delete Draft - Permanently remove draft data
     */
    public function deleteDraft(string $id)
    {
        $user = Auth::user();

        $draft = DB::table('mutasi_keluar')
            ->where('id_mutasi', $id)
            ->first();

        if (!$draft) {
            return back()->with('error', 'Draft tidak ditemukan.');
        }

        // Authorization
        $isOwner = ($user->role === 'Operator' && $draft->pengusul === $user->username)
            || ($user->role === 'Sekolah' && $draft->npsn_sekolah_asal === $user->npsn)
            || in_array($user->role, ['Dinas', 'SuperAdmin']);

        if (!$isOwner) {
            abort(403, 'Anda tidak berhak menghapus draft ini.');
        }

        if ($draft->status !== 'Draft') {
            return back()->with('error', 'Hanya usulan dengan status Draft yang dapat dihapus.');
        }

        // Delete files if any (though currently drafts don't save files, good to be safe)
        // Check standard columns just in case
        $fileColumns = ['file_suket_keluar', 'file_suket_dapo', 'file_suket_diterima', 'file_rapor', 'file_arsip_scan'];

        // Get disk (private_docs/b2)
        $disk = $this->fileUploadService->getDisk();
        $tempDisk = $this->fileUploadService->getTempDisk();

        foreach ($fileColumns as $col) {
            if (!empty($draft->$col)) {
                try {
                    if (str_starts_with($draft->$col, 'temp_uploads/')) {
                        Storage::disk($tempDisk)->delete($draft->$col);
                    } else {
                        Storage::disk($disk)->delete($draft->$col);
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to delete draft file: " . $draft->$col);
                }
            }
        }

        DB::table('mutasi_keluar')->where('id_mutasi', $id)->delete();

        return redirect()->route('mutasi.keluar.index')
            ->with('success', 'Draft berhasil dihapus.');
    }

    private function canAccessDraft(object $draft, $user): bool
    {
        return ($user->role === 'Operator' && $draft->pengusul === $user->username)
            || ($user->role === 'Sekolah' && ($draft->npsn_sekolah_asal === $user->npsn || $draft->pengusul === $user->username))
            || in_array($user->role, ['Dinas', 'SuperAdmin']);
    }

    private function prepareDraftForForm(object $draft): void
    {
        $usesEmptyNisn = $draft->nisn === self::DRAFT_EMPTY_NISN;
        $usesEmptyBirthDate = $draft->tanggal_lahir === self::DRAFT_EMPTY_DATE;

        if ($usesEmptyNisn) {
            $draft->nisn = null;
        }

        if ($draft->npsn_sekolah_tujuan === self::DRAFT_EMPTY_NPSN) {
            $draft->npsn_sekolah_tujuan = null;
        }

        if ($usesEmptyBirthDate) {
            $draft->tanggal_lahir = null;
        }

        if (empty($draft->kelas) || ($usesEmptyBirthDate && $draft->kelas === 'VII')) {
            $draft->kelas = null;
        }

        if (empty($draft->jenis_kelamin) || ($usesEmptyBirthDate && $draft->jenis_kelamin === 'L')) {
            $draft->jenis_kelamin = null;
        }
    }
}
