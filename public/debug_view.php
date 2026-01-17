<?php
// Script Debugging View Laravel (Blade)
// Upload ke public_html
// Akses: domain.com/debug_view.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🎨 Debug View Rendering</h1>";

// 1. Boot Laravel
$paths = [
    __DIR__ . '/../simak_core/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    $_SERVER['DOCUMENT_ROOT'] . '/../simak_core/vendor/autoload.php'
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        require $path;
        break;
    }
}

try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );
    echo "✅ Laravel Booted.<br>";
} catch (\Throwable $e) {
    die("❌ Boot Error: " . $e->getMessage());
}

// 2. Setup Mock Data & User
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\User;

try {
    // Login sembarang user untuk Auth::user() di view
    $user = User::first();
    if (!$user) {
        die("❌ Tidak ada user di database. View butuh Auth::user().");
    }
    Auth::login($user);
    echo "✅ Logged in as: " . $user->username . " (" . $user->role . ")<br>";

    // Mock Data (Sesuai Controller)
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');

    // Dummy data structure
    $data = [];
    if ($user->role === 'Sekolah') {
        $data[] = [
            'periode' => 'Desember 2025',
            'masuk' => 5,
            'keluar' => 3
        ];
    } else {
        $data[] = [
            'nama_sekolah' => 'SMP Testing',
            'masuk' => 10,
            'keluar' => 5,
            'netto' => 5
        ];
    }
    echo "✅ Mock Interface Data Prepared.<br>";

    // 3. Render View
    echo "<h3>Attempting to Render View...</h3>";

    // Test render bagian inner content saja dulu jika bisa, tapi view index extends layout.
    // Kita coba render full.

    $html = View::make('laporan.rekap.index', compact('data', 'start_date', 'end_date'))->render();

    echo "<textarea style='width:100%; height:200px'>Rendering Success! HTML Output length: " . strlen($html) . "</textarea>";
    echo "<h3 style='color:green'>✅ VIEW TIDAK ERROR!</h3>";
    echo "Jika script ini sukses tapi website error 500, berarti masalahnya ada di Permissions Folder Storage (View Cache) atau Route Caching.";

} catch (\Throwable $e) {
    echo "<hr><h3 style='color:red'>❌ VIEW RENDERING ERROR:</h3>";
    echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . " line " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
