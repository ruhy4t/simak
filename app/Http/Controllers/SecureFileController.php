<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
}