<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TemporaryUploadController extends Controller
{
    /**
     * Handle temporary file upload.
     * Stores file in 'temp_uploads' directory.
     * Uses B2 in production (Vercel) since local storage is ephemeral.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048', // Max 2MB (covers Rapor)
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $ext = strtolower($file->getClientOriginalExtension());

            // Generate unique filename with timestamp for easy cleanup
            $filename = 'temp_' . now()->format('Ymd_His') . '_' . Str::random(20) . '.' . $ext;
            $path = 'temp_uploads/' . $filename;

            try {
                // Determine disk based on environment
                // Vercel (production) doesn't allow persistent local storage
                $disk = $this->getTempDisk();

                // Store file
                Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

                Log::info("Temp file uploaded to {$disk}: {$path}");

                return response()->json([
                    'success' => true,
                    'path' => $path, // e.g., "temp_uploads/temp_20260123_xyz.pdf"
                    'disk' => $disk, // For debugging
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize()
                ]);
            } catch (\Exception $e) {
                Log::error("Temp upload failed: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Upload gagal: ' . $e->getMessage()
                ], 500);
            }
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
    }

    /**
     * Get the appropriate disk for temporary uploads.
     * B2 in production, local in development.
     */
    private function getTempDisk(): string
    {
        // Use B2 if configured AND in production
        if (config('filesystems.disks.b2.key') && app()->environment('production')) {
            return 'b2';
        }
        return 'local';
    }
}
