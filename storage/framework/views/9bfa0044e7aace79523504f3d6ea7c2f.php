

<?php $__env->startSection('title', 'Laporan Rekapitulasi'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-6">
        <!-- Header with Back Button -->
        <div class="flex items-center gap-4">
            <a href="<?php echo e(route('laporan.index')); ?>" class="p-2 hover:bg-gray-100 rounded-lg transition text-gray-600">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Laporan Rekapitulasi</h1>
                <p class="text-gray-500 mt-1">Statistik mutasi periode
                    <?php echo e(\Carbon\Carbon::parse($start_date)->translatedFormat('d F Y')); ?> -
                    <?php echo e(\Carbon\Carbon::parse($end_date)->translatedFormat('d F Y')); ?>

                </p>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <form action="<?php echo e(route('laporan.rekap')); ?>" method="GET"
                class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="<?php echo e($start_date); ?>"
                        class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="<?php echo e($end_date); ?>"
                        class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex-1 flex items-center justify-center gap-2">
                        <i class="bi bi-filter"></i> Terapkan
                    </button>

                    <!-- Print Button (opens in new tab) -->
                    <a href="<?php echo e(route('laporan.rekap.print', ['start_date' => $start_date, 'end_date' => $end_date])); ?>"
                        target="_blank"
                        class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex-1 flex items-center justify-center gap-2">
                        <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
                    </a>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="p-6 border-b flex justify-between items-center">
                <h3 class="font-semibold text-gray-900">Preview Data</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 border-b text-gray-600 font-medium">
                        <tr>
                            <th class="px-6 py-3 w-16 text-center">No</th>
                            <?php if(Auth::user()->role === 'Sekolah'): ?>
                                <th class="px-6 py-3">Periode</th>
                            <?php else: ?>
                                <th class="px-6 py-3">Nama Sekolah</th>
                            <?php endif; ?>
                            <th class="px-6 py-3 text-center">Mutasi Masuk</th>
                            <th class="px-6 py-3 text-center">Mutasi Keluar</th>
                            <?php if(Auth::user()->role !== 'Sekolah'): ?>
                                <th class="px-6 py-3 text-center">Jumlah</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php $__empty_1 = true; $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-center text-gray-500"><?php echo e($index + 1); ?></td>
                                <?php if(Auth::user()->role === 'Sekolah'): ?>
                                    <td class="px-6 py-4 font-medium text-gray-900"><?php echo e($item['periode']); ?></td>
                                <?php else: ?>
                                    <td class="px-6 py-4 font-medium text-gray-900"><?php echo e($item['nama_sekolah']); ?></td>
                                <?php endif; ?>

                                <td class="px-6 py-4 text-center">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold">
                                        <?php echo e($item['masuk']); ?>

                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs font-semibold">
                                        <?php echo e($item['keluar']); ?>

                                    </span>
                                </td>

                                <?php if(Auth::user()->role !== 'Sekolah'): ?>
                                    <td class="px-6 py-4 text-center">
                                        <?php if($item['netto'] > 0): ?>
                                            <span class="text-green-600 font-semibold">+<?php echo e($item['netto']); ?></span>
                                        <?php elseif($item['netto'] < 0): ?>
                                            <span class="text-red-600 font-semibold"><?php echo e($item['netto']); ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="<?php echo e(Auth::user()->role === 'Sekolah' ? 4 : 5); ?>"
                                    class="px-6 py-12 text-center text-gray-500">
                                    <i class="bi bi-inbox text-3xl block mb-2"></i>
                                    Tidak ada data mutasi pada periode ini.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\laporan\rekap\index.blade.php ENDPATH**/ ?>