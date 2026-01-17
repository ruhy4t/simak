<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class SecureFileController extends Controller
{
    /**
     * Serve a file from private storage
     * Route: GET /dokumen/lihat/{path}
     */
    public function show(Request $request, $path)
    {
        if (!Auth::check())
            abort(403, 'Akses ditolak.');
        if (strpos($path, '..') !== false)
            abort(400, 'Invalid path.');

        // 1. Try Laravel Storage Path
        $fullPath = storage_path('app/dokumen_rahasia/' . $path);

        // 2. Fallback to base_path calculation (for shared hosting quirks)
        // Previous logs showed app is in: /home/vol10_1/byethost31.com/b31_40607902/htdocs
        if (!file_exists($fullPath)) {
            $base = base_path(); // This retrieves the absolute path to the project root
            $altPath = $base . '/storage/app/dokumen_rahasia/' . $path;

            if (file_exists($altPath)) {
                $fullPath = $altPath;
            } elseif (str_starts_with($path, 'dokumen/')) {
                // Legacy double-folder check
                $legacyPath = $base . '/storage/app/dokumen_rahasia/' . substr($path, 8);
                if (file_exists($legacyPath))
                    $fullPath = $legacyPath;
            }
        }

        if (!file_exists($fullPath))
            abort(404, 'File tidak ditemukan.');

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'pdf' => 'application/pdf',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => File::mimeType($fullPath) ?? 'application/octet-stream',
        };

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    // migrateFiles and organizeFiles methods removed as cleanup is complete.
}