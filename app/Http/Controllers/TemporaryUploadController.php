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
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:2048', // Max 2MB
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
            'content_type' => 'required|string|in:application/pdf,image/jpeg,image/png,image/webp'
        ]);

        $filename = $request->input('filename');
        $contentType = $request->input('content_type');
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowedExtensions, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Format file tidak didukung.'
            ], 422);
        }

        // Check if B2 Bucket and Endpoint are configured
        if (!config('filesystems.disks.b2.bucket') || !config('filesystems.disks.b2.endpoint')) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi B2 (Bucket/Endpoint) belum lengkap di Vercel.'
            ], 500);
        }

        // Secure Filename
        $safeFilename = 'temp_' . now()->format('Ymd_His') . '_' . Str::random(20) . '.' . $ext;
        $path = 'temp_uploads/' . $safeFilename;

        try {
            // 3. Generate Presigned URL using AWS S3 Client directly
            // Note: Flysystem v3 doesn't expose getClient(), so we create the client directly
            $bucket = config('filesystems.disks.b2.bucket');
            $endpoint = config('filesystems.disks.b2.endpoint');
            $region = config('filesystems.disks.b2.region', 'us-west-004');
            $key = config('filesystems.disks.b2.key');
            $secret = config('filesystems.disks.b2.secret');

            $client = new \Aws\S3\S3Client([
                'version' => 'latest',
                'region' => $region,
                'endpoint' => $endpoint,
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret,
                ],
            ]);

            $cmd = $client->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key' => $path,
                'ContentType' => $contentType,
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
        // Use direct upload if B2 is configured (Keys exist)
        // We removed the strictly 'production' check to allow testing B2 in other environments if needed
        // provided the keys are set in .env
        return !empty(config('filesystems.disks.b2.key')) && !empty(config('filesystems.disks.b2.bucket'));
    }


    /**
     * Get the appropriate disk for temporary uploads.
     * B2 in production, local in development.
     */
    private function getTempDisk(): string
    {
        // Use B2 if configured
        if ($this->shouldUseDirectUpload()) {
            return 'b2';
        }
        return 'local';
    }
}
