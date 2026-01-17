

<?php $__env->startSection('title', 'Daftar Mutasi Masuk'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-8 animate-fade-in-up">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Mutasi Masuk</h1>
                <p class="text-slate-500 mt-1">Daftar pengajuan siswa pindahan masuk ke sekolah</p>
            </div>
            <?php if(in_array(Auth::user()->role, ['Sekolah', 'Operator', 'Dinas', 'SuperAdmin'])): ?>
                <a href="<?php echo e(route('mutasi.masuk.create')); ?>"
                    class="inline-flex items-center justify-center px-4 py-2.5 bg-sky-500 hover:bg-sky-600 text-white rounded-xl shadow-lg shadow-sky-500/30 transition-all hover:-translate-y-0.5 font-medium group">
                    <i class="bi bi-plus-lg mr-2 group-hover:rotate-90 transition-transform"></i>
                    Buat Mutasi Masuk
                </a>
            <?php endif; ?>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-1.5">
            <form action="<?php echo e(route('mutasi.masuk.index')); ?>" method="GET" id="filterForm"
                class="flex flex-col sm:flex-row gap-2">
                <div class="relative flex-1">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" id="searchInput" value="<?php echo e(request('search')); ?>"
                        class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-sm text-slate-700 placeholder:text-slate-400"
                        placeholder="Cari nomor surat, nama sekolah...">
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
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden" x-data="{ 
                     showModal: false, 
                     modalTitle: '', 
                     siswaList: []
                 }">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <?php if(in_array(Auth::user()->role, ['Dinas', 'SuperAdmin'])): ?>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Tanggal</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">ID
                                    Mutasi</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Sekolah Tujuan</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Sekolah Asal</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Jumlah Siswa</th>
                            <?php else: ?>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">ID
                                    Mutasi</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Tanggal</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nomor
                                    Surat</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    Sekolah Asal/Tujuan</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Siswa
                                </th>
                            <?php endif; ?>

                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Status
                            </th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php $__empty_1 = true; $__currentLoopData = $mutasiList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mutasi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-slate-50/80 transition-colors group">

                                <?php if(in_array(Auth::user()->role, ['Dinas', 'SuperAdmin'])): ?>
                                                    
                                                    <td class="px-6 py-4 text-sm text-slate-600">
                                                        <?php echo e(\Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->translatedFormat('d M Y')); ?>

                                                    </td>
                                                    <td class="px-6 py-4 text-sm font-mono text-slate-500">
                                                        #<?php echo e($mutasi->id_mutasi); ?>

                                                    </td>
                                                    <td class="px-6 py-4 text-sm">
                                                        <div class="flex flex-col">
                                                            <span class="font-bold text-slate-800"><?php echo e($mutasi->nama_sekolah_tujuan); ?></span>
                                                            <span class="text-xs text-slate-400 font-mono"><?php echo e($mutasi->npsn_sekolah_tujuan); ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-slate-600">
                                                        <?php
                                                            $uniqueSchools = $mutasi->siswa->unique('nama_sekolah_asal');
                                                            $count = $uniqueSchools->count();
                                                            $first = $uniqueSchools->first();
                                                        ?>
                                                        <?php if($count === 1): ?>
                                                            <span class="block truncate max-w-[200px]"
                                                                title="<?php echo e($first->nama_sekolah_asal); ?>"><?php echo e($first->nama_sekolah_asal); ?></span>
                                                        <?php else: ?>
                                                            <span class="italic text-slate-500">Gabungan <?php echo e($count); ?> Sekolah</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-6 py-4 text-center">
                                                        <button @click="
                                                                            showModal = true; 
                                                                            modalTitle = '#<?php echo e($mutasi->id_mutasi); ?>'; 
                                                                            siswaList = <?php echo e(json_encode($mutasi->siswa->map(fn($s) => [
                                        'nama' => $s->nama_murid,
                                        'nisn' => $s->nisn,
                                        'asal' => $s->nama_sekolah_asal
                                    ]))); ?>

                                                                        "
                                                            class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-bold bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white transition-all ring-1 ring-inset ring-indigo-600/10 hover:ring-indigo-600">
                                                            <i class="bi bi-people-fill"></i>
                                                            <?php echo e($mutasi->jumlah_siswa); ?>

                                                        </button>
                                                    </td>
                                <?php else: ?>
                                    
                                    <td class="px-6 py-4 text-sm font-mono text-slate-500">
                                        #<?php echo e($mutasi->id_mutasi); ?>

                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <?php echo e(\Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->translatedFormat('d M Y')); ?>

                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-slate-800">
                                        <?php echo e($mutasi->nomor_surat); ?>

                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <div class="flex flex-col">
                                            <span class="text-xs text-slate-400">Dari:</span>
                                            <span class="font-medium truncate max-w-[200px]"
                                                title="<?php echo e($mutasi->nama_sekolah_asal_text ?? '-'); ?>"><?php echo e($mutasi->nama_sekolah_asal_text ?? 'Luar Kota'); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/10">
                                            <i class="bi bi-people-fill"></i> <?php echo e($mutasi->jumlah_siswa); ?>

                                        </span>
                                    </td>
                                <?php endif; ?>

                                <td class="px-6 py-4">
                                    <?php
                                        $statusMap = [
                                            'Menunggu Verifikasi Dinas' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-800', 'ring' => 'ring-amber-600/20', 'icon' => 'bi-hourglass-split'],
                                            'Disetujui Dinas' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'ring' => 'ring-emerald-600/20', 'icon' => 'bi-check-circle'],
                                            'Proses TTD' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-800', 'ring' => 'ring-indigo-600/20', 'icon' => 'bi-pen'],
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
                                        <a href="<?php echo e(route('mutasi.masuk.show', $mutasi->id_mutasi)); ?>"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-md shadow-blue-500/20 transition-all hover:-translate-y-0.5"
                                            title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <?php if($mutasi->status === 'Selesai'): ?>
                                            <a href="<?php echo e(route('mutasi.masuk.print-letter', $mutasi->id_mutasi)); ?>" target="_blank"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-500/20 transition-all hover:-translate-y-0.5"
                                                title="Cetak Surat">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                            <i class="bi bi-inbox text-3xl text-slate-300"></i>
                                        </div>
                                        <h3 class="text-slate-900 font-medium">Belum ada data</h3>
                                        <p class="text-slate-500 text-sm mt-1">Belum ada pengajuan mutasi masuk yang ditemukan.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if(method_exists($mutasiList, 'links')): ?>
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    <?php echo e($mutasiList->links()); ?>

                </div>
            <?php endif; ?>

            
            <div x-show="showModal" style="display: none;"
                class="fixed inset-0 z-[60] flex items-center justify-center px-4"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

                
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>

                
                <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[80vh] flex flex-col overflow-hidden"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                    
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <i class="bi bi-people-fill text-indigo-600 text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-900">Daftar Siswa Pindah</h3>
                                <p class="text-sm text-slate-500" x-text="'ID: ' + modalTitle"></p>
                            </div>
                        </div>
                        <button @click="showModal = false"
                            class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition">
                            <i class="bi bi-x-lg text-lg"></i>
                        </button>
                    </div>

                    
                    <div class="p-6 overflow-y-auto" style="max-height: 50vh;">
                        <template x-if="siswaList.length === 0">
                            <div class="text-center py-8 text-slate-500">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-slate-100 flex items-center justify-center">
                                    <i class="bi bi-person-x text-2xl text-slate-400"></i>
                                </div>
                                Tidak ada data siswa.
                            </div>
                        </template>

                        <div class="space-y-3">
                            <template x-for="(siswa, index) in siswaList" :key="index">
                                <div class="flex items-start gap-4 p-4 rounded-xl border border-slate-200 hover:border-indigo-300 hover:shadow-lg transition-all duration-200" style="background: linear-gradient(to right, #f8fafc, #ffffff);">
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm" style="background: linear-gradient(135deg, #6366f1, #a855f7); box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);"
                                        x-text="index + 1"></div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-slate-900 text-base" x-text="siswa.nama"></h4>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono" style="background: #eef2ff; color: #4338ca; border: 1px solid rgba(99, 102, 241, 0.2);">
                                                <i class="bi bi-upc-scan mr-1.5" style="opacity: 0.7;"></i>
                                                <span x-text="siswa.nisn"></span>
                                            </span>
                                        </div>
                                        <div class="mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                                            <i class="bi bi-building" style="opacity: 0.7;"></i>
                                            <span class="truncate" x-text="siswa.asal || 'Sekolah tidak terdata'"></span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    
                    <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end">
                        <button @click="showModal = false"
                            class="px-6 py-2.5 bg-slate-900 text-white font-medium rounded-xl hover:bg-slate-800 transition shadow-lg">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
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

        /* Custom Scrollbar Styles */
        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #94a3b8, #64748b);
            border-radius: 4px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #64748b, #475569);
        }

        /* Firefox scrollbar */
        .scrollbar-thin {
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #f1f5f9;
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
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\mutasi\masuk\index.blade.php ENDPATH**/ ?>