<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk konversi gambar ke WebP
 */
class ImageConversionService
{
    /**
     * Convert image to WebP format with target size < 100KB
     * 
     * @param UploadedFile $file Original file
     * @param string $destinationPath Full path where to save
     * @return bool Success status
     */
    public function convertToWebP(UploadedFile $file, string $destinationPath): bool
    {
        try {
            $extension = strtolower($file->getClientOriginalExtension());

            // Create image resource based on type
            $image = match ($extension) {
                'jpg', 'jpeg' => imagecreatefromjpeg($file->getRealPath()),
                'png' => imagecreatefrompng($file->getRealPath()),
                default => null,
            };

            if (!$image) {
                Log::error("ImageConversion: Unsupported format - {$extension}");
                return false;
            }

            // Preserve transparency for PNG
            if ($extension === 'png') {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }

            // Start with quality 85 and reduce until < 100KB
            $quality = 85;
            $maxSize = 100 * 1024; // 100KB

            do {
                // Save to temp file first to check size
                $tempPath = sys_get_temp_dir() . '/temp_' . uniqid() . '.webp';
                imagewebp($image, $tempPath, $quality);
                $fileSize = filesize($tempPath);

                if ($fileSize <= $maxSize) {
                    // Move to destination
                    rename($tempPath, $destinationPath);
                    imagedestroy($image);
                    Log::info("ImageConversion: Success - {$destinationPath} ({$fileSize} bytes, quality: {$quality})");
                    return true;
                }

                // Delete temp and try lower quality
                unlink($tempPath);
                $quality -= 10;

            } while ($quality >= 10);

            // If still too large, resize the image
            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);
            $scale = 0.8;

            while ($scale >= 0.3) {
                $newWidth = (int) ($originalWidth * $scale);
                $newHeight = (int) ($originalHeight * $scale);

                $resized = imagecreatetruecolor($newWidth, $newHeight);

                // Preserve transparency
                imagealphablending($resized, false);
                imagesavealpha($resized, true);

                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

                $tempPath = sys_get_temp_dir() . '/temp_' . uniqid() . '.webp';
                imagewebp($resized, $tempPath, 80);
                $fileSize = filesize($tempPath);

                imagedestroy($resized);

                if ($fileSize <= $maxSize) {
                    rename($tempPath, $destinationPath);
                    imagedestroy($image);
                    Log::info("ImageConversion: Success with resize - {$destinationPath} ({$fileSize} bytes, scale: {$scale})");
                    return true;
                }

                unlink($tempPath);
                $scale -= 0.1;
            }

            imagedestroy($image);
            Log::error("ImageConversion: Could not reduce size below 100KB");
            return false;

        } catch (\Exception $e) {
            Log::error("ImageConversion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if file is an image that needs conversion
     */
    public function needsConversion(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        return in_array($extension, ['jpg', 'jpeg', 'png']);
    }

    /**
     * Check if file is PDF
     */
    public function isPdf(UploadedFile $file): bool
    {
        return strtolower($file->getClientOriginalExtension()) === 'pdf';
    }
}