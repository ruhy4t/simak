

<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-8 animate-fade-in-up">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Dashboard Overview</h1>
                <p class="text-slate-500 mt-1">Selamat datang kembali, <span class="font-semibold text-sky-600"><?php echo e($user->nama_pengguna ?? $user->username); ?></span>!</p>
            </div>
            <div class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-xl shadow-sm text-sm text-slate-500">
                <i class="bi bi-calendar3 mr-2 text-slate-400"></i>
                <?php echo e(now()->translatedFormat('l, j F Y')); ?>

            </div>
        </div>

        <!-- Mutasi Masuk Stats -->
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-slate-800 flex items-center gap-2 px-1">
                <i class="bi bi-box-arrow-in-right text-sky-500"></i>
                Statistik Mutasi Masuk
            </h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-4">
                
                <?php $__currentLoopData = [
                    ['label' => 'Total', 'value' => $statsMasuk['total'], 'icon' => 'bi-archive', 'color' => 'slate'],
                    ['label' => 'Siswa', 'value' => $statsMasuk['total_siswa'], 'icon' => 'bi-people', 'color' => 'indigo'],
                    ['label' => 'Menunggu', 'value' => $statsMasuk['menunggu'], 'icon' => 'bi-hourglass-split', 'color' => 'amber'],
                    ['label' => 'Perbaikan', 'value' => $statsMasuk['perbaikan'], 'icon' => 'bi-exclamation-triangle', 'color' => 'rose'],
                    ['label' => 'Disetujui', 'value' => $statsMasuk['disetujui'], 'icon' => 'bi-check-circle', 'color' => 'emerald'],
                    ['label' => 'Proses TTD', 'value' => $statsMasuk['proses_ttd'] ?? 0, 'icon' => 'bi-pen', 'color' => 'violet'],
                    ['label' => 'Selesai', 'value' => $statsMasuk['selesai'], 'icon' => 'bi-check-all', 'color' => 'sky']
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:shadow-lg hover:-translate-y-1 transition-all duration-300 group">
                    <div class="flex flex-col h-full justify-between gap-3">
                        <div class="w-10 h-10 rounded-xl bg-<?php echo e($stat['color']); ?>-50 text-<?php echo e($stat['color']); ?>-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="bi <?php echo e($stat['icon']); ?> text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-medium uppercase tracking-wider"><?php echo e($stat['label']); ?></p>
                            <p class="text-2xl font-bold text-slate-800 mt-1"><?php echo e(number_format($stat['value'])); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <!-- Mutasi Keluar Stats -->
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-slate-800 flex items-center gap-2 px-1">
                <i class="bi bi-box-arrow-right text-orange-500"></i>
                Statistik Mutasi Keluar
            </h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-4">
                 <?php $__currentLoopData = [
                    ['label' => 'Total', 'value' => $statsKeluar['total'], 'icon' => 'bi-archive', 'color' => 'slate'],
                    ['label' => 'Menunggu', 'value' => $statsKeluar['menunggu'], 'icon' => 'bi-hourglass-split', 'color' => 'amber'],
                    ['label' => 'Perbaikan', 'value' => $statsKeluar['perbaikan'], 'icon' => 'bi-exclamation-triangle', 'color' => 'rose'],
                    ['label' => 'Disetujui', 'value' => $statsKeluar['disetujui'], 'icon' => 'bi-check-circle', 'color' => 'emerald'],
                    ['label' => 'Proses TTD', 'value' => $statsKeluar['proses_ttd'] ?? 0, 'icon' => 'bi-pen', 'color' => 'violet'],
                    ['label' => 'Selesai', 'value' => $statsKeluar['selesai'], 'icon' => 'bi-check-all', 'color' => 'sky']
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] hover:shadow-lg hover:-translate-y-1 transition-all duration-300 group">
                    <div class="flex flex-col h-full justify-between gap-3">
                        <div class="w-10 h-10 rounded-xl bg-<?php echo e($stat['color']); ?>-50 text-<?php echo e($stat['color']); ?>-500 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="bi <?php echo e($stat['icon']); ?> text-lg"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-medium uppercase tracking-wider"><?php echo e($stat['label']); ?></p>
                            <p class="text-2xl font-bold text-slate-800 mt-1"><?php echo e(number_format($stat['value'])); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  
                 <div class="hidden xl:block bg-transparent"></div>
            </div>
        </div>

        <!-- User Stats (Sekolah / Dinas) -->
        <?php if($statsUsers): ?>
            <div class="space-y-4">
                 <h2 class="text-lg font-semibold text-slate-800 flex items-center gap-2 px-1">
                    <i class="bi bi-people text-purple-500"></i>
                    Statistik Pengguna
                </h2>
                
                <?php if($user->role === 'Sekolah'): ?>
                    <!-- Sekolah: Stats Operator saja -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php $__currentLoopData = [
                            ['label' => 'Total Operator', 'value' => $statsUsers['total'], 'color' => 'purple', 'bg' => 'purple'],
                            ['label' => 'Aktif', 'value' => $statsUsers['active'], 'color' => 'emerald', 'bg' => 'emerald'],
                            ['label' => 'Non-Aktif', 'value' => $statsUsers['inactive'], 'color' => 'rose', 'bg' => 'rose']
                        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $uStat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                         <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm flex items-center justify-between group hover:shadow-md transition">
                            <div>
                                <p class="text-sm text-slate-500 font-medium"><?php echo e($uStat['label']); ?></p>
                                <p class="text-3xl font-bold text-slate-800 mt-2"><?php echo e(number_format($uStat['value'])); ?></p>
                            </div>
                            <div class="w-12 h-12 rounded-full bg-<?php echo e($uStat['bg']); ?>-50 text-<?php echo e($uStat['color']); ?>-600 flex items-center justify-center text-xl">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div class="flex justify-end">
                        <a href="<?php echo e(route('users.manage')); ?>"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-600 hover:text-sky-600 hover:border-sky-200 hover:bg-sky-50 transition-colors shadow-sm">
                            Kelola Pengguna <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Dinas: Stats Sekolah & Operator -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Role Sekolah -->
                        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <span class="font-bold text-lg text-slate-800">Sekolah</span>
                                </div>
                                <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-xs font-bold"><?php echo e(number_format($statsUsers['sekolah_total'] ?? 0)); ?> Total</span>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm items-center">
                                    <span class="text-slate-500">Aktif</span>
                                    <span class="font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded"><?php echo e($statsUsers['sekolah_active'] ?? 0); ?></span>
                                </div>
                                <div class="flex justify-between text-sm items-center">
                                     <span class="text-slate-500">Non-Aktif</span>
                                    <span class="font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded"><?php echo e($statsUsers['sekolah_inactive'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>

                         <!-- Role Operator -->
                        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                    <span class="font-bold text-lg text-slate-800">Operator</span>
                                </div>
                                <span class="bg-orange-50 text-orange-700 px-3 py-1 rounded-full text-xs font-bold"><?php echo e(number_format($statsUsers['operator_total'] ?? 0)); ?> Total</span>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm items-center">
                                    <span class="text-slate-500">Aktif</span>
                                    <span class="font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded"><?php echo e($statsUsers['operator_active'] ?? 0); ?></span>
                                </div>
                                <div class="flex justify-between text-sm items-center">
                                     <span class="text-slate-500">Non-Aktif</span>
                                    <span class="font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded"><?php echo e($statsUsers['operator_inactive'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <a href="<?php echo e(route('dinas.users')); ?>"
                             class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white border border-slate-200 text-sm font-medium text-slate-600 hover:text-sky-600 hover:border-sky-200 hover:bg-sky-50 transition-colors shadow-sm">
                            Kelola Semua Pengguna <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Statistik Mutasi per Sekolah -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 overflow-hidden relative">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-sky-400 to-blue-500"></div>
            
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8">
                <div>
                     <h2 class="text-lg font-bold text-slate-800">Statistik Mutasi Per Sekolah</h2>
                     <p class="text-sm text-slate-500 mt-1">Data perpindahan siswa berdasarkan sekolah</p>
                </div>

                <!-- Filter Form -->
                <form method="GET" action="<?php echo e(route('dashboard')); ?>" class="flex flex-wrap gap-2 items-center bg-slate-50 p-1.5 rounded-xl border border-slate-200">
                    <select name="month"
                        class="text-sm bg-white border-0 rounded-lg px-3 py-2 text-slate-600 focus:ring-2 focus:ring-sky-100 placeholder:text-slate-400 shadow-sm cursor-pointer hover:bg-slate-50 transition">
                        <option value="">Semua Bulan</option>
                        <?php
                            $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                        ?>
                        <?php $__currentLoopData = $months; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($idx + 1); ?>" <?php echo e($filterMonth == ($idx + 1) ? 'selected' : ''); ?>><?php echo e($m); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <select name="year"
                        class="text-sm bg-white border-0 rounded-lg px-3 py-2 text-slate-600 focus:ring-2 focus:ring-sky-100 placeholder:text-slate-400 shadow-sm cursor-pointer hover:bg-slate-50 transition">
                        <option value="">Semua Tahun</option>
                        <?php $__currentLoopData = $availableYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($y); ?>" <?php echo e($filterYear == $y ? 'selected' : ''); ?>><?php echo e($y); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <button type="submit"
                        class="text-sm bg-sky-500 text-white px-4 py-2 rounded-lg font-medium hover:bg-sky-600 transition shadow-sm shadow-sky-200">
                        Filter
                    </button>
                    <?php if($filterMonth || $filterYear): ?>
                        <a href="<?php echo e(route('dashboard')); ?>" class="p-2 text-slate-400 hover:text-rose-500 transition" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise text-lg"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

             <?php if($filterMonth || $filterYear): ?>
                <div class="mb-6 inline-flex items-center gap-2 text-sm text-sky-600 bg-sky-50 px-4 py-2 rounded-full border border-sky-100">
                    <i class="bi bi-calendar-check"></i>
                    <span>
                        Filter Aktif: 
                        <span class="font-semibold">
                            <?php echo e($filterMonth ? $months[$filterMonth - 1] : 'Semua Bulan'); ?>

                            <?php echo e($filterYear ? $filterYear : 'Semua Tahun'); ?>

                        </span>
                    </span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                <!-- Tabel Mutasi Masuk (KIRI) -->
                <div>
                    <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center justify-between">
                         <span class="flex items-center gap-2"><i class="bi bi-box-arrow-in-right text-sky-500"></i> Mutasi Masuk</span>
                         <span class="text-xs font-normal text-slate-400">Top 10</span>
                    </h3>
                    <?php if(count($schoolStatsMasuk) > 0): ?>
                        <div class="overflow-hidden rounded-xl border border-slate-100 shadow-sm">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider w-12">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Sekolah Tujuan</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider w-24">Siswa</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php $__currentLoopData = $schoolStatsMasuk; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 text-slate-400 font-mono"><?php echo e($idx + 1); ?></td>
                                            <td class="px-4 py-3 text-slate-700 font-medium"><?php echo e($item->nama_sekolah ?? '-'); ?></td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-sky-50 text-sky-600 font-bold text-xs ring-1 ring-sky-100">
                                                    <?php echo e($item->jumlah); ?>

                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <i class="bi bi-inbox text-slate-300 text-4xl"></i>
                            <p class="mt-2 text-sm text-slate-500">Belum ada data</p>
                        </div>
                    <?php endif; ?>
                    <div class="mt-4 text-center">
                        <a href="<?php echo e(route('mutasi.masuk.index')); ?>"
                            class="text-sm text-slate-500 hover:text-sky-600 font-medium transition inline-flex items-center gap-1 group">
                             Lihat Selengkapnya <i class="bi bi-chevron-right text-xs group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>

                <!-- Tabel Mutasi Keluar (KANAN) -->
                <div>
                     <h3 class="text-sm font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center justify-between">
                         <span class="flex items-center gap-2"><i class="bi bi-box-arrow-right text-orange-500"></i> Mutasi Keluar</span>
                         <span class="text-xs font-normal text-slate-400">Top 10</span>
                    </h3>
                    <?php if(count($schoolStatsKeluar) > 0): ?>
                        <div class="overflow-hidden rounded-xl border border-slate-100 shadow-sm">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider w-12">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Sekolah Asal</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider w-24">Siswa</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php $__currentLoopData = $schoolStatsKeluar; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="px-4 py-3 text-slate-400 font-mono"><?php echo e($idx + 1); ?></td>
                                            <td class="px-4 py-3 text-slate-700 font-medium"><?php echo e($item->nama_sekolah ?? '-'); ?></td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-50 text-orange-600 font-bold text-xs ring-1 ring-orange-100">
                                                    <?php echo e($item->jumlah); ?>

                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <i class="bi bi-inbox text-slate-300 text-4xl"></i>
                            <p class="mt-2 text-sm text-slate-500">Belum ada data</p>
                        </div>
                    <?php endif; ?>
                     <div class="mt-4 text-center">
                        <a href="<?php echo e(route('mutasi.keluar.index')); ?>"
                            class="text-sm text-slate-500 hover:text-sky-600 font-medium transition inline-flex items-center gap-1 group">
                             Lihat Selengkapnya <i class="bi bi-chevron-right text-xs group-hover:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if(in_array($user->role, ['Dinas', 'SuperAdmin'])): ?>
             <!-- Verifikasi -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 <?php
                    $verifyStats = [
                        [
                            'title' => 'Mutasi Masuk', 
                            'count' => $statsMasuk['menunggu'], 
                            'route' => 'mutasi.masuk.index', 
                            'icon' => 'bi-box-arrow-in-right',
                            'color' => 'sky'
                        ],
                        [
                            'title' => 'Mutasi Keluar', 
                            'count' => $statsKeluar['menunggu'], 
                            'route' => 'mutasi.keluar.index', 
                            'icon' => 'bi-box-arrow-right',
                            'color' => 'orange'
                        ]
                    ];
                ?>

                <?php $__currentLoopData = $verifyStats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vStat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route($vStat['route'], ['status' => 'Menunggu Verifikasi Dinas'])); ?>"
                        class="group relative overflow-hidden bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-lg transition-all duration-300">
                        <div class="absolute right-0 top-0 w-32 h-32 bg-gradient-to-br from-<?php echo e($vStat['color']); ?>-100/50 to-transparent rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-150"></div>
                        
                        <div class="relative flex items-center gap-5">
                            <div class="w-14 h-14 rounded-2xl bg-<?php echo e($vStat['color']); ?>-50 text-<?php echo e($vStat['color']); ?>-500 flex items-center justify-center shadow-inner group-hover:rotate-12 transition-transform">
                                <i class="bi <?php echo e($vStat['icon']); ?> text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 font-medium uppercase tracking-wide">Menunggu Verifikasi</p>
                                <h3 class="text-xl font-bold text-slate-800"><?php echo e($vStat['title']); ?></h3>
                            </div>
                            <div class="ml-auto">
                                <span class="text-3xl font-bold text-<?php echo e($vStat['color']); ?>-600"><?php echo e($vStat['count']); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <!-- Footer Info -->
        <div class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-2xl p-8 text-white relative overflow-hidden shadow-xl">
             <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
            <div class="relative z-10 flex flex-col md:flex-row items-center gap-6 text-center md:text-left">
                <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-sm shadow-inner border border-white/10">
                    <i class="bi bi-info-circle text-3xl text-sky-400"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-white mb-2">Pusat Informasi SIMAK</h3>
                    <p class="text-slate-300 leading-relaxed max-w-2xl">
                        Pastikan seluruh dokumen persyaratan mutasi telah diunggah dengan format yang benar (PDF/JPG) agar proses verifikasi dapat berjalan lancar tanpa hambatan.
                    </p>
                </div>
                
            </div>
        </div>
    </div>

    
    <?php if(empty($user->telegram_chat_id) && in_array($user->role, ['Operator', 'Sekolah', 'Dinas', 'SuperAdmin'])): ?>
        <div x-data="{ showTelegramPopup: true }" x-show="showTelegramPopup" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
            <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden scale-100"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95">

                
                <div class="bg-slate-900 px-6 py-6 text-center relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-blue-600/20 to-purple-600/20"></div>
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-white/10 mx-auto rounded-2xl flex items-center justify-center backdrop-blur-md mb-4 border border-white/10">
                            <i class="bi bi-telegram text-sky-400 text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Aktifkan Notifikasi</h3>
                        <p class="text-slate-400 text-sm mt-1">Dapatkan update status realtime via Telegram</p>
                    </div>
                </div>

                
                <div class="px-8 py-6 space-y-6">
                    <div class="space-y-4">
                        <div class="flex gap-4">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center mt-0.5">1</span>
                            <div class="text-sm text-slate-600">
                                Cari bot <span class="font-mono font-bold text-slate-800 bg-slate-100 px-1 rounded">@userinfobot</span> di Telegram
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center mt-0.5">2</span>
                            <div class="text-sm text-slate-600">
                                Start bot untuk mendapatkan <strong>ID Telegram</strong> Anda.
                            </div>
                        </div>
                         <div class="flex gap-4">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center mt-0.5">3</span>
                            <div class="text-sm text-slate-600">
                                Masukkan ID di Profil SIMAK, lalu chat <span class="font-mono font-bold text-sky-600">@simak_smp_bot</span>.
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button @click="showTelegramPopup = false"
                            class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-medium hover:bg-slate-50 transition">
                            Nanti Saja
                        </button>
                        <a href="https://t.me/simak_smp_bot" target="_blank"
                            class="flex-1 px-4 py-2.5 rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-medium text-center shadow-lg shadow-sky-500/30 transition hover:-translate-y-0.5">
                            Buka Telegram
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <style>
        .animate-fade-in-up { animation: fadeInUp 0.5s ease-out; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\dashboard\index.blade.php ENDPATH**/ ?>