<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DinasUserController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MutasiController;
use App\Http\Controllers\MutasiMasukController;
use App\Http\Controllers\PenandatanganController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueueWorkerController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SatuanPendidikanController;
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SystemStatusController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\TemporaryUploadController;
use App\Http\Controllers\UserManagementController;
use App\Http\Middleware\EnsurePasswordChanged;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Route;

// Official Telegram webhook. This exact path is the only Telegram CSRF exception.
Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);
Route::get('/telegram/set-webhook', [TelegramController::class, 'setWebhook']);
Route::get('/telegram/webhook-info', [TelegramController::class, 'webhookInfo']);

// Cron Routes (For external cron service like cron-job.org)
Route::get('/cron/reminder', [CronController::class, 'revisionReminder'])->middleware('cron.auth');
Route::get('/cron/work', [QueueWorkerController::class, 'work'])->middleware('cron.auth');

Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect()->route('login'));
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware(['auth', EnsurePasswordChanged::class])->group(function () {
    // Force Password Change Routes
    Route::get('/password/change', [ChangePasswordController::class, 'show'])->name('password.change');
    Route::post('/password/update', [ChangePasswordController::class, 'update'])->name('password.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Laporan Routes
    // Laporan Routes
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

    // --- Rute Sementara untuk Cek Server & Maintenance (Tanpa SSH) ---
    Route::get('/cek-server', function () {
        // 1. Cek Extension
        $required = ['bcmath', 'ctype', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'gd'];
        $missing = [];
        foreach ($required as $ext) {
            if (! extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        // 2. Cek DomPDF Class
        $dompdfStatus = class_exists(Pdf::class) ? 'Terinstall ✅' : 'Tidak Terbaca (Cek folder vendor) ❌';

        // 3. Clear Cache (Opsional, uncomment jika perlu)
        // \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        // $cacheStatus = "Cache cleared!";

        return response()->json([
            'status' => empty($missing) ? 'OK' : 'MISSING EXTENSIONS',
            'missing' => $missing,
            'php_version' => phpversion(),
        ]);
    })->middleware('role:Dinas,SuperAdmin');

    // --- End Maintenance Routes ---
    // ------------------------------------------------------------------

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');
    Route::post('/profile/telegram/test', [ProfileController::class, 'testTelegram'])
        ->middleware('throttle:6,1')
        ->name('profile.telegram.test');

    // School Profile (For Sekolah/Operator)
    Route::prefix('sekolah')->name('sekolah.')->group(function () {
        Route::get('/profil', [SatuanPendidikanController::class, 'editProfile'])->name('profile');
        Route::put('/profil', [SatuanPendidikanController::class, 'updateProfile'])->name('profile.update');
    });

    // Mutasi Keluar
    Route::prefix('mutasi-keluar')->name('mutasi.keluar.')->group(function () {
        Route::get('/', [MutasiController::class, 'index'])->name('index');
        Route::get('/create', [MutasiController::class, 'create'])->middleware('workday')->name('create');
        Route::post('/', [MutasiController::class, 'store'])->middleware('workday')->name('store');
        Route::get('/api/npsn/{npsn}', [MutasiController::class, 'lookupNpsn'])->middleware('throttle:lookups')->name('api.npsn');
        Route::get('/api/provinces', [SatuanPendidikanController::class, 'getProvinces'])->middleware('throttle:lookups')->name('api.provinces');
        Route::get('/api/regencies/{provinceId}', [SatuanPendidikanController::class, 'getRegencies'])->middleware('throttle:lookups')->name('api.regencies');
        Route::delete('/{id}', [MutasiController::class, 'destroy'])->name('destroy'); // DELETE ROUTE
        Route::put('/{id}/cancel', [MutasiController::class, 'cancel'])->name('cancel'); // CANCEL ROUTE
        Route::post('/draft', [MutasiController::class, 'saveDraft'])->name('draft.save'); // SAVE DRAFT
        Route::get('/draft/{id}/resume', [MutasiController::class, 'resumeDraft'])->name('draft.resume'); // RESUME DRAFT
        Route::delete('/draft/{id}', [MutasiController::class, 'deleteDraft'])->name('draft.delete');
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
        Route::get('/api/npsn/{npsn}', [MutasiMasukController::class, 'lookupNpsn'])->middleware('throttle:lookups')->name('api.npsn');
        // Reuse SatuanPendidikanController for consistent region data
        Route::get('/api/provinces', [SatuanPendidikanController::class, 'getProvinces'])->middleware('throttle:lookups')->name('api.provinces');
        Route::get('/api/regencies/{provinceId}', [SatuanPendidikanController::class, 'getRegencies'])->middleware('throttle:lookups')->name('api.regencies');
        Route::delete('/{id}', [MutasiMasukController::class, 'destroy'])->name('destroy'); // DELETE ROUTE
        Route::put('/{id}/cancel', [MutasiMasukController::class, 'cancel'])->name('cancel'); // CANCEL ROUTE
        Route::get('/{id}', [MutasiMasukController::class, 'show'])->name('show');

        // Revision Routes (with workday restriction on submit)
        Route::get('/{id}/revision', [MutasiMasukController::class, 'revisionEdit'])->name('revision');
        Route::put('/{id}/revision', [MutasiMasukController::class, 'revisionResubmit'])->middleware('workday')->name('revision.submit');

        // Draft API Routes (Auto-Save Feature)
        Route::post('/draft', [MutasiMasukController::class, 'createDraft'])->middleware('workday')->name('draft.create');
        Route::post('/draft/{id}/update-header', [MutasiMasukController::class, 'updateDraftHeader'])->name('draft.update-header');
        Route::post('/draft/{id}/siswa', [MutasiMasukController::class, 'addStudentToDraft'])->name('draft.addStudent');
        Route::post('/draft/{id}/finalize', [MutasiMasukController::class, 'finalizeDraft'])->name('draft.finalize');
        Route::get('/draft/{id}/resume', [MutasiMasukController::class, 'resumeDraft'])->name('draft.resume');
        Route::delete('/draft/{id}', [MutasiMasukController::class, 'deleteDraft'])->name('draft.delete');
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
        Route::post('/mutasi-keluar/{id}/verify', [MutasiController::class, 'verify'])->middleware('workday')->name('mutasi.keluar.verify');
        Route::post('/mutasi-keluar/{id}/issue-letter', [MutasiController::class, 'issueLetter'])->middleware('workday')->name('mutasi.keluar.issue-letter');
        Route::post('/mutasi-keluar/{id}/complete-letter', [MutasiController::class, 'completeLetter'])->middleware('workday')->name('mutasi.keluar.complete-letter');
        Route::get('/mutasi-keluar/{id}/print-letter', [MutasiController::class, 'printLetter'])->name('mutasi.keluar.print-letter');
        Route::get('/mutasi-keluar/{id}/edit', [MutasiController::class, 'edit'])->name('mutasi.keluar.edit');
        Route::put('/mutasi-keluar/{id}', [MutasiController::class, 'update'])->name('mutasi.keluar.update');

        Route::post('/mutasi-masuk/{id}/verify', [MutasiMasukController::class, 'verify'])->middleware('workday')->name('mutasi.masuk.verify');
        Route::post('/mutasi-masuk/{id}/issue-letter', [MutasiMasukController::class, 'issueLetter'])->middleware('workday')->name('mutasi.masuk.issue-letter');
        Route::post('/mutasi-masuk/{id}/complete-letter', [MutasiMasukController::class, 'completeLetter'])->middleware('workday')->name('mutasi.masuk.complete-letter');
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
        Route::post('/dinas/pengguna/{id}/unlock', [DinasUserController::class, 'unlock'])->name('dinas.users.unlock');
        Route::post('/dinas/pengguna/{id}/reset', [DinasUserController::class, 'resetPassword'])->name('dinas.users.reset');

        // Penandatangan
        Route::get('/penandatangan', [PenandatanganController::class, 'index'])->name('penandatangan.index');
        Route::get('penandatangan/trash', [PenandatanganController::class, 'trash'])->name('penandatangan.trash');
        Route::resource('penandatangan', PenandatanganController::class)->except(['create', 'show', 'edit', 'destroy']);
        Route::delete('penandatangan/{id}', [PenandatanganController::class, 'destroy'])->name('penandatangan.destroy');
        Route::post('penandatangan/{id}/restore', [PenandatanganController::class, 'restore'])->name('penandatangan.restore');
        Route::post('penandatangan/{id}/toggle', [PenandatanganController::class, 'toggleStatus'])->name('penandatangan.toggle');
        Route::delete('penandatangan/{id}/signature', [PenandatanganController::class, 'deleteSignature'])->name('penandatangan.delete-signature');

        // Manajemen Wilayah (Regional Data)
        Route::get('/admin/wilayah', [RegionController::class, 'index'])->name('admin.regions.index');
        Route::get('/admin/wilayah/{id}', [RegionController::class, 'show'])->name('admin.regions.show');
        Route::post('/admin/wilayah/sync', [RegionController::class, 'sync'])->name('admin.regions.sync');
        Route::post('/admin/wilayah', [RegionController::class, 'store'])->name('admin.regions.store');
        Route::delete('/admin/wilayah', [RegionController::class, 'destroy'])->name('admin.regions.destroy');

        // Permission Management (SuperAdmin only)
        Route::get('/users/{user}/permissions', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::patch('/users/{user}/permissions', [PermissionController::class, 'update'])->name('permissions.update');
    });

    // SuperAdmin & Dinas routes (Settings)
    Route::middleware('role:Dinas,SuperAdmin')->group(function () {
        Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/pengaturan/tahun-pelajaran', [AcademicYearController::class, 'store'])
            ->name('academic-years.store');
        Route::patch('/pengaturan/tahun-pelajaran/{academicYearId}/periode', [AcademicYearController::class, 'updateSchedule'])
            ->name('academic-years.schedule.update');
        Route::patch('/pengaturan/tahun-pelajaran/{academicYearId}/aktifkan', [AcademicYearController::class, 'activate'])
            ->name('academic-years.activate');

        // System Status
        Route::get('/system-status', [SystemStatusController::class, 'index'])->name('system.status');
        Route::get('/system-status/refresh', [SystemStatusController::class, 'refresh'])->name('system.status.refresh');

        // Holiday management
        Route::post('/holidays', [HolidayController::class, 'store'])->name('holidays.store');
        Route::put('/holidays/{id}', [HolidayController::class, 'update'])->name('holidays.update');
        Route::delete('/holidays/{id}', [HolidayController::class, 'destroy'])->name('holidays.destroy');

        // Satuan Pendidikan Management
        Route::prefix('satuan-pendidikan')->name('satpen.')->group(function () {
            Route::get('/', [SatuanPendidikanController::class, 'manage'])->name('index');
            Route::get('/create', [SatuanPendidikanController::class, 'create'])->name('create');
            Route::post('/', [SatuanPendidikanController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [SatuanPendidikanController::class, 'edit'])->name('edit');
            Route::put('/{id}', [SatuanPendidikanController::class, 'update'])->name('update');
        });
    });

    // Satuan Pendidikan API (Referensi Sekolah - JSON endpoints)
    Route::prefix('api/satuan-pendidikan')->middleware('throttle:lookups')->name('satpen.api.')->group(function () {
        Route::get('/', [SatuanPendidikanController::class, 'index'])->name('list');
        Route::get('/search', [SatuanPendidikanController::class, 'search'])->name('search');
        Route::get('/npsn/{npsn}', [SatuanPendidikanController::class, 'findByNpsn'])->name('find');
    });

    // Wilayah API (emsifa proxy with cache)
    Route::prefix('wilayah')->middleware('throttle:lookups')->name('wilayah.')->group(function () {
        Route::get('/provinces', [SatuanPendidikanController::class, 'getProvinces'])->name('provinces');
        Route::get('/regencies/{provinceId}', [SatuanPendidikanController::class, 'getRegencies'])->name('regencies');
        Route::get('/districts/{regencyId}', [SatuanPendidikanController::class, 'getDistricts'])->name('districts');
        Route::get('/villages/{districtId}', [SatuanPendidikanController::class, 'getVillages'])->name('villages');
    });

    // Secure File Access
    // Handles access to private documents
    Route::get('/dokumen/lihat/{path}', [SecureFileController::class, 'show'])
        ->where('path', '.*') // Allow slashes in path
        ->name('dokumen.view');
});

// Keep old route for backward compatibility
Route::middleware('auth')->group(function () {
    Route::post('/ajax/upload-temp', [TemporaryUploadController::class, 'store'])->middleware('throttle:uploads')->name('api.upload-temp');
    Route::post('/ajax/upload-temp-url', [TemporaryUploadController::class, 'generateUploadUrl'])->middleware('throttle:uploads')->name('api.upload-temp-url');
    Route::match(['get', 'post'], '/ajax/debug-connection', function () {
        $user = request()->user();
        if (! app()->isLocal() && (! $user || ! in_array($user->role, ['Dinas', 'SuperAdmin'], true))) {
            abort(404);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Connection successful',
            'timestamp' => now()->toIso8601String(),
            'method' => request()->method(),
            'url' => app()->isLocal() ? request()->fullUrl() : request()->path(),
            'csrf_received' => request()->header('X-CSRF-TOKEN') ? 'yes' : 'no',
        ]);
    });
    Route::get('/mutasi', fn () => redirect()->route('mutasi.keluar.index'));
    Route::post('/session/alive', fn () => response()->noContent())->name('session.alive');
});
