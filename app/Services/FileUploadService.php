<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * FileUploadService - Handles file upload operations for mutations
 * 
 * Now supports both local storage and Backblaze B2 cloud storage.
 * Uses B2 in production (Vercel), local in development.
 */
class FileUploadService
{
    public function __construct(private readonly TemporaryUploadService $temporaryUploads)
    {
    }

    /**
     * Get the public URL for a file
     * Compatible with local and B2 storage
     */
    public static function getStorageUrl($path)
    {
        if (empty($path))
            return '';

        // If absolute URL already (e.g. from B2 or external), return as is
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        // Check if using B2 (Production)
        if (config('filesystems.disks.b2.key') && app()->environment('production')) {
            return Storage::disk('b2')->url($path);
        }

        // Local Fallback (Public Disk)
        // Ensure path doesn't already have 'storage/' prefix to avoid double
        $cleanPath = ltrim($path, '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            return asset($cleanPath);
        }

        return asset('storage/' . $cleanPath);
    }

    /**
     * Get the storage disk to use based on environment
     */
    public function getDisk(): string
    {
        // Use B2 if configured, otherwise fall back to local
        if (config('filesystems.disks.b2.key') && app()->environment('production')) {
            return 'b2';
        }
        return 'private_docs';
    }

    /**
     * Check if we're using cloud storage
     */
    public function isCloudStorage(): bool
    {
        return $this->getDisk() === 'b2';
    }

    /**
     * Get the disk for public image assets such as a school's letterhead.
     */
    public function getPublicImageDisk(): string
    {
        return $this->isCloudStorage() ? 'b2' : 'public';
    }

    /**
     * Get the disk for temporary uploads.
     * B2 in production (Vercel doesn't persist local), local in development.
     */
    public function getTempDisk(): string
    {
        return $this->temporaryUploads->disk();
    }

