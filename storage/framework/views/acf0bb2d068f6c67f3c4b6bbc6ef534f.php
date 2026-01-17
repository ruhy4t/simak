

<?php $__env->startSection('title', 'Detail Wilayah - ' . $province->nama); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <a href="<?php echo e(route('admin.regions.index')); ?>" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo e($province->nama); ?></h1>
                <p class="text-gray-500 mt-1">Kode: <?php echo e($province->id); ?></p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="p-4 border-b bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-2">
                    <h3 class="font-semibold text-gray-900">Daftar Kabupaten/Kota</h3>
                    <span
                        class="text-xs px-2 py-0.5 rounded-full bg-gray-200 text-gray-700"><?php echo e($regencies->total()); ?></span>
                </div>

                <form action="<?php echo e(route('admin.regions.show', $province->id)); ?>" method="GET"
                    class="relative w-full sm:w-64">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Cari Kab/Kota..."
                        class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                </form>
            </div>

            <?php if($regencies->count() > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-700 w-24">Kode</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-700">Nama Kabupaten/Kota</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php $__currentLoopData = $regencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-gray-600 font-mono"><?php echo e($reg->id); ?></td>
                                    <td class="px-4 py-3 text-gray-900"><?php echo e(str_replace('Kabupaten', 'Kab.', $reg->nama)); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t">
                    <?php echo e($regencies->withQueryString()->links()); ?>

                </div>
            <?php else: ?>
                <div class="p-8 text-center">
                    <p class="text-gray-500">Data tidak ditemukan.</p>
                    <?php if(request('search')): ?>
                        <a href="<?php echo e(route('admin.regions.show', $province->id)); ?>"
                            class="text-primary-600 hover:underline text-sm">Reset Pencarian</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\admin\regions\show.blade.php ENDPATH**/ ?>