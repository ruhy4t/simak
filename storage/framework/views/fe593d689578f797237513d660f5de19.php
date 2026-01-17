

<?php $__env->startSection('title', 'Laporan Mutasi'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan Mutasi</h1>
            <p class="text-gray-500 mt-1">Pilih jenis laporan yang ingin Anda cetak atau lihat.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Card Laporan Rekapitulasi -->
            <a href="<?php echo e(route('laporan.rekap')); ?>"
                class="group relative bg-white rounded-xl shadow-sm border p-6 overflow-hidden transition hover:shadow-md hover:border-blue-300">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                    <i class="bi bi-bar-chart-fill text-9xl text-blue-600"></i>
                </div>

                <div class="relative z-10 flex flex-col h-full">
                    <div
                        class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4 text-blue-600 group-hover:scale-110 transition">
                        <i class="bi bi-bar-chart-line text-2xl"></i>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-2">Laporan Rekapitulasi</h3>
                    <p class="text-gray-500 text-sm mb-6 flex-1">
                        Ringkasan statistik jumlah mutasi masuk dan keluar.
                        <?php if(Auth::user()->role === 'Dinas' || Auth::user()->role === 'SuperAdmin'): ?>
                            Dilengkapi dengan data per sekolah.
                        <?php else: ?>
                            Dilengkapi dengan data per bulan.
                        <?php endif; ?>
                    </p>

                    <div class="flex items-center text-blue-600 font-medium text-sm group-hover:translate-x-1 transition">
                        Buka Laporan <i class="bi bi-arrow-right ml-2"></i>
                    </div>
                </div>
            </a>

            <!-- Card Laporan Detail (Coming Soon or Implementation) -->
            <a href="#"
                class="group relative bg-white rounded-xl shadow-sm border p-6 overflow-hidden transition hover:shadow-md hover:border-orange-300 opacity-60 cursor-not-allowed">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition">
                    <i class="bi bi-card-list text-9xl text-orange-600"></i>
                </div>

                <div class="relative z-10 flex flex-col h-full">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4 text-orange-600">
                        <i class="bi bi-table text-2xl"></i>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 mb-2">Laporan Detail (Daftar Nominatif)</h3>
                    <p class="text-gray-500 text-sm mb-6 flex-1">
                        Daftar lengkap siswa yang melakukan mutasi dengan detail NISN, Nama, dan Sekolah Tujuan/Asal.
                    </p>

                    <div class="flex items-center text-orange-600 font-medium text-sm">
                        Segera Hadir <i class="bi bi-clock-history ml-2"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\laporan\index.blade.php ENDPATH**/ ?>