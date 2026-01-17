<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DinasUserController;
use App\Http\Controllers\MutasiController;
use App\Http\Controllers\MutasiMasukController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PenandatanganController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SatuanPendidikanController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

// Public routes
// Telegram Webhook (Bypass CSRF via Middleware)
Route::post('/telegram/webhook', [\App\Http\Controllers\TelegramController::class, 'webhook']);
Route::get('/telegram/set-webhook', [\App\Http\Controllers\TelegramController::class, 'setWebhook']);
Route::get('/telegram/webhook-info', [\App\Http\Controllers\TelegramController::class, 'webhookInfo']);

// Cron Routes (For external cron service like cron-job.org)
Route::get('/cron/reminder/{token}', [CronController::class, 'revisionReminder']);

Route::middleware('guest')->group(function () {
    Route::get('/', fn() => redirect()->route('login'));
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1'); // 5 attempts per minute
});

// Authenticated routes
Route::middleware(['auth', \App\Http\Middleware\EnsurePasswordChanged::class])->group(function () {
    // Force Password Change Routes
    Route::get('/password/change', [\App\Http\Controllers\ChangePasswordController::class, 'show'])->name('password.change');
    Route::post('/password/update', [\App\Http\Controllers\ChangePasswordController::class, 'update'])->name('password.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard (with daily tasks middleware for auto-reminders and auto-cancel)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('dailytasks')
        ->name('dashboard');

    // Laporan Routes
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('/rekap', [LaporanController::class, 'rekap'])->name('rekap');
        Route::get('/rekap/print', [LaporanController::class, 'printRekap'])->name('rekap.print');
    });

    // --- Rute Sementara untuk Cek Server & Maintenance (Tanpa SSH) ---
    Route::get('/cek-server', function () {
        // 1. Cek Extension
        $required = ['bcmath', 'ctype', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'gd'];
        $missing = [];
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        // 2. Cek DomPDF Class
        $dompdfStatus = class_exists(\Barryvdh\DomPDF\Facade\Pdf::class) ? 'Terinstall ✅' : 'Tidak Terbaca (Cek folder vendor) ❌';

        // 3. Clear Cache (Opsional, uncomment jika perlu)
        // \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        // $cacheStatus = "Cache cleared!";

        return response()->json([
            'status' => empty($missing) ? 'OK' : 'MISSING EXTENSIONS',
            'missing' => $missing,
            'php_version' => phpversion(),
        ]);
    });

    // --- End Maintenance Routes ---
    // ------------------------------------------------------------------

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');

    // Mutasi Keluar
    Route::prefix('mutasi-keluar')->name('mutasi.keluar.')->group(function () {
        Route::get('/', [MutasiController::class, 'index'])->name('index');
        Route::get('/create', [MutasiController::class, 'create'])->middleware('workday')->name('create');
        Route::post('/', [MutasiController::class, 'store'])->middleware('workday')->name('store');
        Route::get('/api/npsn/{npsn}', [MutasiController::class, 'lookupNpsn'])->name('api.npsn');
        Route::get('/api/provinces', [SatuanPendidikanController::class, 'getProvinces'])->name('api.provinces');
        Route::get('/api/regencies/{provinceId}', [SatuanPendidikanController::class, 'getRegencies'])->name('api.regencies');
        Route::delete('/{id}', [MutasiController::class, 'destroy'])->name('destroy'); // DELETE ROUTE
        Route::put('/{id}/cancel', [MutasiController::class, 'cancel'])->name('cancel'); // CANCEL ROUTE
        Route::get('/{id}', [MutasiController::class, 'show'])->name('show');

        // Revision Routes (with workday restriction on submit)
        Route::get('/{id}/revision', [MutasiController::class, 'revisionEdit'])->name('revision');
        Route::put('/{id}/revision', [MutasiController::class, 'revisionResubmit'])->middleware('workday')->name('revision.submit');
    });

    // Mutasi Masuk
    Route::prefix('mutasi-masuk')->name('mutasi.masuk.')->group(function () {
        Route::get('/', [MutasiMasukController::class, 'index'])->name('index');
        Route::get('/create', [MutasiMasukController::class, 'create'])->middleware('workday')->name('create');
        Route::post('/', [MutasiMasukController::class, 'store'])->middleware('workday')->name('store');
        Route::get('/api/npsn/{npsn}', [MutasiMasukController::class, 'lookupNpsn'])->name('api.npsn');
        // Reuse SatuanPendidikanController for consistent region data
        Route::get('/api/provinces', [SatuanPendidikanController::class, 'getProvinces'])->name('api.provinces');
        Route::get('/api/regencies/{provinceId}', [SatuanPendidikanController::class, 'getRegencies'])->name('api.regencies');
        Route::delete('/{id}', [MutasiMasukController::class, 'destroy'])->name('destroy'); // DELETE ROUTE
        Route::put('/{id}/cancel', [MutasiMasukController::class, 'cancel'])->name('cancel'); // CANCEL ROUTE
        Route::get('/{id}', [MutasiMasukController::class, 'show'])->name('show');

        // Revision Routes (with workday restriction on submit)
        Route::get('/{id}/revision', [MutasiMasukController::class, 'revisionEdit'])->name('revision');
        Route::put('/{id}/revision', [MutasiMasukController::class, 'revisionResubmit'])->middleware('workday')->name('revision.submit');
    });

    // User Management (Sekolah Only)
    Route::middleware('role:Sekolah')->group(function () {
        Route::get('/kelola-pengguna', [UserManagementController::class, 'index'])->name('users.manage');
        Route::post('/kelola-pengguna', [UserManagementController::class, 'store'])->name('users.store');
        Route::post('/kelola-pengguna/{id}/toggle', [UserManagementController::class, 'toggleStatus'])->name('users.toggle');
        Route::post('/kelola-pengguna/{id}/reset', [UserManagementController::class, 'resetPassword'])->name('users.reset');
    });

    // Dinas only routes
    Route::middleware('role:Dinas,SuperAdmin')->group(function () {
        // Verification
        Route::post('/mutasi-keluar/{id}/verify', [MutasiController::class, 'verify'])->name('mutasi.keluar.verify');
        Route::post('/mutasi-keluar/{id}/issue-letter', [MutasiController::class, 'issueLetter'])->name('mutasi.keluar.issue-letter');
        Route::post('/mutasi-keluar/{id}/complete-letter', [MutasiController::class, 'completeLetter'])->name('mutasi.keluar.complete-letter');
        Route::get('/mutasi-keluar/{id}/print-letter', [MutasiController::class, 'printLetter'])->name('mutasi.keluar.print-letter');
        Route::get('/mutasi-keluar/{id}/edit', [MutasiController::class, 'edit'])->name('mutasi.keluar.edit');
        Route::put('/mutasi-keluar/{id}', [MutasiController::class, 'update'])->name('mutasi.keluar.update');

        Route::post('/mutasi-masuk/{id}/verify', [MutasiMasukController::class, 'verify'])->name('mutasi.masuk.verify');
        Route::post('/mutasi-masuk/{id}/issue-letter', [MutasiMasukController::class, 'issueLetter'])->name('mutasi.masuk.issue-letter');
        Route::post('/mutasi-masuk/{id}/complete-letter', [MutasiMasukController::class, 'completeLetter'])->name('mutasi.masuk.complete-letter');
        Route::get('/mutasi-masuk/{id}/print-letter', [MutasiMasukController::class, 'printLetter'])->name('mutasi.masuk.print-letter');
        Route::get('/mutasi-masuk/{id}/edit', [MutasiMasukController::class, 'edit'])->name('mutasi.masuk.edit');
        Route::put('/mutasi-masuk/{id}', [MutasiMasukController::class, 'update'])->name('mutasi.masuk.update');

        // Digital Archive Upload
        Route::post('/mutasi-keluar/{id}/upload-arsip', [MutasiController::class, 'uploadArsip'])->name('mutasi.keluar.upload-arsip');
        Route::post('/mutasi-masuk/{id}/upload-arsip', [MutasiMasukController::class, 'uploadArsip'])->name('mutasi.masuk.upload-arsip');

        // Dinas User Management
        Route::get('/dinas/pengguna', [DinasUserController::class, 'index'])->name('dinas.users');
        Route::post('/dinas/pengguna', [DinasUserController::class, 'store'])->name('dinas.users.store');
        Route::get('/dinas/pengguna/npsn/{npsn}', [DinasUserController::class, 'lookupNpsn'])->name('dinas.users.npsn');
        Route::get('/dinas/pengguna/{id}/edit', [DinasUserController::class, 'edit'])->name('dinas.users.edit');
        Route::put('/dinas/pengguna/{id}', [DinasUserController::class, 'update'])->name('dinas.users.update');
        Route::post('/dinas/pengguna/{id}/toggle', [DinasUserController::class, 'toggleStatus'])->name('dinas.users.toggle');
        Route::post('/dinas/pengguna/{id}/reset', [DinasUserController::class, 'resetPassword'])->name('dinas.users.reset');

        // Penandatangan
        Route::get('/penandatangan', [PenandatanganController::class, 'index'])->name('penandatangan.index');
        Route::get('penandatangan/trash', [App\Http\Controllers\PenandatanganController::class, 'trash'])->name('penandatangan.trash');
        Route::resource('penandatangan', App\Http\Controllers\PenandatanganController::class)->except(['create', 'show', 'edit', 'destroy']);
        Route::delete('penandatangan/{id}', [App\Http\Controllers\PenandatanganController::class, 'destroy'])->name('penandatangan.destroy');
        Route::post('penandatangan/{id}/restore', [App\Http\Controllers\PenandatanganController::class, 'restore'])->name('penandatangan.restore');
        Route::post('penandatangan/{id}/toggle', [App\Http\Controllers\PenandatanganController::class, 'toggleStatus'])->name('penandatangan.toggle');
        Route::put('/penandatangan/{id}', [PenandatanganController::class, 'update'])->name('penandatangan.update');

        // Laporan (Reports)
        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('/', [LaporanController::class, 'index'])->name('index');
            Route::get('/rekap', [LaporanController::class, 'rekap'])->name('rekap');
            Route::get('/rekap/print', [LaporanController::class, 'printRekap'])->name('rekap.print');
            Route::get('/rekap/excel', [LaporanController::class, 'exportRekapExcel'])->name('rekap.excel');
            Route::get('/tren', [LaporanController::class, 'tren'])->name('tren');
            Route::get('/komprehensif/print', [LaporanController::class, 'printKomprehensif'])->name('cetak-komprehensif');
            Route::get('/nominatif', [LaporanController::class, 'nominatif'])->name('nominatif');
            Route::get('/nominatif/print', [LaporanController::class, 'printNominatif'])->name('nominatif.print');
            Route::get('/nominatif/excel', [LaporanController::class, 'exportNominatifExcel'])->name('nominatif.excel');
        });

        // Manajemen Wilayah (Regional Data)
        Route::get('/admin/wilayah', [\App\Http\Controllers\RegionController::class, 'index'])->name('admin.regions.index');
        Route::get('/admin/wilayah/{id}', [\App\Http\Controllers\RegionController::class, 'show'])->name('admin.regions.show');
        Route::post('/admin/wilayah/sync', [\App\Http\Controllers\RegionController::class, 'sync'])->name('admin.regions.sync');
        Route::post('/admin/wilayah', [\App\Http\Controllers\RegionController::class, 'store'])->name('admin.regions.store');
        Route::delete('/admin/wilayah', [\App\Http\Controllers\RegionController::class, 'destroy'])->name('admin.regions.destroy');
    });

    // SuperAdmin & Dinas routes (Settings)
    Route::middleware('role:Dinas,SuperAdmin')->group(function () {
        Route::get('/pengaturan', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
        Route::post('/pengaturan', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');

        // System Status
        Route::get('/system-status', [\App\Http\Controllers\SystemStatusController::class, 'index'])->name('system.status');
        Route::get('/system-status/refresh', [\App\Http\Controllers\SystemStatusController::class, 'refresh'])->name('system.status.refresh');

        // Holiday management
        Route::post('/holidays', [\App\Http\Controllers\HolidayController::class, 'store'])->name('holidays.store');
        Route::put('/holidays/{id}', [\App\Http\Controllers\HolidayController::class, 'update'])->name('holidays.update');
        Route::delete('/holidays/{id}', [\App\Http\Controllers\HolidayController::class, 'destroy'])->name('holidays.destroy');

        // Satuan Pendidikan Management
        Route::prefix('satuan-pendidikan')->name('satpen.')->group(function () {
            Route::get('/', [\App\Http\Controllers\SatuanPendidikanController::class, 'manage'])->name('index');
            Route::get('/create', [\App\Http\Controllers\SatuanPendidikanController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\SatuanPendidikanController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [\App\Http\Controllers\SatuanPendidikanController::class, 'edit'])->name('edit');
            Route::put('/{id}', [\App\Http\Controllers\SatuanPendidikanController::class, 'update'])->name('update');
        });
    });

    // Satuan Pendidikan API (Referensi Sekolah - JSON endpoints)
    Route::prefix('api/satuan-pendidikan')->name('satpen.api.')->group(function () {
        Route::get('/', [SatuanPendidikanController::class, 'index'])->name('list');
        Route::get('/search', [SatuanPendidikanController::class, 'search'])->name('search');
        Route::get('/npsn/{npsn}', [SatuanPendidikanController::class, 'findByNpsn'])->name('find');
    });

    // Wilayah API (emsifa proxy with cache)
    Route::prefix('wilayah')->name('wilayah.')->group(function () {
        Route::get('/provinces', [SatuanPendidikanController::class, 'getProvinces'])->name('provinces');
        Route::get('/regencies/{provinceId}', [SatuanPendidikanController::class, 'getRegencies'])->name('regencies');
        Route::get('/districts/{regencyId}', [SatuanPendidikanController::class, 'getDistricts'])->name('districts');
        Route::get('/villages/{districtId}', [SatuanPendidikanController::class, 'getVillages'])->name('villages');
    });

    // Secure File Access
    // Handles access to private documents
    Route::get('/dokumen/lihat/{path}', [\App\Http\Controllers\SecureFileController::class, 'show'])
        ->where('path', '.*') // Allow slashes in path
        ->name('dokumen.view');
});

// Keep old route for backward compatibility
Route::middleware('auth')->group(function () {
    Route::get('/mutasi', fn() => redirect()->route('mutasi.keluar.index'));
    Route::post('/session/alive', fn() => response()->noContent())->name('session.alive');
});