

<?php $__env->startSection('title', 'Satuan Pendidikan'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Manajemen Satuan Pendidikan</h1>
                <p class="text-gray-500 mt-1">Kelola data sekolah di Kota Bogor</p>
            </div>
            <a href="<?php echo e(route('satpen.create')); ?>"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium">
                <i class="bi bi-plus-lg mr-2"></i> Tambah Sekolah
            </a>
        </div>

        <!-- Search & Filters -->
        <div class="bg-white rounded-xl shadow-sm border p-4">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" placeholder="Cari nama atau NPSN..."
                        value="<?php echo e(request('search')); ?>"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                </div>
                <div>
                    <select name="status"
                        class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                        <option value="">Semua Status</option>
                        <option value="Negeri" <?php echo e(request('status') == 'Negeri' ? 'selected' : ''); ?>>Negeri</option>
                        <option value="Swasta" <?php echo e(request('status') == 'Swasta' ? 'selected' : ''); ?>>Swasta</option>
                    </select>
                </div>
                <div>
                    <select name="is_local"
                        class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                        <option value="">Semua Sumber</option>
                        <option value="1" <?php echo e(request('is_local') === '1' ? 'selected' : ''); ?>>Lokal (Kota Bogor)</option>
                        <option value="0" <?php echo e(request('is_local') === '0' ? 'selected' : ''); ?>>Eksternal</option>
                    </select>
                </div>
                <button type="submit"
                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition font-medium">
                    <i class="bi bi-search mr-1"></i> Cari
                </button>
                <?php if(request()->hasAny(['search', 'status', 'is_local'])): ?>
                    <a href="<?php echo e(route('satpen.index')); ?>" class="px-4 py-2 text-gray-500 hover:text-gray-700">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">NPSN</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nama Sekolah</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Bentuk</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kota</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Sumber</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php $__empty_1 = true; $__currentLoopData = $satpenList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $satpen): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-mono text-sm"><?php echo e($satpen->npsn); ?></td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900"><?php echo e($satpen->nama); ?></div>
                                    <div class="text-xs text-gray-500 truncate max-w-xs"><?php echo e($satpen->alamat_lengkap ?? '-'); ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm"><?php echo e($satpen->bentuk_pendidikan ?? 'SMP'); ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full <?php echo e($satpen->status_sp == 'Negeri' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'); ?>">
                                        <?php echo e($satpen->status_sp ?? 'Swasta'); ?>

                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm"><?php echo e($satpen->kota ?? '-'); ?></td>
                                <td class="px-4 py-3">
                                    <?php if($satpen->is_local): ?>
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">
                                            <i class="bi bi-geo-alt mr-1"></i> Lokal
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                                            <i class="bi bi-globe mr-1"></i> Eksternal
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="<?php echo e(route('satpen.edit', $satpen->id)); ?>"
                                        class="inline-flex items-center px-3 py-1.5 text-sm text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition">
                                        <i class="bi bi-pencil mr-1"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    <i class="bi bi-building text-4xl text-gray-300"></i>
                                    <p class="mt-2">Tidak ada data ditemukan</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if($satpenList->hasPages()): ?>
                <div class="px-4 py-3 border-t bg-gray-50">
                    <?php echo e($satpenList->withQueryString()->links()); ?>

                </div>
            <?php endif; ?>
        </div>

        <!-- Stats -->
        <div class="text-sm text-gray-500 text-center">
            Total: <?php echo e($satpenList->total()); ?> satuan pendidikan
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\satpen\index.blade.php ENDPATH**/ ?>