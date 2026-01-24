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
            $filename = 'temp_' . now()->format('Ymd_His') . '_' . Str::random(20) . '.' . $ext;
            $path = 'temp_uploads/' . $filename;

            try {
                $disk = $this->getTempDisk();
                Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));
                Log::info("Temp file uploaded to {$disk}: {$path}");

                return response()->json([
                    'success' => true,
                    'path' => $path,
                    'disk' => $disk,
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
     * Generate Presigned URL for Direct Upload (B2/S3)
     */
    public function generateUploadUrl(Request $request)
    {
        // 1. Check if we should use Direct Upload (Only in Production/B2)
        if (!$this->shouldUseDirectUpload()) {
            return response()->json([
                'use_check_upload' => false, // Signal frontend to use legacy POST
                'message' => 'Direct upload not available (using local storage)',
                'is_local' => true
            ]);
        }

        // 2. Validate Request
        $request->validate([
            'filename' => 'required|string',
            'content_type' => 'required|string'
        ]);

        $filename = $request->input('filename');
        $contentType = $request->input('content_type');
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        // Secure Filename
        $safeFilename = 'temp_' . now()->format('Ymd_His') . '_' . Str::random(20) . '.' . $ext;
        $path = 'temp_uploads/' . $safeFilename;

        try {
            // 3. Generate Presigned URL
            $disk = 'b2';
            // Access the underlying S3 Client
            $client = Storage::disk($disk)->getClient();
            $bucket = config('filesystems.disks.b2.bucket');

            $cmd = $client->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key' => $path,
                'ContentType' => $contentType,
                // 'ACL' => 'public-read' // Optional: depends on bucket settings
            ]);

            $expiry = '+30 minutes';
            $presignedRequest = $client->createPresignedRequest($cmd, $expiry);
            $presignedUrl = (string) $presignedRequest->getUri();

            return response()->json([
                'success' => true,
                'upload_url' => $presignedUrl,
                'relative_path' => $path,
                'method' => 'PUT',
                'headers' => [
                    'Content-Type' => $contentType
                ],
                'expires_in' => 1800 // seconds
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to generate presigned URL: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat URL upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if direct upload should be used
     */
    private function shouldUseDirectUpload(): bool
    {
        // Only use direct upload if B2 is configured AND we are in production
        // OR if forced for testing (you can remove 'production' check if testing locally with B2)
        return config('filesystems.disks.b2.key') && app()->environment('production');
        // Uncomment below to test locally if B2 credentials are set
        // return !empty(config('filesystems.disks.b2.key'));
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
