<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * FileUploadService - Handles file upload operations for mutations
 * 
 * Extracted from MutasiController and MutasiMasukController.
 * Provides secure file upload with validation and sanitization.
 */
class FileUploadService
{
    /**
     * Default upload path relative to public/storage
     */
    protected string $uploadPath = 'dokumen';

    /**
     * Get the full upload path
     */
    public function getUploadPath(): string
    {
        // Use PRIVATE storage path (not in public/storage)
        $fullPath = storage_path('app/dokumen_rahasia');

        // Note: The $uploadPath property itself 'dokumen' is now just used for structure,
        // but the base root is different. We assume 'dokumen_rahasia' is the root for all uploads.

        // Create directory if not exists
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        return $fullPath;
    }

    /**
     * Get full path for date-based folder and ensure it exists
     */
    public function getFullDateBasedPath(string $type = 'keluar'): string
    {
        $subDir = $this->getDateBasedPath($type);
        $subDir = $this->getDateBasedPath($type);
        // Relative path logic remains same for DB storage, but physical location changes
        $relativePath = $this->uploadPath . '/' . $subDir;

        $fullPath = storage_path('app/dokumen_rahasia/' . $subDir);

        // Remove 'dokumen/' redundancy if $uploadPath is 'dokumen' but we store in 'dokumen_rahasia' root directly?
        // Let's keep structure consistent: storage/app/dokumen_rahasia/mutasi_keluar/2025/...
        // So we don't need 'dokumen' subfolder inside 'dokumen_rahasia' if we don't want to.
        // But to minimize changes, let's keep it simple.

        // Actually, let's map:
        // Public: public/storage/dokumen/mutasi_keluar/...
        // Private: storage/app/dokumen_rahasia/mutasi_keluar/... (drop 'dokumen' subfolder to be cleaner)

        $fullPath = storage_path('app/dokumen_rahasia/' . $subDir);

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        return $fullPath; // Returns absolute path
    }

    /**
     * Get relative path for date-based folder
     */
    public function getRelativeDateBasedPath(string $type = 'keluar'): string
    {
        $subDir = $this->getDateBasedPath($type);
        return $this->uploadPath . '/' . $subDir;
    }

