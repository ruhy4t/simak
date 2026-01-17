<<<<<<< HEAD


<?php $__env->startSection('title', 'Tambah Satuan Pendidikan'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="<?php echo e(route('satpen.index')); ?>" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tambah Satuan Pendidikan</h1>
                <p class="text-gray-500 mt-1">Tambahkan sekolah baru ke database</p>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <form action="<?php echo e(route('satpen.store')); ?>" method="POST" class="space-y-6">
                <?php echo csrf_field(); ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- NPSN -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NPSN <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="npsn" required maxlength="8" pattern="[0-9A-Za-z]{8}"
                            value="<?php echo e(old('npsn')); ?>"
=======


<?php $__env->startSection('title', 'Tambah Satuan Pendidikan'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="<?php echo e(route('satpen.index')); ?>" class="text-gray-500 hover:text-gray-700">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tambah Satuan Pendidikan</h1>
                <p class="text-gray-500 mt-1">Tambahkan sekolah baru ke database</p>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <form action="<?php echo e(route('satpen.store')); ?>" method="POST" class="space-y-6">
                <?php echo csrf_field(); ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- NPSN -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NPSN <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="npsn" required maxlength="8" pattern="[0-9A-Za-z]{8}"
                            value="<?php echo e(old('npsn')); ?>"
>>>>>>> 9466a07 (Upload project pertama kali)
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none <?php $__errorArgs = ['npsn'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
<<<<<<< HEAD
unset($__errorArgs, $__bag); ?>"
                            placeholder="8 digit NPSN">
=======
unset($__errorArgs, $__bag); ?>"
                            placeholder="8 digit NPSN">
>>>>>>> 9466a07 (Upload project pertama kali)
                        <?php $__errorArgs = ['npsn'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
<<<<<<< HEAD
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Nama Sekolah -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Sekolah <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="nama" required maxlength="150" value="<?php echo e(old('nama')); ?>"
=======
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Nama Sekolah -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Sekolah <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="nama" required maxlength="150" value="<?php echo e(old('nama')); ?>"
>>>>>>> 9466a07 (Upload project pertama kali)
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none <?php $__errorArgs = ['nama'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
<<<<<<< HEAD
unset($__errorArgs, $__bag); ?>"
                            placeholder="contoh: SMP Negeri 1 Bogor">
=======
unset($__errorArgs, $__bag); ?>"
                            placeholder="contoh: SMP Negeri 1 Bogor">
>>>>>>> 9466a07 (Upload project pertama kali)
                        <?php $__errorArgs = ['nama'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
<<<<<<< HEAD
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Bentuk Pendidikan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bentuk Pendidikan <span
                                class="text-red-500">*</span></label>
                        <select name="bentuk_pendidikan" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                            <option value="SMP" <?php echo e(old('bentuk_pendidikan') == 'SMP' ? 'selected' : ''); ?>>SMP</option>
                            <option value="MTs" <?php echo e(old('bentuk_pendidikan') == 'MTs' ? 'selected' : ''); ?>>MTs</option>
                            <option value="SMLB" <?php echo e(old('bentuk_pendidikan') == 'SMLB' ? 'selected' : ''); ?>>SMLB</option>
                            <option value="PKBM" <?php echo e(old('bentuk_pendidikan') == 'PKBM' ? 'selected' : ''); ?>>PKBM</option>
                            <option value="SMP Terbuka" <?php echo e(old('bentuk_pendidikan') == 'SMP Terbuka' ? 'selected' : ''); ?>>SMP
                                Terbuka</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status <span
                                class="text-red-500">*</span></label>
                        <select name="status_sp" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                            <option value="Negeri" <?php echo e(old('status_sp') == 'Negeri' ? 'selected' : ''); ?>>Negeri</option>
                            <option value="Swasta" <?php echo e(old('status_sp', 'Swasta') == 'Swasta' ? 'selected' : ''); ?>>Swasta
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Alamat Section -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Alamat</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Alamat Jalan -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Jalan</label>
                            <textarea name="alamat_jalan" rows="2"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none"
                                placeholder="Jalan, nomor, RT/RW"><?php echo e(old('alamat_jalan')); ?></textarea>
                        </div>

                        <!-- Kelurahan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan</label>
                            <input type="text" name="kelurahan" maxlength="50" value="<?php echo e(old('kelurahan')); ?>"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none"
                                placeholder="contoh: Paledang">
                        </div>

                        <!-- Kecamatan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan</label>
                            <input type="text" name="kecamatan" maxlength="50" value="<?php echo e(old('kecamatan')); ?>"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none"
                                placeholder="contoh: Bogor Tengah">
                        </div>

                        <!-- Kota -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kota <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="kota" required maxlength="50" value="<?php echo e(old('kota', 'Kota Bogor')); ?>"
=======
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Bentuk Pendidikan -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bentuk Pendidikan <span
                                class="text-red-500">*</span></label>
                        <select name="bentuk_pendidikan" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                            <option value="SMP" <?php echo e(old('bentuk_pendidikan') == 'SMP' ? 'selected' : ''); ?>>SMP</option>
                            <option value="MTs" <?php echo e(old('bentuk_pendidikan') == 'MTs' ? 'selected' : ''); ?>>MTs</option>
                            <option value="SMLB" <?php echo e(old('bentuk_pendidikan') == 'SMLB' ? 'selected' : ''); ?>>SMLB</option>
                            <option value="PKBM" <?php echo e(old('bentuk_pendidikan') == 'PKBM' ? 'selected' : ''); ?>>PKBM</option>
                            <option value="SMP Terbuka" <?php echo e(old('bentuk_pendidikan') == 'SMP Terbuka' ? 'selected' : ''); ?>>SMP
                                Terbuka</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status <span
                                class="text-red-500">*</span></label>
                        <select name="status_sp" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                            <option value="Negeri" <?php echo e(old('status_sp') == 'Negeri' ? 'selected' : ''); ?>>Negeri</option>
                            <option value="Swasta" <?php echo e(old('status_sp', 'Swasta') == 'Swasta' ? 'selected' : ''); ?>>Swasta
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Alamat Section -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Alamat</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Alamat Jalan -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Jalan</label>
                            <textarea name="alamat_jalan" rows="2"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none"
                                placeholder="Jalan, nomor, RT/RW"><?php echo e(old('alamat_jalan')); ?></textarea>
                        </div>

                        <!-- Kelurahan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kelurahan</label>
                            <input type="text" name="kelurahan" maxlength="50" value="<?php echo e(old('kelurahan')); ?>"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none"
                                placeholder="contoh: Paledang">
                        </div>

                        <!-- Kecamatan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kecamatan</label>
                            <input type="text" name="kecamatan" maxlength="50" value="<?php echo e(old('kecamatan')); ?>"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none"
                                placeholder="contoh: Bogor Tengah">
                        </div>

                        <!-- Kota -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kota <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="kota" required maxlength="50" value="<?php echo e(old('kota', 'Kota Bogor')); ?>"
>>>>>>> 9466a07 (Upload project pertama kali)
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none <?php $__errorArgs = ['kota'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
<<<<<<< HEAD
unset($__errorArgs, $__bag); ?>">
=======
unset($__errorArgs, $__bag); ?>">
>>>>>>> 9466a07 (Upload project pertama kali)
                            <?php $__errorArgs = ['kota'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
<<<<<<< HEAD
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Provinsi -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="provinsi" required maxlength="50"
                                value="<?php echo e(old('provinsi', 'Jawa Barat')); ?>"
=======
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Provinsi -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi <span
                                    class="text-red-500">*</span></label>
                            <input type="text" name="provinsi" required maxlength="50"
                                value="<?php echo e(old('provinsi', 'Jawa Barat')); ?>"
>>>>>>> 9466a07 (Upload project pertama kali)
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none <?php $__errorArgs = ['provinsi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
<<<<<<< HEAD
unset($__errorArgs, $__bag); ?>">
=======
unset($__errorArgs, $__bag); ?>">
>>>>>>> 9466a07 (Upload project pertama kali)
                            <?php $__errorArgs = ['provinsi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
<<<<<<< HEAD
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="<?php echo e(route('satpen.index')); ?>"
                        class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition font-medium">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-save mr-2"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
=======
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="<?php echo e(route('satpen.index')); ?>"
                        class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition font-medium">
                        Batal
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-save mr-2"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
>>>>>>> 9466a07 (Upload project pertama kali)
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\satpen\create.blade.php ENDPATH**/ ?>