    /**
     * Sanitize filename - only allow safe characters
     */
    public function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
    }

    /**
     * Validate ownership and metadata before promoting a temporary upload.
     *
     * @param list<string>|null $allowedMimes
     */
    public function promoteTemporaryUpload(
        string $temporaryPath,
        string $permanentPath,
        ?array $allowedMimes = null,
        int|string|null $ownerId = null,
    ): string {
        $metadata = $this->temporaryUploads->validateOwned($temporaryPath, $ownerId, $allowedMimes);
        $targetDisk = $this->getDisk();
        $content = Storage::disk($metadata['disk'])->get($metadata['path']);

        Storage::disk($targetDisk)->put($permanentPath, $content);
        Storage::disk($metadata['disk'])->delete($metadata['path']);

        Log::info('Temporary upload promoted.', [
            'temporary_path' => $metadata['path'],
            'permanent_path' => $permanentPath,
            'owner_id' => $metadata['owner_id'],
            'mime_type' => $metadata['mime_type'],
            'size' => $metadata['size'],
        ]);

        return $permanentPath;
    }

    /**
     * Delete old file from storage to save space
     */
    public function deleteOldFile(?string $oldPath, ?string $disk = null): bool
    {
        if (empty($oldPath)) {
            return true;
        }

        try {
            $disk ??= $this->getDisk();

            if (Storage::disk($disk)->exists($oldPath)) {
                Storage::disk($disk)->delete($oldPath);
                Log::info("Deleted old file: {$oldPath} from {$disk}");
            }

            return true;
        } catch (\Exception $e) {
            Log::warning("Failed to delete old file: {$oldPath}. Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload a single PDF file with security validation
     * 
     * @param UploadedFile $file The uploaded file
     * @param string $prefix Prefix for the filename
     * @param string $suffix Suffix for the filename
     * @param string|null $subDir Optional subdirectory
     * @return string Relative path to the uploaded file
     */
    public function uploadPdf($file, string $prefix, string $suffix, ?string $subDir = null): string
    {
        // Handle Temporary File Path (String)
        if (is_string($file)) {
            $metadata = $this->temporaryUploads->validateOwned($file, allowedMimes: ['application/pdf']);
            $file = $metadata['path'];
            $tempDisk = $metadata['disk'];

            // Build safe filename
            $safePrefix = $this->sanitizeFilename($prefix);
            $safeSuffix = $this->sanitizeFilename($suffix);
            $filename = "{$safePrefix}_{$safeSuffix}.pdf";

            // Determine final path
            if ($subDir) {
                $cleanSubDir = trim($subDir, '/');
                $storedPath = $cleanSubDir . '/' . $filename;
            } else {
                $storedPath = $filename;
            }

            $disk = $this->getDisk();

            // Move/Copy from temp disk to target disk
            $content = Storage::disk($tempDisk)->get($file);
            Storage::disk($disk)->put($storedPath, $content);

            // Delete temp file
            Storage::disk($tempDisk)->delete($file);

            Log::info("Moved temp file {$file} from {$tempDisk} to {$disk}: {$storedPath}");
            return $storedPath;
        }

        // Standard UploadedFile handling
        if (!($file instanceof UploadedFile)) {
            throw new \Exception('Invalid file input.');
        }

        // Validate extension
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'pdf') {
            throw new \Exception('Hanya file PDF yang diperbolehkan.');
        }

        // Validate MIME type
        $mimeType = $file->getMimeType();
        if ($mimeType !== 'application/pdf') {
            throw new \Exception('File harus berformat PDF.');
        }

        // Build safe filename
        $safePrefix = $this->sanitizeFilename($prefix);
        $safeSuffix = $this->sanitizeFilename($suffix);
        $filename = "{$safePrefix}_{$safeSuffix}.pdf";

        // Determine storage path
        if ($subDir) {
            $cleanSubDir = trim($subDir, '/');
            $storedPath = $cleanSubDir . '/' . $filename;
        } else {
            $storedPath = $filename;
        }

        $disk = $this->getDisk();

        // Upload to storage
        Storage::disk($disk)->put($storedPath, file_get_contents($file->getRealPath()));

        Log::info("File uploaded to {$disk}: {$storedPath}");

        return $storedPath;
    }

    /**
     * Upload an Image file (JPG, PNG, WebP)
     */
    public function uploadImage(
        $file,
        string $prefix,
        string $suffix,
        ?string $subDir = null,
        ?string $targetDisk = null,
    ): string
    {
        // Handle Temporary File Path (String)
        if (is_string($file)) {
            $metadata = $this->temporaryUploads->validateOwned(
                $file,
                allowedMimes: ['image/jpeg', 'image/png', 'image/webp'],
            );
            $file = $metadata['path'];
            $tempDisk = $metadata['disk'];

            // Get extension from path
            $ext = $metadata['extension'];

            // Build safe filename
            $safePrefix = $this->sanitizeFilename($prefix);
            $safeSuffix = $this->sanitizeFilename($suffix);
            $filename = "{$safePrefix}_{$safeSuffix}.{$ext}";

            // Determine final path
            if ($subDir) {
                $cleanSubDir = trim($subDir, '/');
                $storedPath = $cleanSubDir . '/' . $filename;
            } else {
                $storedPath = $filename;
            }

            $disk = $targetDisk ?? $this->getDisk();

            // Move/Copy from temp disk to target disk
            $content = Storage::disk($tempDisk)->get($file);
            Storage::disk($disk)->put($storedPath, $content);

            // Delete temp file
            Storage::disk($tempDisk)->delete($file);

            Log::info("Moved temp image {$file} from {$tempDisk} to {$disk}: {$storedPath}");
            return $storedPath;
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            throw new \Exception('Format gambar tidak didukung (Gunakan JPG, PNG, atau WebP).');
        }

        // Build safe filename
        $safePrefix = $this->sanitizeFilename($prefix);
        $safeSuffix = $this->sanitizeFilename($suffix);
        $filename = "{$safePrefix}_{$safeSuffix}.{$ext}";

        // Determine storage path
        if ($subDir) {
            $cleanSubDir = trim($subDir, '/');
            $storedPath = $cleanSubDir . '/' . $filename;
        } else {
            $storedPath = $filename;
        }

        $disk = $targetDisk ?? $this->getDisk();

        // Upload
        Storage::disk($disk)->put($storedPath, file_get_contents($file->getRealPath()));
        Log::info("Image uploaded to {$disk}: {$storedPath}");

        return $storedPath;
    }

    /**
     * Generate date-based subdirectory string
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
        $file = $request->file('file_surat_permohonan');
        if (!$file) {
            $file = $request->input('file_surat_permohonan');
        }

        if (!$file) {
            return null;
        }

        // Check for persistent path (Resume Draft)
        if (is_string($file) && !str_starts_with($file, 'temp_uploads/')) {
            return $file;
        }

        $subDir = $this->getDateBasedPath('masuk');

        return $this->uploadPdf(
            $file,
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

            $file = $request->file($fileKey);
            if (!$file) {
                // Check flat input name (dot notation might be converted to array in PHP)
                // In Laravel request->input('siswa.0.file_...') works.
                $file = $request->input($fileKey);
            }

            if ($file) {
                // Check for persistent path (Resume Draft)
                if (is_string($file) && !str_starts_with($file, 'temp_uploads/')) {
                    $filePaths[$field] = $file;
                    continue;
                }

                // Only validate MIME if it's an UploadedFile object
                if ($file instanceof UploadedFile) {
                    $mimeType = $file->getMimeType();
                    if ($mimeType !== 'application/pdf') {
                        throw new \Exception("File {$field} siswa #" . ($studentIndex + 1) . " harus berformat PDF.");
                    }
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
    public function uploadArsip(UploadedFile $file, string $idMutasi, string $type = 'keluar'): string
    {
        $prefix = $type === 'masuk' ? 'ARSIP_MASUK' : 'ARSIP';
        $safeId = str_replace('/', '-', $idMutasi);

        $year = date('Y');
        $month = date('m');
        $folderName = $type === 'masuk' ? 'arsip_mutasi_masuk' : 'arsip_mutasi_keluar';
        $subDir = "{$folderName}/{$year}/{$month}";

        return $this->uploadPdf($file, $prefix, $safeId, $subDir);
    }

    // Legacy methods kept for backward compatibility
    public function getUploadPath(): string
    {
        return storage_path('app/dokumen_rahasia');
    }

    public function getFullDateBasedPath(string $type = 'keluar'): string
    {
        $subDir = $this->getDateBasedPath($type);
        $fullPath = storage_path('app/dokumen_rahasia/' . $subDir);
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        return $fullPath;
    }

    public function getRelativeDateBasedPath(string $type = 'keluar'): string
    {
        return 'dokumen/' . $this->getDateBasedPath($type);
    }
}
