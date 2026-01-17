

<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-gray-500 mt-1">Selamat datang, <?php echo e($user->nama_pengguna ?? $user->username); ?>!</p>
            </div>
            <div class="text-sm text-gray-500">
                <i class="bi bi-calendar3 mr-1"></i>
                <?php echo e(now()->translatedFormat('l, j F Y')); ?>

            </div>
        </div>

        <!-- Mutasi Keluar Stats -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="bi bi-box-arrow-right text-orange-600"></i>
                Statistik Mutasi Keluar
            </h2>
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <!-- Total Keluar -->
                <div class="bg-orange-50 rounded-xl p-4 border border-orange-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-archive text-orange-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Total</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo e(number_format($statsKeluar['total'])); ?></p>
                        </div>
                    </div>
                </div>
                <!-- Menunggu -->
                <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-hourglass-split text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Menunggu</p>
                            <p class="text-2xl font-bold text-yellow-600"><?php echo e(number_format($statsKeluar['menunggu'])); ?></p>
                        </div>
                    </div>
                </div>
                <!-- Proses -->
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-gear text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Proses</p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo e(number_format($statsKeluar['proses'])); ?></p>
                        </div>
                    </div>
                </div>
                <!-- Disetujui -->
                <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-check-circle text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Disetujui</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo e(number_format($statsKeluar['disetujui'])); ?></p>
                        </div>
                    </div>
                </div>
                <!-- Selesai -->
                <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-check-all text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Selesai</p>
                            <p class="text-2xl font-bold text-emerald-600"><?php echo e(number_format($statsKeluar['selesai'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mutasi Masuk Stats -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="bi bi-box-arrow-in-right text-blue-600"></i>
                Statistik Mutasi Masuk
            </h2>
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
                <!-- Total Masuk -->
                <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-archive text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Total</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo e(number_format($statsMasuk['total'])); ?></p>
                        </div>
                    </div>
                </div>
                <!-- Total Siswa -->
                <div class="bg-indigo-50 rounded-xl p-4 border border-indigo-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-people text-indigo-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Siswa</p>
                            <p class="text-2xl font-bold text-indigo-600"><?php echo e(number_format($statsMasuk['total_siswa'])); ?>

                            </p>
                        </div>
                    </div>
                </div>
                <!-- Menunggu -->
                <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-hourglass-split text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Menunggu</p>
                            <p class="text-2xl font-bold text-yellow-600"><?php echo e(number_format($statsMasuk['menunggu'])); ?></p>
                        </div>
                    </div>
                </div>
                <!-- Disetujui -->
                <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-check-circle text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Disetujui</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo e(number_format($statsMasuk['disetujui'])); ?></p>
                        </div>
                    </div>
                </div>
                <!-- Perbaikan -->
                <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-exclamation-triangle text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Perbaikan</p>
                            <p class="text-2xl font-bold text-red-600"><?php echo e(number_format($statsMasuk['perbaikan'])); ?></p>
                        </div>
                    </div>
                </div>
                <!-- Selesai -->
                <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-check-all text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Selesai</p>
                            <p class="text-2xl font-bold text-emerald-600"><?php echo e(number_format($statsMasuk['selesai'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Stats (Sekolah / Dinas) -->
        <?php if($statsUsers): ?>
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <i class="bi bi-people text-purple-600"></i>
                    Statistik Pengguna
                </h2>

                <?php if($user->role === 'Sekolah'): ?>
                    <!-- Sekolah: Stats Operator saja -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-purple-50 rounded-xl p-4 border border-purple-100">
                            <p class="text-xs text-gray-500">Total Operator</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo e(number_format($statsUsers['total'])); ?></p>
                        </div>
                        <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                            <p class="text-xs text-gray-500">Aktif</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo e(number_format($statsUsers['active'])); ?></p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-4 border border-red-100">
                            <p class="text-xs text-gray-500">Non-Aktif</p>
                            <p class="text-2xl font-bold text-red-600"><?php echo e(number_format($statsUsers['inactive'])); ?></p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="<?php echo e(route('users.manage')); ?>"
                            class="inline-flex items-center text-sm text-purple-600 hover:text-purple-700 font-medium">
                            <i class="bi bi-arrow-right mr-1"></i> Kelola Pengguna
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Dinas: Stats Sekolah & Operator -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Role Sekolah -->
                        <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold text-blue-800">Role Sekolah</span>
                                <span
                                    class="text-xl font-bold text-blue-900"><?php echo e(number_format($statsUsers['sekolah_total'] ?? 0)); ?></span>
                            </div>
                            <div class="flex gap-4 text-sm">
                                <span class="text-green-600"><i class="bi bi-check-circle mr-1"></i>Aktif:
                                    <?php echo e($statsUsers['sekolah_active'] ?? 0); ?></span>
                                <span class="text-red-600"><i class="bi bi-x-circle mr-1"></i>Non-Aktif:
                                    <?php echo e($statsUsers['sekolah_inactive'] ?? 0); ?></span>
                            </div>
                        </div>
                        <!-- Role Operator -->
                        <div class="bg-green-50 rounded-xl p-4 border border-green-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-semibold text-green-800">Role Operator</span>
                                <span
                                    class="text-xl font-bold text-green-900"><?php echo e(number_format($statsUsers['operator_total'] ?? 0)); ?></span>
                            </div>
                            <div class="flex gap-4 text-sm">
                                <span class="text-green-600"><i class="bi bi-check-circle mr-1"></i>Aktif:
                                    <?php echo e($statsUsers['operator_active'] ?? 0); ?></span>
                                <span class="text-red-600"><i class="bi bi-x-circle mr-1"></i>Non-Aktif:
                                    <?php echo e($statsUsers['operator_inactive'] ?? 0); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="<?php echo e(route('dinas.users')); ?>"
                            class="inline-flex items-center text-sm text-purple-600 hover:text-purple-700 font-medium">
                            <i class="bi bi-arrow-right mr-1"></i> Kelola Pengguna
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Statistik Mutasi per Sekolah -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <i class="bi bi-building text-primary-600"></i>
                    Statistik Mutasi Per Sekolah
                </h2>

                <!-- Filter Form -->
                <form method="GET" action="<?php echo e(route('dashboard')); ?>" class="flex gap-2 items-center flex-wrap">
                    <select name="month"
                        class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-primary-100 focus:border-primary-500">
                        <option value="">Semua Bulan</option>
                        <?php
                            $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        ?>
                        <?php $__currentLoopData = $months; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($idx + 1); ?>" <?php echo e($filterMonth == ($idx + 1) ? 'selected' : ''); ?>><?php echo e($m); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <select name="year"
                        class="text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-primary-100 focus:border-primary-500">
                        <option value="">Semua Tahun</option>
                        <?php $__currentLoopData = $availableYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($y); ?>" <?php echo e($filterYear == $y ? 'selected' : ''); ?>><?php echo e($y); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <button type="submit"
                        class="text-sm bg-primary-600 text-white px-4 py-1.5 rounded-lg hover:bg-primary-700 transition">
                        <i class="bi bi-funnel mr-1"></i>Filter
                    </button>
                    <?php if($filterMonth || $filterYear): ?>
                        <a href="<?php echo e(route('dashboard')); ?>" class="text-sm text-gray-500 hover:text-gray-700">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if($filterMonth || $filterYear): ?>
                <div class="mb-4 text-sm text-gray-500 bg-gray-50 px-3 py-2 rounded-lg">
                    <i class="bi bi-info-circle mr-1"></i>
                    Menampilkan data:
                    <?php if($filterMonth): ?>
                        <?php echo e($months[$filterMonth - 1]); ?>

                    <?php endif; ?>
                    <?php if($filterYear): ?>
                        <?php echo e($filterYear); ?>

                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Tabel Mutasi Keluar -->
                <div>
                    <h3 class="text-sm font-semibold text-orange-600 mb-3 flex items-center gap-2">
                        <i class="bi bi-box-arrow-right"></i>
                        Mutasi Keluar (Top 10)
                    </h3>
                    <?php if(count($schoolStatsKeluar) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-orange-50 border-b">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">#</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Sekolah Asal</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-600">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php $__currentLoopData = $schoolStatsKeluar; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 text-gray-500"><?php echo e($idx + 1); ?></td>
                                            <td class="px-3 py-2 text-gray-900"><?php echo e($item->nama_sekolah ?? '-'); ?></td>
                                            <td class="px-3 py-2 text-center">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    <?php echo e($item->jumlah); ?>

                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-6 text-gray-400">
                            <i class="bi bi-inbox text-3xl"></i>
                            <p class="mt-2 text-sm">Belum ada data</p>
                        </div>
                    <?php endif; ?>
                    <div class="mt-3">
                        <a href="<?php echo e(route('mutasi.keluar.index')); ?>"
                            class="text-sm text-orange-600 hover:text-orange-700 font-medium">
                            <i class="bi bi-arrow-right mr-1"></i> Lihat Semua Mutasi Keluar
                        </a>
                    </div>
                </div>

                <!-- Tabel Mutasi Masuk -->
                <div>
                    <h3 class="text-sm font-semibold text-blue-600 mb-3 flex items-center gap-2">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Mutasi Masuk (Top 10)
                    </h3>
                    <?php if(count($schoolStatsMasuk) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-blue-50 border-b">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">#</th>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-600">Sekolah Tujuan</th>
                                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-600">Siswa</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <?php $__currentLoopData = $schoolStatsMasuk; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2 text-gray-500"><?php echo e($idx + 1); ?></td>
                                            <td class="px-3 py-2 text-gray-900"><?php echo e($item->nama_sekolah ?? '-'); ?></td>
                                            <td class="px-3 py-2 text-center">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?php echo e($item->jumlah); ?>

                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-6 text-gray-400">
                            <i class="bi bi-inbox text-3xl"></i>
                            <p class="mt-2 text-sm">Belum ada data</p>
                        </div>
                    <?php endif; ?>
                    <div class="mt-3">
                        <a href="<?php echo e(route('mutasi.masuk.index')); ?>"
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                            <i class="bi bi-arrow-right mr-1"></i> Lihat Semua Mutasi Masuk
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if(in_array($user->role, ['Dinas', 'SuperAdmin'])): ?>
            <!-- Verifikasi - Only for Dinas/SuperAdmin -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="bi bi-clipboard-check mr-2 text-yellow-600"></i>
                    Verifikasi Berkas
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="<?php echo e(route('mutasi.keluar.index', ['status' => 'Menunggu Verifikasi Dinas'])); ?>"
                        class="flex items-center gap-4 p-4 bg-yellow-50 border-2 border-yellow-200 hover:border-yellow-400 rounded-xl transition">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-hourglass-split text-2xl text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Mutasi Keluar Menunggu</p>
                            <p class="text-sm text-gray-500"><?php echo e($statsKeluar['menunggu']); ?> perlu verifikasi</p>
                        </div>
                    </a>
                    <a href="<?php echo e(route('mutasi.masuk.index', ['status' => 'Menunggu Verifikasi Dinas'])); ?>"
                        class="flex items-center gap-4 p-4 bg-yellow-50 border-2 border-yellow-200 hover:border-yellow-400 rounded-xl transition">
                        <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="bi bi-hourglass-split text-2xl text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Mutasi Masuk Menunggu</p>
                            <p class="text-sm text-gray-500"><?php echo e($statsMasuk['menunggu']); ?> perlu verifikasi</p>
                        </div>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Info Card -->
        <div class="bg-gradient-to-r from-primary-50 to-blue-50 rounded-xl border border-primary-100 p-6">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-info-circle text-primary-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">Informasi</h3>
                    <p class="text-sm text-gray-600 mt-1">
                        SIMAK adalah Sistem Informasi Mutasi untuk mengelola perpindahan siswa antar sekolah di Kota Bogor.
                        Pastikan semua dokumen yang diupload lengkap dan jelas untuk mempercepat proses verifikasi.
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\dashboard\index-non-tele.blade.php ENDPATH**/ ?>