    /**
     * Sanitize filename - only allow safe characters
     */
    public function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
    }

    /**
     * Delete old file from storage to save space
     * Used during revision/replacement uploads
     * 
     * @param string|null $oldPath Relative path to the old file (as stored in DB)
     * @return bool True if file was deleted or didn't exist, false on error
     */
    public function deleteOldFile(?string $oldPath): bool
    {
        if (empty($oldPath)) {
            return true; // No file to delete
        }

        try {
            // Build full path - files are stored in dokumen_rahasia
            $fullPath = storage_path('app/dokumen_rahasia/' . ltrim($oldPath, '/'));

            if (file_exists($fullPath)) {
                unlink($fullPath);
                Log::info("Deleted old file for replacement: {$oldPath}");
                return true;
            }

            return true; // File didn't exist, that's fine
        } catch (\Exception $e) {
            Log::warning("Failed to delete old file: {$oldPath}. Error: " . $e->getMessage());
            return false; // Continue even if delete fails
        }
    }

    /**
     * Upload a single PDF file with security validation
     * 
     * @param UploadedFile $file The uploaded file
     * @param string $prefix Prefix for the filename (usually mutation ID)
     * @param string $suffix Suffix for the filename (usually field name)
     * @return string Relative path to the uploaded file
     * @throws \Exception If file is not a valid PDF
     */
    /**
     * Upload a single PDF file with security validation
     * 
     * @param UploadedFile $file The uploaded file
     * @param string $prefix Prefix for the filename
     * @param string $suffix Suffix for the filename
     * @param string|null $subDir Optional subdirectory (e.g. 'mutasi_keluar/2025/12')
     * @return string Relative path to the uploaded file
     * @throws \Exception If file is not a valid PDF
     */
    public function uploadPdf(UploadedFile $file, string $prefix, string $suffix, ?string $subDir = null): string
    {
        // Validate extension
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'pdf') {
            throw new \Exception('Hanya file PDF yang diperbolehkan.');
        }

        // Validate MIME type for extra security
        $mimeType = $file->getMimeType();
        if ($mimeType !== 'application/pdf') {
            throw new \Exception('File harus berformat PDF.');
        }

        // Build safe filename
        $safePrefix = $this->sanitizeFilename($prefix);
        $safeSuffix = $this->sanitizeFilename($suffix);
        $filename = "{$safePrefix}_{$safeSuffix}.pdf";

        // Determine target path
        // Determine target path
        // $relativePath is what we store in DB. It used to be 'dokumen/mutasi_keluar/...'
        // We will keep the SAME relative path format in DB to minimize breaking changes?
        // NO. If we serve via route, we can pass whatever path.
        // Let's standardise: "mutasi_keluar/2025/12/filename.pdf" (without 'dokumen/' prefix if possible)
        // BUT current DB has 'dokumen/...' prefix likely.
        // Let's check `uploadPath` value. It is 'dokumen'.

        // Strategy:
        // Physical: storage/app/dokumen_rahasia/mutasi_keluar/2025/...
        // DB link: mutasi_keluar/2025/12/filename.pdf 
        // (We REMOVE 'dokumen/' prefix from DB to make it cleaner and because we route to 'dokumen_rahasia' root).

        // If we change DB format, we break existing links unless we migrate DB too.
        // Current DB likely has: "dokumen/mutasi_keluar/..."

        // Let's stick to: Store in `storage/app/dokumen_rahasia/dokumen/...` to match exact structure?
        // Or better: Just store in `storage/app/dokumen_rahasia/...` and if DB string has `dokumen/`, we strip it or handle it in Controller.

        // Decision: 
        // Physical: storage/app/dokumen_rahasia/mutasi_keluar/...
        // DB: mutasi_keluar/2025/12/filename.pdf (Cleaner)
        // Implementation:

        if ($subDir) {
            $cleanSubDir = trim($subDir, '/'); // e.g. mutasi_keluar/2025/12
            $fullDestPath = storage_path('app/dokumen_rahasia/' . $cleanSubDir);
            $storedPath = $cleanSubDir . '/' . $filename;
        } else {
            $fullDestPath = storage_path('app/dokumen_rahasia');
            $storedPath = $filename;
        }

        // Create directory if not exists
        if (!file_exists($fullDestPath)) {
            mkdir($fullDestPath, 0755, true);
        }

        // Move file
        $file->move($fullDestPath, $filename);

        return $storedPath;
    }

    /**
     * Generate date-based subdirectory string
     * e.g. "mutasi_keluar/2024/01"
     */
    public function getDateBasedPath(string $type = 'keluar'): string
    {
        $year = date('Y');
        $month = date('m');
        $folderType = $type === 'masuk' ? 'mutasi_masuk' : 'mutasi_keluar';

        return "{$folderType}/{$year}/{$month}";
    }

    /**
     * Upload multiple PDF files for Mutasi Keluar
     */
    public function uploadMutasiKeluarFiles($request, string $idMutasi): array
    {
        $filePaths = [];
        $fields = ['file_suket_keluar', 'file_suket_dapo', 'file_suket_diterima', 'file_rapor'];
        $subDir = $this->getDateBasedPath('keluar');

        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                $filePaths[$field] = $this->uploadPdf(
                    $request->file($field),
                    $idMutasi,
                    $field,
                    $subDir
                );
            }
        }

        return $filePaths;
    }

    /**
     * Upload multiple PDF files for Mutasi Keluar revision
     * Revisions should go to the same folder as original ideally, 
     * but strictly speaking simpler to put in current date folder or same logic.
     * We will use current date logic for simplicity and organization.
     */
    public function uploadMutasiKeluarRevisionFiles($request, string $idMutasi): array
    {
        $filePaths = [];
        $fields = ['file_suket_keluar', 'file_suket_dapo', 'file_suket_diterima', 'file_rapor'];
        $subDir = $this->getDateBasedPath('keluar');

        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                $filePaths[$field] = $this->uploadPdf(
                    $request->file($field),
                    $idMutasi,
                    $field . '_rev',
                    $subDir
                );
            }
        }

        return $filePaths;
    }

    /**
     * Upload surat permohonan for Mutasi Masuk
     */
    public function uploadSuratPermohonan($request, string $idMutasi): ?string
    {
        if (!$request->hasFile('file_surat_permohonan')) {
            return null;
        }

        $subDir = $this->getDateBasedPath('masuk');

        return $this->uploadPdf(
            $request->file('file_surat_permohonan'),
            $idMutasi,
            'surat_permohonan',
            $subDir
        );
    }

    /**
     * Upload files for a single student in Mutasi Masuk
     */
    public function uploadMutasiMasukSiswaFiles($request, string $idMutasi, int $studentIndex): array
    {
        $filePaths = [];
        $fields = [
            'file_surat_pindah',
            'file_surat_dapodik',
            'file_rekomendasi',
            'file_ijazah_sd',
            'file_rapor',
            'file_surat_penyaluran'
        ];

        $subDir = $this->getDateBasedPath('masuk');

        foreach ($fields as $field) {
            $fileKey = "siswa.{$studentIndex}.{$field}";
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);

                // Validate MIME type
                $mimeType = $file->getMimeType();
                if ($mimeType !== 'application/pdf') {
                    throw new \Exception("File {$field} siswa #" . ($studentIndex + 1) . " harus berformat PDF.");
                }

                $filePaths[$field] = $this->uploadPdf(
                    $file,
                    $idMutasi,
                    "siswa{$studentIndex}_{$field}",
                    $subDir
                );
            }
        }

        return $filePaths;
    }

    /**
     * Upload archive file for completed mutation
     */
    /**
     * Upload archive file for completed mutation
     */
    public function uploadArsip(UploadedFile $file, string $idMutasi, string $type = 'keluar'): string
    {
        $prefix = $type === 'masuk' ? 'ARSIP_MASUK' : 'ARSIP';
        $safeId = str_replace('/', '-', $idMutasi);

        // Change: Store in specific arsip folder based on type
        $year = date('Y');
        $month = date('m');
        $folderName = $type === 'masuk' ? 'arsip_mutasi_masuk' : 'arsip_mutasi_keluar';
        $subDir = "{$folderName}/{$year}/{$month}";

        return $this->uploadPdf($file, $prefix, $safeId, $subDir);
    }
}
