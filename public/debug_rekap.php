<<<<<<< HEAD
<?php
// Script Debugging Error 500 Laporan
// Upload ke public_html
// Akses: domain.com/debug_rekap.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🐞 Debug Laporan Error</h1>";

// 1. Boot Laravel
$paths = [
    __DIR__ . '/../simak_core/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    $_SERVER['DOCUMENT_ROOT'] . '/../simak_core/vendor/autoload.php'
];

$autoloadFound = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        require $path;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound) {
    die("❌ Gagal load vendor autoload. Pastikan folder vendor ada.");
}

try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    echo "✅ Laravel Booted Successfully.<br>";
} catch (\Throwable $e) {
    die("❌ Laravel Boot Error: " . $e->getMessage());
}

// 2. Test Query Manual
echo "<h3>Test Query Database...</h3>";

use App\Models\MutasiMasuk;
use App\Models\MutasiKeluar;
use App\Models\SatuanPendidikan;
use Illuminate\Support\Facades\DB;

try {
    // Cek Koneksi DB
    DB::connection()->getPdo();
    echo "✅ Database Connection OK.<br>";

    // Cek Model
    $countSatpen = SatuanPendidikan::count();
    echo "✅ SatuanPendidikan Count: $countSatpen<br>";

    // Cek Mutasi Query (Logic from Controller)
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');

    echo "Testing Query Range: $start_date s/d $end_date<br>";

    $masuk = MutasiMasuk::whereBetween('tanggal_pengajuan', [$start_date, $end_date])->count();
    echo "✅ Query Mutasi Masuk: OK (Count: $masuk)<br>";

    $keluar = MutasiKeluar::whereBetween('tanggal_pengajuan', [$start_date, $end_date])->count();
    echo "✅ Query Mutasi Keluar: OK (Count: $keluar)<br>";

    // Jika sampai sini aman, berarti masalahnya mungkin di View atau PDF
    echo "<hr><h3 style='color:green'>Database & Model Aman!</h3>";
    echo "Jika halaman rekap masih error 500, kemungkinan masalahnya ada di:<br>";
    echo "1. File View (<code>resources/views/laporan/rekap/index.blade.php</code>) error syntax.<br>";
    echo "2. Helper function yang hilang di View.<br>";

} catch (\Throwable $e) {
    echo "<hr><h3 style='color:red'>❌ ERROR DETECTED:</h3>";
    echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
=======
<?php
// Script Debugging Error 500 Laporan
// Upload ke public_html
// Akses: domain.com/debug_rekap.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🐞 Debug Laporan Error</h1>";

// 1. Boot Laravel
$paths = [
    __DIR__ . '/../simak_core/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    $_SERVER['DOCUMENT_ROOT'] . '/../simak_core/vendor/autoload.php'
];

$autoloadFound = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        require $path;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound) {
    die("❌ Gagal load vendor autoload. Pastikan folder vendor ada.");
}

try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    echo "✅ Laravel Booted Successfully.<br>";
} catch (\Throwable $e) {
    die("❌ Laravel Boot Error: " . $e->getMessage());
}

// 2. Test Query Manual
echo "<h3>Test Query Database...</h3>";

use App\Models\MutasiMasuk;
use App\Models\MutasiKeluar;
use App\Models\SatuanPendidikan;
use Illuminate\Support\Facades\DB;

try {
    // Cek Koneksi DB
    DB::connection()->getPdo();
    echo "✅ Database Connection OK.<br>";

    // Cek Model
    $countSatpen = SatuanPendidikan::count();
    echo "✅ SatuanPendidikan Count: $countSatpen<br>";

    // Cek Mutasi Query (Logic from Controller)
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');

    echo "Testing Query Range: $start_date s/d $end_date<br>";

    $masuk = MutasiMasuk::whereBetween('tanggal_pengajuan', [$start_date, $end_date])->count();
    echo "✅ Query Mutasi Masuk: OK (Count: $masuk)<br>";

    $keluar = MutasiKeluar::whereBetween('tanggal_pengajuan', [$start_date, $end_date])->count();
    echo "✅ Query Mutasi Keluar: OK (Count: $keluar)<br>";

    // Jika sampai sini aman, berarti masalahnya mungkin di View atau PDF
    echo "<hr><h3 style='color:green'>Database & Model Aman!</h3>";
    echo "Jika halaman rekap masih error 500, kemungkinan masalahnya ada di:<br>";
    echo "1. File View (<code>resources/views/laporan/rekap/index.blade.php</code>) error syntax.<br>";
    echo "2. Helper function yang hilang di View.<br>";

} catch (\Throwable $e) {
    echo "<hr><h3 style='color:red'>❌ ERROR DETECTED:</h3>";
    echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
>>>>>>> 9466a07 (Upload project pertama kali)
