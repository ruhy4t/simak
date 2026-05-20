<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SecureFileController extends Controller
{
    /**
     * Get the storage disk to use based on environment
     */
    protected function getDisk(): string
    {
        // Use B2 if configured, otherwise fall back to local
        if (config('filesystems.disks.b2.key') && app()->environment('production')) {
            return 'b2';
        }
        return 'private_docs';
    }

    /**
     * Serve a file from storage (B2 in production, local in development)
     * Route: GET /dokumen/lihat/{path}
     */
    public function show(Request $request, $path)
    {
        if (!Auth::check()) {
            abort(403, 'Akses ditolak.');
        }

        if (strpos($path, '..') !== false) {
            abort(400, 'Invalid path.');
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        $disk = $this->getDisk();

        // Try multiple path variations for backward compatibility
        $pathsToTry = [
            $path,
            ltrim($path, '/'),
        ];

        // Handle legacy 'dokumen/' prefix
        if (str_starts_with($path, 'dokumen/')) {
            $pathsToTry[] = substr($path, 8); // Remove 'dokumen/' prefix
        }

        $foundPath = null;

        foreach ($pathsToTry as $tryPath) {
            if (Storage::disk($disk)->exists($tryPath)) {
                $foundPath = $tryPath;
                break;
            }
        }

        // If still not found and we're in production, fall back to local for debugging
        if (!$foundPath && $disk === 'b2') {
            // Try local storage as fallback (for files not yet migrated)
            $localPath = storage_path('app/dokumen_rahasia/' . ltrim($path, '/'));
            if (file_exists($localPath)) {
                Log::warning("File found in local storage but not in B2: {$path}");
                return $this->serveLocalFile($localPath, $path);
            }
        }

        if (!$foundPath) {
            Log::warning("File not found in any storage: {$path}");
            abort(404, 'File tidak ditemukan.');
        }

        $this->authorizeFileAccess($foundPath);

        // Get file content and serve
        $content = Storage::disk($disk)->get($foundPath);
        $extension = strtolower(pathinfo($foundPath, PATHINFO_EXTENSION));

        $mimeType = match ($extension) {
            'pdf' => 'application/pdf',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($foundPath) . '"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Serve a local file (fallback for development or unmigrated files)
     */
    protected function serveLocalFile(string $fullPath, string $originalPath)
    {
        $this->authorizeFileAccess(ltrim(str_replace('\\', '/', $originalPath), '/'));

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        $mimeType = match ($extension) {
            'pdf' => 'application/pdf',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($originalPath) . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    protected function authorizeFileAccess(string $path): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Akses ditolak.');
        }

        if (in_array($user->role, ['Dinas', 'SuperAdmin'], true)) {
            return;
        }

        $paths = array_values(array_unique([
            $path,
            ltrim($path, '/'),
            str_starts_with($path, 'dokumen/') ? substr($path, 8) : 'dokumen/' . ltrim($path, '/'),
        ]));

        $mutasiKeluar = $this->findByFileColumns('mutasi_keluar', [
            'file_suket_keluar',
            'file_suket_dapo',
            'file_suket_diterima',
            'file_rapor',
            'file_arsip_scan',
        ], $paths);

        if ($mutasiKeluar) {
            $allowed = $user->role === 'Operator'
                ? $mutasiKeluar->pengusul === $user->username
                : $mutasiKeluar->npsn_sekolah_asal === $user->npsn || $mutasiKeluar->pengusul === $user->username;

            abort_unless($allowed, 403, 'Anda tidak berhak mengakses dokumen ini.');
            return;
        }

        $mutasiMasuk = $this->findByFileColumns('mutasi_masuk', [
            'file_surat_permohonan',
            'file_arsip_scan',
        ], $paths);

        if ($mutasiMasuk) {
            $allowed = $user->role === 'Operator'
                ? $mutasiMasuk->pengusul === $user->username
                : $mutasiMasuk->npsn_sekolah_tujuan === $user->npsn || $mutasiMasuk->pengusul === $user->username;

            abort_unless($allowed, 403, 'Anda tidak berhak mengakses dokumen ini.');
            return;
        }

        $siswa = $this->findByFileColumns('mutasi_masuk_siswa', [
            'file_surat_pindah',
            'file_surat_dapodik',
            'file_rekomendasi',
            'file_ijazah_sd',
            'file_rapor',
            'file_surat_penyaluran',
        ], $paths);

        if ($siswa) {
            $parent = DB::table('mutasi_masuk')->where('id_mutasi', $siswa->id_mutasi)->first();
            $allowed = $parent && ($user->role === 'Operator'
                ? $parent->pengusul === $user->username
                : $parent->npsn_sekolah_tujuan === $user->npsn || $parent->pengusul === $user->username);

            abort_unless($allowed, 403, 'Anda tidak berhak mengakses dokumen ini.');
            return;
        }

        Log::warning("File access denied because no owner record was found: {$path}");
        abort(403, 'Anda tidak berhak mengakses dokumen ini.');
    }

    protected function findByFileColumns(string $table, array $columns, array $paths): ?object
    {
        return DB::table($table)
            ->where(function ($query) use ($columns, $paths) {
                foreach ($columns as $column) {
                    $query->orWhereIn($column, $paths);
                }
            })
            ->first();
    }
}
