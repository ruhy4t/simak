<?php

namespace App\Helpers;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR Code Helper - Now using chillerlan/php-qrcode for logo support
 */
class QrCodeHelper
{
    /**
     * Generate QR Code as SVG with optional logo in center
     */
    /**
     * Generate QR Code as PNG Data URI with HTML overlay for Logo
     * This is FASTER and MORE COMPATIBLE than SVG injection.
     */
    public static function generateWithLogo(string $data, int $size = 150, ?string $logoPath = null): string
    {
        try {
            // 1. Generate Base QR Code (Clean, High ECC)
            $options = new QROptions([
                'version' => QRCode::VERSION_AUTO,
                'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                'eccLevel' => QRCode::ECC_H, // High ECC allows covering 30% of QR
                'scale' => 10, // Increased scale for sharper image
                'imageBase64' => true,
            ]);

            $qrcode = new QRCode($options);
            $qrBase64 = $qrcode->render($data); // Returns "data:image/png;base64,..."

            // 2. Prepare Logo (if exists)
            // 2. Prepare Logo (if exists)
            $logoHtml = '';

            // Resolve Logo Source
            $logoSrc = null;

            if ($logoPath) {
                // Scenario 0: Check if it's the Disdik Logo and we have embedded config (NUCLEAR FALLBACK)
                // This guarantees the logo works even if file system/URL fails.
                $isDisdikLogo = strpos($logoPath, 'disdik') !== false;
                $embeddedLogo = config('logo_b64');

                if ($isDisdikLogo && $embeddedLogo) {
                    $logoSrc = "data:image/jpeg;base64," . $embeddedLogo;
                } elseif (file_exists($logoPath)) {
                    // Scenario A: Local File Exists
                    $logoBinary = @file_get_contents($logoPath);
                    if ($logoBinary !== false) {
                        $logoData = base64_encode($logoBinary);
                        $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
                        $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
                        $logoSrc = "data:$mime;base64,$logoData";
                    }
                } else {
                    // Scenario B: URL or Missing File -> Asset URL
                    $filename = basename($logoPath);
                    $logoSrc = asset('images/' . $filename);
                }
            }

            if ($logoSrc) {
                // Box size calculation (approx 22% of QR size)
                $logoSize = intval($size * 0.22);
                $centerPos = intval(($size - $logoSize) / 2);

                $logoHtml = '
                <div style="
                    position: absolute;
                    top: ' . $centerPos . 'px;
                    left: ' . $centerPos . 'px;
                    width: ' . $logoSize . 'px;
                    height: ' . $logoSize . 'px;
                    background: #fff;
                    padding: 2px;
                    border-radius: 4px;
                    z-index: 20;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">
                    <img src="' . $logoSrc . '" style="width:100%; height:100%; object-fit:contain;">
                </div>';
            }

            // 3. Return HTML Wrapper
            return '
            <div style="position: relative; width: ' . $size . 'px; height: ' . $size . 'px; display: inline-block;">
                <img src="' . $qrBase64 . '" alt="QR Code" style="width: 100%; height: 100%;">
                ' . $logoHtml . '
            </div>';

        } catch (\Throwable $e) {
            // FAST Fallback (avoid 500 fatal errors)
            // If local generation fails, just return a simple text error or fallback API
            // Using API as last resort
            $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);
            return '<img src="' . $qrUrl . '" style="width:' . $size . 'px; height:' . $size . 'px;" alt="QR Code">';
        }
    }

    public static function generateSuratData(string $idSurat, string $tanggalUsulan, string $namaSiswa): string
    {
        $shortName = mb_substr($namaSiswa, 0, 20);
        return "SIMAK|{$idSurat}|{$tanggalUsulan}|{$shortName}";
    }
}