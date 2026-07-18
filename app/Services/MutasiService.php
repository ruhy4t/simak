<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SatuanPendidikan;
use App\Helpers\NotificationHelper;

/**
 * MutasiService - Handles business logic for mutation operations
 * 
 * Extracted from MutasiController and MutasiMasukController to reduce fat controller issue.
 * Role/Authorization checks remain in controllers - this service only handles data operations.
 */
class MutasiService
{
    /**
     * Sanitize string input - remove dangerous characters
     */
    public function sanitize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        // Remove null bytes and control characters
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);

        // Trim whitespace
        return trim($input);
    }

    /**
     * Sanitize filename - only allow safe characters
     */
    public function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
    }

    /**
     * Validate and sanitize NPSN
     * Allows: digits (regular schools), P prefix (PKBM), LN- prefix (Luar Negeri)
     */
    public function sanitizeNpsn(?string $npsn): ?string
    {
        if ($npsn === null || $npsn === '' || $npsn === '-') {
            return null;
        }

        // Allow alphanumeric, dash, and underscore (for PKBM and foreign schools)
        $sanitized = preg_replace('/[^a-zA-Z0-9\-]/', '', $npsn);

        // Return uppercase to normalize
        return strtoupper($sanitized);
    }

    /**
     * Validate and sanitize NISN (digits only)
     */
    public function sanitizeNisn(?string $nisn): ?string
    {
        if ($nisn === null || $nisn === '') {
            return null;
        }
        // Remove non-digits and return
        return preg_replace('/[^0-9]/', '', $nisn);
    }

    /**
     * Generate unique Mutasi ID (Keluar)
     */
    public function generateMutasiKeluarId(): string
    {
        $count = DB::table('mutasi_keluar')->count() + 1;
        $idMutasi = 'MK' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);

        // Ensure unique
        while (DB::table('mutasi_keluar')->where('id_mutasi', $idMutasi)->exists()) {
            $count++;
            $idMutasi = 'MK' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        return $idMutasi;
    }

    /**
     * Generate unique Mutasi ID (Masuk)
     */
    public function generateMutasiMasukId(): string
    {
        $count = DB::table('mutasi_masuk')->count() + 1;
        $idMutasi = 'MM' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);

        // Ensure unique
        while (DB::table('mutasi_masuk')->where('id_mutasi', $idMutasi)->exists()) {
            $count++;
            $idMutasi = 'MM' . date('Ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);
        }

        return $idMutasi;
    }

    /**
     * Check for duplicate active mutation for NISN (Keluar)
     * 
     * @throws \Illuminate\Validation\ValidationException
     */
    public function checkDuplicateNisnKeluar(string $nisn): void
    {
        $existingMutation = DB::table('mutasi_keluar')
            ->where('nisn', $nisn)
            ->whereNotIn('status', ['Dibatalkan', 'Ditolak'])
            ->whereNull('deleted_at')
            ->lockForUpdate()
            ->first();

        if ($existingMutation) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'nisn' => "Siswa dengan NISN {$nisn} sedang dalam proses mutasi aktif (ID: {$existingMutation->id_mutasi}). Mohon selesaikan atau batalkan pengajuan sebelumnya."
            ]);
        }
    }

    /**
     * Update Mutasi Keluar status with notification
     */
    public function updateMutasiKeluarStatus(string $id, string $status, ?string $catatan = null): object
    {
        $previousStatus = DB::table('mutasi_keluar')->where('id_mutasi', $id)->value('status');
        $updateData = [
            'status' => $status,
            'updated_at' => now(),
        ];

        if ($catatan !== null) {
            $updateData['catatan_berkas'] = $catatan;
        }

        DB::table('mutasi_keluar')->where('id_mutasi', $id)->update($updateData);
        MutationStatisticsCache::invalidate();

        $mutasi = DB::table('mutasi_keluar')->where('id_mutasi', $id)->first();

        // Notify pengusul about status change
        if ($mutasi) {
            NotificationHelper::notifyPengusulOnStatusChange($mutasi, $previousStatus, $status, 'keluar');
        }

        return $mutasi;
    }

    /**
     * Update Mutasi Masuk status with notification
     */
    public function updateMutasiMasukStatus(string $id, string $status, ?string $catatan = null): object
    {
        $previousStatus = DB::table('mutasi_masuk')->where('id_mutasi', $id)->value('status');
        $updateData = [
            'status' => $status,
            'updated_at' => now(),
        ];

        if ($catatan !== null) {
            $updateData['catatan_berkas'] = $this->sanitize($catatan);
        }

        DB::table('mutasi_masuk')->where('id_mutasi', $id)->update($updateData);
        MutationStatisticsCache::invalidate();

        $mutasi = DB::table('mutasi_masuk')->where('id_mutasi', $id)->first();

        // Notify pengusul about status change
        if ($mutasi) {
            NotificationHelper::notifyPengusulOnStatusChange($mutasi, $previousStatus, $status, 'masuk');
        }

        return $mutasi;
    }

    /**
     * Complete Mutasi Keluar - Save school reference and send notifications
     */
    public function completeMutasiKeluar(object $mutasi, string $tanggalSelesai): array
    {
        $previousStatus = $mutasi->status ?? null;
        // Update status to Selesai
        DB::table('mutasi_keluar')->where('id_mutasi', $mutasi->id_mutasi)->update([
            'tanggal_selesai' => $tanggalSelesai,
            'status' => 'Selesai',
            'updated_at' => now(),
        ]);
        MutationStatisticsCache::invalidate();

        // Auto-save new school reference
        \App\Http\Controllers\SatuanPendidikanController::maybeStoreNewSchool([
            'npsn' => $mutasi->npsn_sekolah_tujuan,
            'nama' => $mutasi->nama_sekolah_tujuan,
            'alamat' => $mutasi->alamat_sekolah_tujuan,
            'kota' => $mutasi->kabupaten_kota_tujuan,
            'provinsi' => $mutasi->provinsi_tujuan,
        ]);

        // Send Telegram notification
        NotificationHelper::notifyPengusulOnStatusChange($mutasi, $previousStatus, 'Selesai', 'keluar');

        return [
            'pengusul' => DB::table('users')->where('username', $mutasi->pengusul)->first(),
        ];
    }

    /**
     * Complete Mutasi Masuk - Save school references and send notifications
     */
    public function completeMutasiMasuk(object $mutasi, string $tanggalSelesai): array
    {
        $id = $mutasi->id_mutasi;
        $previousStatus = $mutasi->status ?? null;

        // Update status to Selesai
        DB::table('mutasi_masuk')->where('id_mutasi', $id)->update([
            'tanggal_selesai' => $tanggalSelesai,
            'status' => 'Selesai',
            'updated_at' => now(),
        ]);
        MutationStatisticsCache::invalidate();

        // Get siswa list and build student names
        $siswaList = DB::table('mutasi_masuk_siswa')->where('id_mutasi', $id)->get();
        $namaSiswa = $siswaList->pluck('nama_murid')->implode(', ');
        $mutasiWithSiswa = (object) array_merge((array) $mutasi, ['nama_siswa' => $namaSiswa]);

        // Auto-save school references for each student
        foreach ($siswaList as $student) {
            \App\Http\Controllers\SatuanPendidikanController::maybeStoreNewSchool([
                'npsn' => $student->npsn_sekolah_asal,
                'nama' => $student->nama_sekolah_asal,
                'alamat' => $student->alamat_sekolah_asal,
                'kota' => $student->kabupaten_kota_asal,
                'provinsi' => $student->provinsi_asal,
            ]);
        }

        // Send Telegram notification
        NotificationHelper::notifyPengusulOnStatusChange($mutasiWithSiswa, $previousStatus, 'Selesai', 'masuk');

        Log::info("Surat Izin Mutasi Masuk {$id} selesai");

        return [
            'pengusul' => DB::table('users')->where('username', $mutasi->pengusul)->first(),
            'siswa_list' => $siswaList,
        ];
    }

    /**
     * Get validation rules for Mutasi Keluar
     */
    public function getMutasiKeluarValidationRules(bool $tujuanLuarNegeri, bool $isDinasEntry): array
    {
        $rules = [
            'nama_murid' => 'required|string|max:100',
            'nisn' => 'required|string|size:10',
            'nis' => 'required|string|max:20',
            'tempat_lahir' => 'required|string|max:50',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'kelas' => 'required|in:VII,VIII,IX',
            'nama_sekolah_asal' => 'required|string|max:150',
            'npsn_sekolah_asal' => 'required|string|size:8',
            'alamat_sekolah_asal' => 'required|string',
            'nomor_surat_sekolah' => 'required|string|max:50',
            'tanggal_surat_sekolah' => 'required|date',
            'nama_sekolah_tujuan' => 'required|string|max:150',
            'alamat_sekolah_tujuan' => 'nullable|string',
            'kabupaten_kota_tujuan' => 'required|string|max:100',
            'provinsi_tujuan' => 'required|string|max:100',
            'alasan_pindah' => 'required|string',
        ];

        // NPSN Sekolah Tujuan: optional for foreign schools
        if ($tujuanLuarNegeri) {
            $rules['npsn_sekolah_tujuan'] = 'nullable|string|max:8';
        } else {
            $rules['npsn_sekolah_tujuan'] = 'required|string|max:8';
        }

        // File validation: skip if is_dinas_entry
        if (!$isDinasEntry) {
            $rules['file_suket_keluar'] = 'required|file|mimes:pdf|max:1024';
            $rules['file_suket_dapo'] = 'required|file|mimes:pdf|max:1024';
            $rules['file_suket_diterima'] = 'required|file|mimes:pdf|max:1024';
            $rules['file_rapor'] = 'required|file|mimes:pdf|max:5120';
        }

        return $rules;
    }

    /**
     * Get validation messages for Mutasi Keluar
     */
    public function getMutasiKeluarValidationMessages(): array
    {
        return [
            // NISN & NIS
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.size' => 'NISN harus tepat 10 digit angka. Contoh: 0012345678.',
            'nisn.string' => 'NISN harus berupa angka.',
            'nis.required' => 'NIS/NIPD wajib diisi.',

            // NPSN
            'npsn_sekolah_asal.required' => 'NPSN Sekolah Asal wajib diisi.',
            'npsn_sekolah_asal.size' => 'NPSN Sekolah Asal harus tepat 8 karakter.',
            'npsn_sekolah_tujuan.required' => 'NPSN Sekolah Tujuan wajib diisi.',
            'npsn_sekolah_tujuan.max' => 'NPSN Sekolah Tujuan maksimal 8 karakter.',

            // Student Data
            'nama_murid.required' => 'Nama Siswa wajib diisi.',
            'tempat_lahir.required' => 'Tempat Lahir wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal Lahir wajib diisi.',
            'tanggal_lahir.before' => 'Tanggal Lahir harus sebelum hari ini.',
            'jenis_kelamin.required' => 'Jenis Kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis Kelamin harus Laki-laki (L) atau Perempuan (P).',
            'kelas.required' => 'Kelas wajib dipilih.',
            'kelas.in' => 'Kelas harus VII, VIII, atau IX.',

            // School Data
            'nama_sekolah_asal.required' => 'Nama Sekolah Asal wajib diisi.',
            'alamat_sekolah_asal.required' => 'Alamat Sekolah Asal wajib diisi.',
            'nama_sekolah_tujuan.required' => 'Nama Sekolah Tujuan wajib diisi.',
            'kabupaten_kota_tujuan.required' => 'Kabupaten/Kota Tujuan wajib dipilih.',
            'provinsi_tujuan.required' => 'Provinsi Tujuan wajib dipilih.',

            // Letter Data
            'nomor_surat_sekolah.required' => 'Nomor Surat Mutasi wajib diisi.',
            'tanggal_surat_sekolah.required' => 'Tanggal Surat Mutasi wajib diisi.',
            'tanggal_surat_sekolah.before_or_equal' => 'Tanggal Surat tidak boleh lebih dari hari ini.',
            'alasan_pindah.required' => 'Alasan Pindah wajib diisi.',

            // Files
            'file_suket_keluar.required' => 'File Surat Keterangan Keluar wajib diunggah.',
            'file_suket_keluar.mimes' => 'File Surat Keterangan Keluar harus berformat PDF.',
            'file_suket_keluar.max' => 'File Surat Keterangan Keluar maksimal 1MB.',
            'file_suket_dapo.required' => 'File Surat Keterangan Dapodik wajib diunggah.',
            'file_suket_dapo.mimes' => 'File Surat Keterangan Dapodik harus berformat PDF.',
            'file_suket_dapo.max' => 'File Surat Keterangan Dapodik maksimal 1MB.',
            'file_suket_diterima.required' => 'File Surat Keterangan Diterima wajib diunggah.',
            'file_suket_diterima.mimes' => 'File Surat Keterangan Diterima harus berformat PDF.',
            'file_suket_diterima.max' => 'File Surat Keterangan Diterima maksimal 1MB.',
            'file_rapor.required' => 'File Rapor wajib diunggah.',
            'file_rapor.mimes' => 'File Rapor harus berformat PDF.',
            'file_rapor.max' => 'File Rapor maksimal 5MB.',
        ];
    }
}
