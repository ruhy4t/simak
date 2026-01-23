<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class PdfImageHelper
{
    /**
     * Convert an image file to a base64 string for embedding in PDF.
     *
     * @param string $path The path to the image file.
     * @param bool $isStorage Whether the path is in the storage directory (default false, meaning public).
     * @return string The base64 encoded image string suitable for src attribute.
     */
    public static function encode($path, $isStorage = false)
    {
        try {
            if ($isStorage) {
                // If it's a storage path, we need the full absolute path
                // Usually $path passed here is relative to 'app/public', e.g., 'kop_surat/xyz.jpg'
                // storage_path('app/public/' . $path)
                $fullPath = storage_path('app/public/' . $path);
            } else {
                // Public path
                $fullPath = public_path($path);
            }

            if (!file_exists($fullPath)) {
                // Return a placeholder or empty string if file not found to prevent error
                return '';
            }

            $type = pathinfo($fullPath, PATHINFO_EXTENSION);
            $data = file_get_contents($fullPath);

            // Normalize type for base64
            if ($type == 'jpg')
                $type = 'jpeg';

            // CRITICAL: Dompdf on Vercel (without GD) crashes on PNGs even if Base64 encoded.
            // We must ONLY return JPEG images.
            if (strtolower($type) !== 'jpeg') {
                // Return empty to safe-fail instead of 500 Error
                return '';
            }

            // Return safe JPEG Base64
            return 'data:image/' . $type . ';base64,' . base64_encode($data);
        } catch (\Exception $e) {
            // Log error or just return empty to allow PDF to generate without image
            return '';
        }
    }
}
