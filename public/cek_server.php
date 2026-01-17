<<<<<<< HEAD
<?php

// Script Pengecekan Server untuk SIMAK Mutasi
// Upload ke folder public_html
// Akses: domain.com/cek_server.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$status = [
    'ok' => '<span style="color:green; font-weight:bold">✅ OK</span>',
    'fail' => '<span style="color:red; font-weight:bold">❌ MISSING</span>',
    'warning' => '<span style="color:orange; font-weight:bold">⚠️ WARNING</span>'
];

echo "<h1>🔍 SIMAK Server Checker</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

echo "<h3>1. PHP Extensions Required</h3>";
$extensions = [
    'bcmath',
    'ctype',
    'fileinfo',
    'json',
    'mbstring',
    'openssl',
    'pdo',
    'tokenizer',
    'xml',
    'gd'
];

echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
foreach ($extensions as $ext) {
    $isLoaded = extension_loaded($ext);
    echo "<tr>";
    echo "<td>$ext</td>";
    echo "<td>" . ($isLoaded ? $status['ok'] : $status['fail']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>2. DomPDF Library Check</h3>";
// Mencoba deteksi autoload
$possiblePaths = [
    __DIR__ . '/../simak_core/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    $_SERVER['DOCUMENT_ROOT'] . '/../simak_core/vendor/autoload.php'
];

$autoloadFound = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloadFound = true;
        echo "<div>Autoload found at: <code>$path</code></div>";
        break;
    }
}

if ($autoloadFound) {
    if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
        echo "<p>Class <code>Barryvdh\DomPDF\Facade\Pdf</code> detect: " . $status['ok'] . "</p>";
    } else {
        echo "<p>Class <code>Barryvdh\DomPDF\Facade\Pdf</code> detect: " . $status['fail'] . "</p>";
        echo "<p><em>Kemungkinan folder vendor belum terupload sempurna atau perlu running 'composer dump-autoload' jika bisa.</em></p>";
    }

    if (class_exists('Dompdf\Dompdf')) {
        echo "<p>Class <code>Dompdf\Dompdf</code> detect: " . $status['ok'] . "</p>";
    } else {
        echo "<p>Class <code>Dompdf\Dompdf</code> detect: " . $status['fail'] . "</p>";
    }

} else {
    echo "<p style='color:red'>Vendor Autoload NOT FOUND. Tidak bisa mengecek DomPDF.</p>";
}


echo "<h3>3. Folder Permissions</h3>";
$folders = [
    '../storage' => 'Writable (Logs/Cache/Session)',
    '../bootstrap/cache' => 'Writable (App Cache)'
];

echo "<ul>";
foreach ($folders as $path => $desc) {
    $checkPath = __DIR__ . '/' . $path; // Adjust based on where script is relative to core
    // Try to resolve realpath if possible to be accurate
    $real = realpath($checkPath);
    if ($real) {
        $isWritable = is_writable($real);
        echo "<li>$real ($desc): " . ($isWritable ? $status['ok'] : $status['fail']) . "</li>";
    } else {
        echo "<li>$path ($desc): <span style='color:red'>Path not found check config</span></li>";
    }
}
echo "</ul>";

echo "<hr><p>Done.</p>";
=======
<?php

// Script Pengecekan Server untuk SIMAK Mutasi
// Upload ke folder public_html
// Akses: domain.com/cek_server.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$status = [
    'ok' => '<span style="color:green; font-weight:bold">✅ OK</span>',
    'fail' => '<span style="color:red; font-weight:bold">❌ MISSING</span>',
    'warning' => '<span style="color:orange; font-weight:bold">⚠️ WARNING</span>'
];

echo "<h1>🔍 SIMAK Server Checker</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

echo "<h3>1. PHP Extensions Required</h3>";
$extensions = [
    'bcmath',
    'ctype',
    'fileinfo',
    'json',
    'mbstring',
    'openssl',
    'pdo',
    'tokenizer',
    'xml',
    'gd'
];

echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
foreach ($extensions as $ext) {
    $isLoaded = extension_loaded($ext);
    echo "<tr>";
    echo "<td>$ext</td>";
    echo "<td>" . ($isLoaded ? $status['ok'] : $status['fail']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>2. DomPDF Library Check</h3>";
// Mencoba deteksi autoload
$possiblePaths = [
    __DIR__ . '/../simak_core/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
    $_SERVER['DOCUMENT_ROOT'] . '/../simak_core/vendor/autoload.php'
];

$autoloadFound = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloadFound = true;
        echo "<div>Autoload found at: <code>$path</code></div>";
        break;
    }
}

if ($autoloadFound) {
    if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
        echo "<p>Class <code>Barryvdh\DomPDF\Facade\Pdf</code> detect: " . $status['ok'] . "</p>";
    } else {
        echo "<p>Class <code>Barryvdh\DomPDF\Facade\Pdf</code> detect: " . $status['fail'] . "</p>";
        echo "<p><em>Kemungkinan folder vendor belum terupload sempurna atau perlu running 'composer dump-autoload' jika bisa.</em></p>";
    }

    if (class_exists('Dompdf\Dompdf')) {
        echo "<p>Class <code>Dompdf\Dompdf</code> detect: " . $status['ok'] . "</p>";
    } else {
        echo "<p>Class <code>Dompdf\Dompdf</code> detect: " . $status['fail'] . "</p>";
    }

} else {
    echo "<p style='color:red'>Vendor Autoload NOT FOUND. Tidak bisa mengecek DomPDF.</p>";
}


echo "<h3>3. Folder Permissions</h3>";
$folders = [
    '../storage' => 'Writable (Logs/Cache/Session)',
    '../bootstrap/cache' => 'Writable (App Cache)'
];

echo "<ul>";
foreach ($folders as $path => $desc) {
    $checkPath = __DIR__ . '/' . $path; // Adjust based on where script is relative to core
    // Try to resolve realpath if possible to be accurate
    $real = realpath($checkPath);
    if ($real) {
        $isWritable = is_writable($real);
        echo "<li>$real ($desc): " . ($isWritable ? $status['ok'] : $status['fail']) . "</li>";
    } else {
        echo "<li>$path ($desc): <span style='color:red'>Path not found check config</span></li>";
    }
}
echo "</ul>";

echo "<hr><p>Done.</p>";
>>>>>>> 9466a07 (Upload project pertama kali)
