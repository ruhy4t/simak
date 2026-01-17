<<<<<<< HEAD


<?php $__env->startSection('title', 'Daftar Mutasi Keluar'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-8 animate-fade-in-up">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Mutasi Keluar</h1>
                <p class="text-slate-500 mt-1">Kelola data mutasi siswa keluar</p>
            </div>
            <a href="<?php echo e(route('mutasi.keluar.create')); ?>"
                class="inline-flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white font-medium px-5 py-2.5 rounded-xl shadow-lg shadow-sky-500/30 transition-all hover:-translate-y-0.5 group">
                <i class="bi bi-plus-lg group-hover:rotate-90 transition-transform"></i>
                Buat Mutasi Baru
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-1.5">
            <form method="GET" id="filterForm" class="flex flex-col sm:flex-row gap-2">
                <div class="relative flex-1">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" id="searchInput" value="<?php echo e(request('search')); ?>"
                        class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-sm text-slate-700 placeholder:text-slate-400"
                        placeholder="Cari nama siswa, sekolah...">
                </div>

                <div class="relative sm:w-64">
                    <i class="bi bi-filter absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <select name="status" id="statusFilter"
                        class="w-full pl-11 pr-8 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-sm text-slate-700 cursor-pointer appearance-none">
                        <option value="">Semua Status</option>
                        <option value="Menunggu Verifikasi Dinas" <?php echo e(request('status') == 'Menunggu Verifikasi Dinas' ? 'selected' : ''); ?>>Menunggu Verifikasi</option>
                        <option value="Perlu Perbaikan" <?php echo e(request('status') == 'Perlu Perbaikan' ? 'selected' : ''); ?>>Perlu
                            Perbaikan</option>
                        <option value="Disetujui Dinas" <?php echo e(request('status') == 'Disetujui Dinas' ? 'selected' : ''); ?>>
                            Disetujui Dinas</option>
                        <option value="Proses TTD" <?php echo e(request('status') == 'Proses TTD' ? 'selected' : ''); ?>>Proses TTD
                        </option>
                        <option value="Selesai" <?php echo e(request('status') == 'Selesai' ? 'selected' : ''); ?>>Selesai</option>
                    </select>
                </div>

                <button type="submit"
                    class="px-6 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl transition font-medium shadow-md">
                    Cari
                </button>

                <?php if(request('search') || request('status')): ?>
                    <a href="<?php echo e(route('mutasi.keluar.index')); ?>"
                        class="px-4 py-2.5 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 rounded-xl transition flex items-center justify-center"
                        title="Reset Filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <?php if(count($mutasiList) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Tanggal</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">ID
                                    Mutasi</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Siswa
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Sekolah Asal</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Sekolah Tujuan</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php $__currentLoopData = $mutasiList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mutasi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors group">
                                        <td class="px-6 py-4 whitespace-nowrap text-slate-600">
                                            <?php echo e(\Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->translatedFormat('d M Y')); ?>

                                        </td>
                                        <td class="px-6 py-4 text-slate-500 font-mono text-xs">
                                            #<?php echo e($mutasi->id_mutasi); ?>

                                        </td>
                                        <td class="px-6 py-4 font-medium text-slate-800">
                                            <?php echo e($mutasi->nama_murid); ?>

                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800 truncate max-w-[180px]"
                                                    title="<?php echo e($mutasi->nama_sekolah_asal); ?>"><?php echo e($mutasi->nama_sekolah_asal); ?></span>
                                                <span
                                                    class="text-xs text-slate-400 font-mono"><?php echo e($mutasi->npsn_sekolah_asal ?? '-'); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800 truncate max-w-[180px]"
                                                    title="<?php echo e($mutasi->nama_sekolah_tujuan); ?>"><?php echo e($mutasi->nama_sekolah_tujuan); ?></span>
                                                <span
                                                    class="text-xs text-slate-400 font-mono"><?php echo e($mutasi->npsn_sekolah_tujuan ?? '-'); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                                $statusMap = [
                                                    'Menunggu Verifikasi Dinas' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'ring' => 'ring-amber-600/20', 'icon' => 'bi-hourglass-split'],
                                                    'Proses' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'ring' => 'ring-blue-600/20', 'icon' => 'bi-arrow-repeat'],
                                                    'Proses TTD' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'ring' => 'ring-indigo-600/20', 'icon' => 'bi-pen'],
                                                    'Disetujui Dinas' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'ring' => 'ring-emerald-600/20', 'icon' => 'bi-check-circle'],
                                                    'Selesai' => ['bg' => 'bg-sky-100', 'text' => 'text-sky-800', 'ring' => 'ring-sky-600/20', 'icon' => 'bi-check-all'],
                                                    'Perlu Perbaikan' => ['bg' => 'bg-rose-100', 'text' => 'text-rose-800', 'ring' => 'ring-rose-600/20', 'icon' => 'bi-exclamation-circle'],
                                                    'Dibatalkan' => ['bg' => 'bg-slate-200', 'text' => 'text-slate-600', 'ring' => 'ring-slate-500/20', 'icon' => 'bi-x-circle'],
                                                ];
                                                $defaultStatus = ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'ring' => 'ring-slate-600/20', 'icon' => 'bi-circle'];
                                                $statusConfig = $statusMap[$mutasi->status] ?? $defaultStatus;
                                            ?>
                                <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?php echo e($statusConfig['bg']); ?> <?php echo e($statusConfig['text']); ?> ring-1 ring-inset <?php echo e($statusConfig['ring']); ?>">
                                                <i class="bi <?php echo e($statusConfig['icon']); ?>"></i>
                                                <?php echo e($mutasi->status); ?>

                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="<?php echo e(route('mutasi.keluar.show', $mutasi->id_mutasi)); ?>"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-md shadow-blue-500/20 transition-all hover:-translate-y-0.5"
                                                    title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>

                                                <?php if($mutasi->status === 'Selesai'): ?>
                                                    <a href="<?php echo e(route('mutasi.keluar.print-letter', $mutasi->id_mutasi)); ?>" target="_blank"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-500/20 transition-all hover:-translate-y-0.5"
                                                        title="Cetak Surat">
                                                        <i class="bi bi-printer"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if(method_exists($mutasiList, 'links')): ?>
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                        <?php echo e($mutasiList->appends(request()->query())->links()); ?>

                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="p-16 text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full mx-auto mb-4 flex items-center justify-center">
                        <i class="bi bi-inbox text-3xl text-slate-300"></i>
                    </div>
                    <h3 class="text-lg font-medium text-slate-900 mb-1">Belum Ada Data</h3>
                    <p class="text-slate-500 mb-6">Belum ada data mutasi keluar yang sesuai dengan filter.</p>
                    <a href="<?php echo e(route('mutasi.keluar.create')); ?>"
                        class="inline-flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white font-medium px-5 py-2.5 rounded-xl transition shadow-lg shadow-sky-500/30 hover:-translate-y-0.5">
                        <i class="bi bi-plus-lg"></i>
                        Buat Mutasi Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const filterForm = document.getElementById('filterForm');
            const statusFilter = document.getElementById('statusFilter');
            let debounceTimer;

            // Auto-search when typing 3+ characters
            if (searchInput && filterForm) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    const query = this.value.trim();

                    debounceTimer = setTimeout(function () {
                        if (query.length >= 3 || query.length === 0) {
                            filterForm.submit();
                        }
                    }, 500);
                });
            }

            // Auto-submit when status filter changes
            if (statusFilter && filterForm) {
                statusFilter.addEventListener('change', function () {
                    filterForm.submit();
                });
            }
        });
    </script>
