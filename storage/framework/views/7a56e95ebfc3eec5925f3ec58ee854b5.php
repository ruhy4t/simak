

<?php $__env->startSection('title', 'Profil Saya'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Profil Saya</h1>
            <p class="text-gray-500 mt-1">Kelola informasi profil dan keamanan akun Anda</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Edit Profile -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="bi bi-person-circle mr-2 text-primary-600"></i>
                    Informasi Profil
                </h2>

                <form action="<?php echo e(route('profile.update')); ?>" method="POST" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" value="<?php echo e($user->username); ?>" disabled
                            class="w-full px-4 py-2 bg-gray-100 border rounded-lg text-gray-500 cursor-not-allowed">
                        <p class="text-xs text-gray-400 mt-1">Username tidak dapat diubah</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <input type="text" value="<?php echo e($user->role); ?>" disabled
                            class="w-full px-4 py-2 bg-gray-100 border rounded-lg text-gray-500 cursor-not-allowed">
                    </div>

                    <div>
                        <label for="nama_pengguna" class="block text-sm font-medium text-gray-700 mb-1">Nama Pengguna
                            *</label>
                        <input type="text" id="nama_pengguna" name="nama_pengguna"
                            value="<?php echo e(old('nama_pengguna', $user->nama_pengguna)); ?>" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none <?php $__errorArgs = ['nama_pengguna'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php $__errorArgs = ['nama_pengguna'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="no_kontak" class="block text-sm font-medium text-gray-700 mb-1">Nomor HP</label>
                        <input type="text" id="no_kontak" name="no_kontak" value="<?php echo e(old('no_kontak', $user->no_kontak)); ?>"
                            placeholder="08xxxxxxxxxx"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none <?php $__errorArgs = ['no_kontak'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php $__errorArgs = ['no_kontak'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    
                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <label for="telegram_chat_id" class="block text-sm font-medium text-blue-800 mb-1">
                            <i class="bi bi-telegram mr-1"></i> Telegram Chat ID
                            <?php if($user->telegram_chat_id): ?>
                                <span class="ml-2 text-green-600"><i class="bi bi-check-circle-fill"></i> Aktif</span>
                            <?php endif; ?>
                        </label>
                        <input type="text" id="telegram_chat_id" name="telegram_chat_id"
                            value="<?php echo e(old('telegram_chat_id', $user->telegram_chat_id)); ?>"
                            placeholder="Masukkan Chat ID dari bot Telegram"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 outline-none <?php $__errorArgs = ['telegram_chat_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php $__errorArgs = ['telegram_chat_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                        
                        <div class="mt-3 text-sm text-blue-700" x-data="{ showTutorial: false }">
                            <button type="button" @click="showTutorial = !showTutorial" class="font-medium hover:underline">
                                <i class="bi" :class="showTutorial ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                                Cara mendapatkan Chat ID
                            </button>
                            <div x-show="showTutorial" x-transition
                                class="mt-2 space-y-2 bg-white p-3 rounded-lg border border-blue-100">
                                <div class="flex items-start gap-2">
                                    <span
                                        class="flex-shrink-0 w-5 h-5 bg-blue-600 text-white rounded-full text-xs flex items-center justify-center">1</span>
                                    <span>Buka aplikasi <strong>Telegram</strong> di HP atau Desktop</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span
                                        class="flex-shrink-0 w-5 h-5 bg-blue-600 text-white rounded-full text-xs flex items-center justify-center">2</span>
                                    <span>Buka Telegram, cari bot: <code
                                            class="bg-gray-100 px-1.5 py-0.5 rounded">@userinfobot</code></span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span
                                        class="flex-shrink-0 w-5 h-5 bg-blue-600 text-white rounded-full text-xs flex items-center justify-center">3</span>
                                    <span>Klik tombol <strong>Start</strong>. Bot akan membalas dengan <strong>ID</strong>
                                        Anda.</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span
                                        class="flex-shrink-0 w-5 h-5 bg-blue-600 text-white rounded-full text-xs flex items-center justify-center">4</span>
                                    <span>Copy angka ID tersebut.</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span
                                        class="flex-shrink-0 w-5 h-5 bg-blue-600 text-white rounded-full text-xs flex items-center justify-center">5</span>
                                    <span>Paste di kolom di atas & Simpan. Lalu chat bot kami:
                                        <strong>@simak_smp_bot</strong> agar aktif.</span>
                                </div>
                                <div class="mt-3 pt-2 border-t border-blue-100">
                                    <a href="https://t.me/simak_smp_bot" target="_blank"
                                        class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium transition">
                                        <i class="bi bi-telegram mr-1"></i> Buka Bot di Telegram
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if($user->npsn): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NPSN</label>
                            <input type="text" value="<?php echo e($user->npsn); ?>" disabled
                                class="w-full px-4 py-2 bg-gray-100 border rounded-lg text-gray-500 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Sekolah</label>
                            <input type="text" value="<?php echo e($user->nama_sekolah); ?>" disabled
                                class="w-full px-4 py-2 bg-gray-100 border rounded-lg text-gray-500 cursor-not-allowed">
                        </div>
                    <?php endif; ?>

                    <button type="submit"
                        class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 rounded-lg transition">
                        <i class="bi bi-check-lg mr-1"></i> Simpan Perubahan
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="bi bi-shield-lock mr-2 text-yellow-600"></i>
                    Ubah Password
                </h2>

                <form action="<?php echo e(route('profile.password')); ?>" method="POST" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <!-- Current Password -->
                    <div x-data="{ show: false }">
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Password Lama
                            *</label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" id="current_password" name="current_password" required
                                class="w-full px-4 py-2 pr-12 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none z-10 cursor-pointer transition-colors">
                                <i class="bi" :class="show ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
                            </button>
                        </div>
                        <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- New Password -->
                    <div x-data="{ show: false }">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru *</label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" id="password" name="password" required
                                class="w-full px-4 py-2 pr-12 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none z-10 cursor-pointer transition-colors">
                                <i class="bi" :class="show ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
                            </button>
                        </div>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <!-- Confirm Password -->
                    <div x-data="{ show: false }">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi
                            Password Baru *</label>
                        <div class="relative">
                            <input :type="show ? 'text' : 'password'" id="password_confirmation"
                                name="password_confirmation" required
                                class="w-full px-4 py-2 pr-12 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                            <button type="button" @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none z-10 cursor-pointer transition-colors">
                                <i class="bi" :class="show ? 'bi-eye-slash-fill' : 'bi-eye-fill'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-yellow-800">
                        <i class="bi bi-info-circle mr-1"></i>
                        <strong>Syarat Password:</strong>
                        <ul class="list-disc ml-5 mt-1 space-y-0.5">
                            <li>Minimal 8 karakter</li>
                            <li>Huruf besar (A-Z)</li>
                            <li>Huruf kecil (a-z)</li>
                            <li>Angka (0-9)</li>
                            <li>Minimal 1 karakter khusus (misal: !@#$%^&*_-+=)</li>
                        </ul>
                    </div>

                    <button type="submit"
                        class="w-full bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2.5 rounded-lg transition">
                        <i class="bi bi-key mr-1"></i> Ubah Password
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\profile\index.blade.php ENDPATH**/ ?>