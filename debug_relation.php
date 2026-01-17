<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 'MM202512290009'; // ID from screenshot
$mutasi = \App\Models\MutasiMasuk::find($id);

if (!$mutasi) {
    // Try to find ANY mutasi to test
    $mutasi = \App\Models\MutasiMasuk::first();
    echo "Requested ID not found, using first found: " . ($mutasi ? $mutasi->id_mutasi : 'NONE') . "\n";
}

if (!$mutasi) {
    echo "No Mutasi data found in table.\n";
    exit;
}

echo "Mutasi ID: " . $mutasi->id_mutasi . "\n";
echo "Pengusul (Raw): '" . $mutasi->pengusul . "'\n";

$user = \App\Models\User::where('username', $mutasi->pengusul)->first();

if ($user) {
    echo "User Found via manual query.\n";
    echo "User ID (PK): " . $user->id . "\n";
    echo "User Username: '" . $user->username . "'\n";
    echo "User nama_pengguna: '" . $user->nama_pengguna . "'\n";
    echo "User nama_sekolah: '" . $user->nama_sekolah . "'\n";
} else {
    echo "User NOT found via manual query with username='" . $mutasi->pengusul . "'.\n";

    // Check if user exists with whitespace diff
    $userLike = \App\Models\User::where('username', 'LIKE', '%' . $mutasi->pengusul . '%')->first();
    if ($userLike) {
        echo "Check: Found user with LIKE query: '" . $userLike->username . "'\n";
    }
}

echo "----------------\n";
echo "Testing Relationship:\n";
// Force reload
$mutasi->load('user');

if ($mutasi->user) {
    echo "Relation User: MATCHED.\n";
    echo "Relation nama_pengguna: '" . $mutasi->user->nama_pengguna . "'\n";
} else {
    echo "Relation User: NULL.\n";
    // Check relation definition
    $rel = $mutasi->user();
    echo "Relation Type: " . get_class($rel) . "\n";
    echo "ForeignKey: " . $rel->getForeignKeyName() . "\n";
    echo "OwnerKey: " . $rel->getOwnerKeyName() . "\n";
}
