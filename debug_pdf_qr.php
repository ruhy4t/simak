<?php

use Illuminate\Contracts\Console\Kernel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\QrCodeHelper;

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

echo "Laravel Bootstrapped.\n";

try {
    echo "Testing QrCodeHelper...\n";
    // Mock public_path if needed or reliance on helper
    // In console public_path() usually works and points to public folder

    // Create a dummy image for logo if not exists to test logic
    $logoPath = public_path('images/logo_disdik_kota_bogor.png');
    if (!file_exists($logoPath)) {
        echo "Warning: Logo not found at $logoPath\n";
    }

    $qr = QrCodeHelper::generateWithLogo('TEST_DATA', 80, $logoPath);
    echo "QR Generated successfully. Length: " . strlen($qr) . "\n";
} catch (\Throwable $e) {
    echo "QR Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

try {
    echo "Testing DomPDF...\n";
    $pdf = Pdf::loadHTML('<h1>Test</h1>');
    $output = $pdf->output();
    echo "PDF Generated successfully. Size: " . strlen($output) . "\n";
} catch (\Throwable $e) {
    echo "PDF Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