=======


<?php $__env->startSection('title', 'Daftar Mutasi Keluar'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-8 animate-fade-in-up">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Mutasi Keluar</h1>
                <p class="text-slate-500 mt-1">Kelola data mutasi siswa keluar</p>
            </div>
            <a href="<?php echo e(route('mutasi.keluar.create')); ?>"
                class="inline-flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white font-medium px-5 py-2.5 rounded-xl shadow-lg shadow-sky-500/30 transition-all hover:-translate-y-0.5 group">
                <i class="bi bi-plus-lg group-hover:rotate-90 transition-transform"></i>
                Buat Mutasi Baru
            </a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-1.5">
            <form method="GET" id="filterForm" class="flex flex-col sm:flex-row gap-2">
                <div class="relative flex-1">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" id="searchInput" value="<?php echo e(request('search')); ?>"
                        class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-sm text-slate-700 placeholder:text-slate-400"
                        placeholder="Cari nama siswa, sekolah...">
                </div>

                <div class="relative sm:w-64">
                    <i class="bi bi-filter absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <select name="status" id="statusFilter"
                        class="w-full pl-11 pr-8 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-sm text-slate-700 cursor-pointer appearance-none">
                        <option value="">Semua Status</option>
                        <option value="Menunggu Verifikasi Dinas" <?php echo e(request('status') == 'Menunggu Verifikasi Dinas' ? 'selected' : ''); ?>>Menunggu Verifikasi</option>
                        <option value="Perlu Perbaikan" <?php echo e(request('status') == 'Perlu Perbaikan' ? 'selected' : ''); ?>>Perlu
                            Perbaikan</option>
                        <option value="Disetujui Dinas" <?php echo e(request('status') == 'Disetujui Dinas' ? 'selected' : ''); ?>>
                            Disetujui Dinas</option>
                        <option value="Proses TTD" <?php echo e(request('status') == 'Proses TTD' ? 'selected' : ''); ?>>Proses TTD
                        </option>
                        <option value="Selesai" <?php echo e(request('status') == 'Selesai' ? 'selected' : ''); ?>>Selesai</option>
                    </select>
                </div>

                <button type="submit"
                    class="px-6 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl transition font-medium shadow-md">
                    Cari
                </button>

                <?php if(request('search') || request('status')): ?>
                    <a href="<?php echo e(route('mutasi.keluar.index')); ?>"
                        class="px-4 py-2.5 bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 rounded-xl transition flex items-center justify-center"
                        title="Reset Filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <?php if(count($mutasiList) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Tanggal</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">ID
                                    Mutasi</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Siswa
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Sekolah Asal</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Sekolah Tujuan</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php $__currentLoopData = $mutasiList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mutasi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors group">
                                        <td class="px-6 py-4 whitespace-nowrap text-slate-600">
                                            <?php echo e(\Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->translatedFormat('d M Y')); ?>

                                        </td>
                                        <td class="px-6 py-4 text-slate-500 font-mono text-xs">
                                            #<?php echo e($mutasi->id_mutasi); ?>

                                        </td>
                                        <td class="px-6 py-4 font-medium text-slate-800">
                                            <?php echo e($mutasi->nama_murid); ?>

                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800 truncate max-w-[180px]"
                                                    title="<?php echo e($mutasi->nama_sekolah_asal); ?>"><?php echo e($mutasi->nama_sekolah_asal); ?></span>
                                                <span
                                                    class="text-xs text-slate-400 font-mono"><?php echo e($mutasi->npsn_sekolah_asal ?? '-'); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col">
                                                <span class="font-bold text-slate-800 truncate max-w-[180px]"
                                                    title="<?php echo e($mutasi->nama_sekolah_tujuan); ?>"><?php echo e($mutasi->nama_sekolah_tujuan); ?></span>
                                                <span
                                                    class="text-xs text-slate-400 font-mono"><?php echo e($mutasi->npsn_sekolah_tujuan ?? '-'); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                                $statusMap = [
                                                    'Menunggu Verifikasi Dinas' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'ring' => 'ring-amber-600/20', 'icon' => 'bi-hourglass-split'],
                                                    'Proses' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'ring' => 'ring-blue-600/20', 'icon' => 'bi-arrow-repeat'],
                                                    'Proses TTD' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'ring' => 'ring-indigo-600/20', 'icon' => 'bi-pen'],
                                                    'Disetujui Dinas' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'ring' => 'ring-emerald-600/20', 'icon' => 'bi-check-circle'],
                                                    'Selesai' => ['bg' => 'bg-sky-100', 'text' => 'text-sky-800', 'ring' => 'ring-sky-600/20', 'icon' => 'bi-check-all'],
                                                    'Perlu Perbaikan' => ['bg' => 'bg-rose-100', 'text' => 'text-rose-800', 'ring' => 'ring-rose-600/20', 'icon' => 'bi-exclamation-circle'],
                                                    'Dibatalkan' => ['bg' => 'bg-slate-200', 'text' => 'text-slate-600', 'ring' => 'ring-slate-500/20', 'icon' => 'bi-x-circle'],
                                                ];
                                                $defaultStatus = ['bg' => 'bg-slate-100', 'text' => 'text-slate-700', 'ring' => 'ring-slate-600/20', 'icon' => 'bi-circle'];
                                                $statusConfig = $statusMap[$mutasi->status] ?? $defaultStatus;
                                            ?>
                                <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?php echo e($statusConfig['bg']); ?> <?php echo e($statusConfig['text']); ?> ring-1 ring-inset <?php echo e($statusConfig['ring']); ?>">
                                                <i class="bi <?php echo e($statusConfig['icon']); ?>"></i>
                                                <?php echo e($mutasi->status); ?>

                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="<?php echo e(route('mutasi.keluar.show', $mutasi->id_mutasi)); ?>"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-md shadow-blue-500/20 transition-all hover:-translate-y-0.5"
                                                    title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>

                                                <?php if($mutasi->status === 'Selesai'): ?>
                                                    <a href="<?php echo e(route('mutasi.keluar.print-letter', $mutasi->id_mutasi)); ?>" target="_blank"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-500/20 transition-all hover:-translate-y-0.5"
                                                        title="Cetak Surat">
                                                        <i class="bi bi-printer"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if(method_exists($mutasiList, 'links')): ?>
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                        <?php echo e($mutasiList->appends(request()->query())->links()); ?>

                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="p-16 text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full mx-auto mb-4 flex items-center justify-center">
                        <i class="bi bi-inbox text-3xl text-slate-300"></i>
                    </div>
                    <h3 class="text-lg font-medium text-slate-900 mb-1">Belum Ada Data</h3>
                    <p class="text-slate-500 mb-6">Belum ada data mutasi keluar yang sesuai dengan filter.</p>
                    <a href="<?php echo e(route('mutasi.keluar.create')); ?>"
                        class="inline-flex items-center gap-2 bg-sky-500 hover:bg-sky-600 text-white font-medium px-5 py-2.5 rounded-xl transition shadow-lg shadow-sky-500/30 hover:-translate-y-0.5">
                        <i class="bi bi-plus-lg"></i>
                        Buat Mutasi Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const filterForm = document.getElementById('filterForm');
            const statusFilter = document.getElementById('statusFilter');
            let debounceTimer;

            // Auto-search when typing 3+ characters
            if (searchInput && filterForm) {
                searchInput.addEventListener('input', function () {
                    clearTimeout(debounceTimer);
                    const query = this.value.trim();

                    debounceTimer = setTimeout(function () {
                        if (query.length >= 3 || query.length === 0) {
                            filterForm.submit();
                        }
                    }, 500);
                });
            }

            // Auto-submit when status filter changes
            if (statusFilter && filterForm) {
                statusFilter.addEventListener('change', function () {
                    filterForm.submit();
                });
            }
        });
    </script>
>>>>>>> 9466a07 (Upload project pertama kali)
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\mutasi\keluar\index.blade.php ENDPATH**/ ?>