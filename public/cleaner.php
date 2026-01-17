<?php

// SIMAK Cache Cleaner V2
// Upload ke public_html
// Akses: domain.com/cleaner.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧹 SIMAK Cache Cleaner</h1>";

// Deteksi Path Base Project
$basePath = null;
$possiblePaths = [
    __DIR__ . '/../simak_core',
    __DIR__ . '/..',
    $_SERVER['DOCUMENT_ROOT'] . '/../simak_core'
];

foreach ($possiblePaths as $path) {
    if (file_exists($path . '/artisan')) {
        $basePath = realpath($path);
        break;
    }
}

if (!$basePath) {
    die("❌ Error: Folder Laravel tidak ketemu. Edit file ini baris 15.");
}

echo "Base Path: $basePath<br><hr>";

// 1. HARD DELETE Bootstrap Cache (Paling Penting)
echo "<h3>1. Deleting Bootstrap Cache (Forces Re-discovery)...</h3>";
$files = [
    '/bootstrap/cache/packages.php',
    '/bootstrap/cache/services.php',
    '/bootstrap/cache/config.php',
    '/bootstrap/cache/routes-v7.php',
    '/bootstrap/cache/events.php',
    '/bootstrap/cache/data.php',
];

foreach ($files as $file) {
    $target = $basePath . $file;
    if (file_exists($target)) {
        if (unlink($target)) {
            echo "✅ Deleted: $file<br>";
        } else {
            echo "❌ Failed to delete: $file (Permission Denied)<br>";
        }
    } else {
        echo "ℹ️ Not found (Clean): $file<br>";
    }
}

// 2. Delete View Cache
echo "<h3>2. Deleting View Cache...</h3>";
$viewPath = $basePath . '/storage/framework/views';
$files = glob($viewPath . '/*.php');
$count = 0;
foreach ($files as $file) {
    if (is_file($file)) {
        @unlink($file);
        $count++;
    }
}
echo "✅ Cleared $count view files.<br>";

// 3. Try Artisan Optimize
echo "<h3>3. Rebuilding Cache (Artisan)...</h3>";
try {
    if (file_exists($basePath . '/vendor/autoload.php')) {
        require $basePath . '/vendor/autoload.php';
        $app = require_once $basePath . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

        echo "Running <code>optimize:clear</code>...<br>";
        $kernel->call('optimize:clear');
        echo "<pre>" . $kernel->output() . "</pre>";

        echo "Running <code>config:cache</code>...<br>";
        $kernel->call('config:cache');
        echo "<pre>" . $kernel->output() . "</pre>";

    } else {
        echo "⚠️ Folder vendor tidak ketemu, skip artisan.<br>";
    }
} catch (\Throwable $e) {
    echo "⚠️ Artisan Error (Ignored): " . $e->getMessage() . "<br>";
}

echo "<hr><h2>🎉 SELESAI! Silakan cek website.</h2>";
