

<?php $__env->startSection('title', 'Ganti Password'); ?>

<?php $__env->startSection('content'); ?>
    <div class="min-h-[80vh] flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-xl shadow-lg border border-red-100 overflow-hidden">
            <!-- Header -->
            <div class="bg-red-50 px-6 py-4 border-b border-red-100 flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-shield-lock text-red-600 text-xl"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-red-800">Keamanan Akun</h2>
                    <p class="text-xs text-red-600">Anda wajib mengganti password.</p>
                </div>
            </div>

            <div class="p-6">
                <?php if(session('warning')): ?>
                    <div class="mb-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-start gap-3">
                        <i class="bi bi-exclamation-triangle text-yellow-600 mt-0.5"></i>
                        <p class="text-sm text-yellow-800"><?php echo e(session('warning')); ?></p>
                    </div>
                <?php endif; ?>

                <form action="<?php echo e(route('password.update')); ?>" method="POST" class="space-y-4">
                    <?php echo csrf_field(); ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini</label>
                        <div class="relative" x-data="{ show: false }">
                            <input :type="show ? 'text' : 'password'" name="current_password" required
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-red-100 focus:border-red-500 outline-none pr-10">
                            <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none z-10 transition-colors cursor-pointer">
                                <i class="bi" :class="show ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
                            </button>
                        </div>
                        <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="border-t border-gray-100 my-4"></div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                        <div class="relative" x-data="{ show: false }">
                            <input :type="show ? 'text' : 'password'" name="password" required minlength="8"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none pr-10">
                            <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none z-10 transition-colors cursor-pointer">
                                <i class="bi" :class="show ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                        <div class="relative" x-data="{ show: false }">
                            <input :type="show ? 'text' : 'password'" name="password_confirmation" required minlength="8"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none pr-10">
                            <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none z-10 transition-colors cursor-pointer">
                                <i class="bi" :class="show ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
                            </button>
                        </div>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-xs text-red-600 mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="bi bi-info-circle mr-1"></i>
                            Minimal 8 karakter, mengandung huruf dan angka.
                        </p>
                    </div>

                    <button type="submit"
                        class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                        <i class="bi bi-check-lg"></i> Simpan Password Baru
                    </button>
                </form>
            </div>

            <div
                class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex justify-between items-center text-xs text-gray-500">
                <span>&copy; <?php echo e(date('Y')); ?> SIMAK</span>
                <form action="<?php echo e(route('logout')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="text-gray-600 hover:text-red-600 font-medium">Log Out</button>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\auth\passwords\change.blade.php ENDPATH**/ ?>