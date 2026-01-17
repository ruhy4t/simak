

<?php $__env->startSection('title', 'Buat Mutasi Keluar'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-8 animate-fade-in-up">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="<?php echo e(route('mutasi.keluar.index')); ?>" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-all">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Buat Mutasi Keluar</h1>
                <p class="text-slate-500 mt-1">Ajukan permohonan mutasi siswa keluar</p>
            </div>
        </div>

        <!-- Form -->
        <form action="<?php echo e(route('mutasi.keluar.store')); ?>" method="POST" enctype="multipart/form-data" class="space-y-6"
            data-autosave="mutasi-keluar">
            <?php echo csrf_field(); ?>

            <!-- Data Siswa -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-sky-100 flex items-center justify-center">
                        <i class="bi bi-person-fill text-sky-600"></i>
                    </div>
                    Data Siswa
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Siswa *</label>
                        <input type="text" name="nama_murid" value="<?php echo e(old('nama_murid')); ?>" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 <?php $__errorArgs = ['nama_murid'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            placeholder="Nama lengkap siswa">
                        <?php $__errorArgs = ['nama_murid'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NISN *</label>
                        <input type="text" name="nisn" value="<?php echo e(old('nisn')); ?>" required maxlength="10"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 font-mono <?php $__errorArgs = ['nisn'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            placeholder="10 digit NISN">
                        <?php $__errorArgs = ['nisn'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NIS</label>
                        <input type="text" name="nis" value="<?php echo e(old('nis')); ?>"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 font-mono"
                            placeholder="Nomor Induk Siswa (opsional)">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Kelas *</label>
                        <select name="kelas" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 cursor-pointer appearance-none <?php $__errorArgs = ['kelas'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <option value="">Pilih Kelas</option>
                            <option value="VII" <?php echo e(old('kelas') == 'VII' ? 'selected' : ''); ?>>VII (Tujuh)</option>
                            <option value="VIII" <?php echo e(old('kelas') == 'VIII' ? 'selected' : ''); ?>>VIII (Delapan)</option>
                            <option value="IX" <?php echo e(old('kelas') == 'IX' ? 'selected' : ''); ?>>IX (Sembilan)</option>
                        </select>
                        <?php $__errorArgs = ['kelas'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tempat Lahir *</label>
                        <input type="text" name="tempat_lahir" value="<?php echo e(old('tempat_lahir')); ?>" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 <?php $__errorArgs = ['tempat_lahir'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            placeholder="Kota/Kabupaten">
                        <?php $__errorArgs = ['tempat_lahir'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Lahir *</label>
                        <input type="date" name="tanggal_lahir" value="<?php echo e(old('tanggal_lahir')); ?>" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 cursor-pointer <?php $__errorArgs = ['tanggal_lahir'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php $__errorArgs = ['tanggal_lahir'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Jenis Kelamin *</label>
                        <select name="jenis_kelamin" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 cursor-pointer appearance-none <?php $__errorArgs = ['jenis_kelamin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <option value="">Pilih</option>
                            <option value="L" <?php echo e(old('jenis_kelamin') == 'L' ? 'selected' : ''); ?>>Laki-laki</option>
                            <option value="P" <?php echo e(old('jenis_kelamin') == 'P' ? 'selected' : ''); ?>>Perempuan</option>
                        </select>
                        <?php $__errorArgs = ['jenis_kelamin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <!-- Sekolah Asal -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                        <i class="bi bi-building text-orange-600"></i>
                    </div>
                    Sekolah Asal
                </h2>
                <?php
                    $isLocked = in_array(auth()->user()->role, ['Operator', 'Sekolah']);
                ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NPSN Sekolah Asal *</label>
                        <div class="relative">
                            <input type="text" name="npsn_sekolah_asal" id="npsn_sekolah_asal"
                                value="<?php echo e(old('npsn_sekolah_asal', $npsnSekolahAsal ?? $user->npsn ?? '')); ?>" required
                                maxlength="8"
                                <?php echo e($isLocked ? 'readonly' : ''); ?>

                                class="w-full px-4 py-2.5 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition outline-none <?php echo e($isLocked ? 'bg-slate-100 text-slate-500 cursor-not-allowed font-mono' : 'bg-slate-50 focus:bg-white text-slate-800'); ?>"
                                oninput="lookupNpsnSekolahAsal(this.value)">
                            <span id="npsn_asal_status" class="absolute right-4 top-1/2 -translate-y-1/2 text-sm"></span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Sekolah Asal *</label>
                        <input type="text" name="nama_sekolah_asal" id="nama_sekolah_asal"
                            value="<?php echo e(old('nama_sekolah_asal', $namaSekolahAsal ?? $user->nama_sekolah ?? '')); ?>" required
                            <?php echo e($isLocked ? 'readonly' : ''); ?>

                            class="w-full px-4 py-2.5 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition outline-none <?php echo e($isLocked ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'bg-slate-50 focus:bg-white text-slate-800'); ?>">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Sekolah Asal *</label>
                        <textarea name="alamat_sekolah_asal" id="alamat_sekolah_asal" required rows="2"
                            <?php echo e($isLocked ? 'readonly' : ''); ?>

                            class="w-full px-4 py-2.5 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition outline-none resize-none <?php $__errorArgs = ['alamat_sekolah_asal'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php echo e($isLocked ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : 'bg-slate-50 focus:bg-white text-slate-800'); ?>"><?php echo e(old('alamat_sekolah_asal', $alamatSekolahAsal ?? '')); ?></textarea>
                        <?php $__errorArgs = ['alamat_sekolah_asal'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <!-- Di section Sekolah Asal, setelah field alamat_sekolah_asal -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Surat Mutasi *</label>
                        <p class="text-xs text-rose-500 italic mb-2">Silahkan isi dengan Nomor Surat Keterangan Pindah dari
                            Sekolah</p>
                        <input type="text" name="nomor_surat_sekolah" value="<?php echo e(old('nomor_surat_sekolah')); ?>" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 <?php $__errorArgs = ['nomor_surat_sekolah'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            placeholder="contoh: 421/XXX/SMP/2024">
                        <?php $__errorArgs = ['nomor_surat_sekolah'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Surat Mutasi *</label>
                        <p class="text-xs text-rose-500 italic mb-2">Silahkan isi dengan Tanggal Surat Keterangan Pindah dari
                            Sekolah</p>
                        <input type="date" name="tanggal_surat_sekolah" value="<?php echo e(old('tanggal_surat_sekolah')); ?>" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 cursor-pointer <?php $__errorArgs = ['tanggal_surat_sekolah'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <?php $__errorArgs = ['tanggal_surat_sekolah'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            </div>

            <!-- Sekolah Tujuan -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <i class="bi bi-geo-alt-fill text-emerald-600"></i>
                    </div>
                    Sekolah Tujuan
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">NPSN Sekolah Tujuan <span
                                id="npsn_required_label">*</span></label>
                        <div class="relative">
                            <input type="text" name="npsn_sekolah_tujuan" id="npsn_sekolah_tujuan"
                                value="<?php echo e(old('npsn_sekolah_tujuan')); ?>" maxlength="8"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 font-mono <?php $__errorArgs = ['npsn_sekolah_tujuan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                placeholder="8 karakter (contoh: 12345678 atau P1234567)">
                            <span id="npsn_loading" class="hidden absolute right-3 top-2.5 text-slate-400">
                                <i class="bi bi-arrow-repeat animate-spin"></i>
                            </span>
                        </div>
                        <p id="npsn_status" class="text-xs mt-1 text-slate-500">Contoh: 12345678 atau P1234567 (PKBM)</p>
                        <?php $__errorArgs = ['npsn_sekolah_tujuan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Sekolah Tujuan *</label>
                        <input type="text" name="nama_sekolah_tujuan" id="nama_sekolah_tujuan"
                            value="<?php echo e(old('nama_sekolah_tujuan')); ?>" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 <?php $__errorArgs = ['nama_sekolah_tujuan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            placeholder="Nama sekolah tujuan">
                        <?php $__errorArgs = ['nama_sekolah_tujuan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Provinsi Tujuan *</label>
                        <select id="provinsi_tujuan_select"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 cursor-pointer appearance-none <?php $__errorArgs = ['provinsi_tujuan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <option value="">Pilih Provinsi</option>
                        </select>
                        <input type="hidden" id="provinsi_tujuan" name="provinsi_tujuan"
                            value="<?php echo e(old('provinsi_tujuan')); ?>">
                        <?php $__errorArgs = ['provinsi_tujuan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Kabupaten/Kota Tujuan *</label>
                        <select id="kabupaten_kota_tujuan_select"
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 cursor-pointer appearance-none <?php $__errorArgs = ['kabupaten_kota_tujuan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <option value="">Pilih Kabupaten/Kota</option>
                        </select>
                        <input type="hidden" id="kabupaten_kota_tujuan" name="kabupaten_kota_tujuan"
                            value="<?php echo e(old('kabupaten_kota_tujuan')); ?>">
                        <?php $__errorArgs = ['kabupaten_kota_tujuan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat Sekolah Tujuan *</label>
                        <textarea name="alamat_sekolah_tujuan" id="alamat_sekolah_tujuan" rows="2" required
                            class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 resize-none <?php $__errorArgs = ['alamat_sekolah_tujuan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                            placeholder="Alamat lengkap sekolah tujuan"><?php echo e(old('alamat_sekolah_tujuan')); ?></textarea>
                        <?php $__errorArgs = ['alamat_sekolah_tujuan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-3 cursor-pointer p-4 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                            <input type="checkbox" name="tujuan_luar_negeri" id="tujuan_luar_negeri" value="1"
                                class="w-5 h-5 rounded text-sky-600 focus:ring-sky-500 border-slate-300" onchange="toggleTujuanLuarNegeri()">
                            <div>
                                <span class="text-sm font-semibold text-slate-700">Sekolah Tujuan di Luar Negeri</span>
                                <p class="text-xs text-slate-500 mt-0.5">Centang jika siswa pindah ke sekolah di luar negeri. NPSN menjadi opsional.</p>
                            </div>
                        </label>
                        <input type="hidden" id="tujuan_luar_negeri_hidden" name="tujuan_luar_negeri_flag" value="0">
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-4 flex items-center gap-1.5 p-3 bg-slate-50 rounded-lg">
                    <i class="bi bi-info-circle-fill text-sky-500"></i>
                    <span>Jika NPSN tidak ditemukan di database, silakan cari di <a href="https://referensi.data.kemendikdasmen.go.id/pendidikan/dikdas" target="_blank"
                        class="text-sky-600 hover:text-sky-700 hover:underline font-medium">referensi.data.kemendikdasmen.go.id</a></span>
                </p>
            </div>

            <!-- Alasan Pindah -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="bi bi-chat-left-text-fill text-purple-600"></i>
                    </div>
                    Alasan Pindah / Mutasi Keluar
                </h2>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Alasan Pindah Sekolah *</label>
                    <textarea name="alasan_pindah" required rows="3"
                        class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 focus:bg-white transition text-slate-800 placeholder:text-slate-400 resize-none <?php $__errorArgs = ['alasan_pindah'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                        placeholder="Contoh: Mengikuti orang tua pindah tugas ke luar kota."><?php echo e(old('alasan_pindah')); ?></textarea>
                    <?php $__errorArgs = ['alasan_pindah'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <!-- Dokumen -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center">
                        <i class="bi bi-file-earmark-pdf-fill text-rose-600"></i>
                    </div>
                    <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin'])): ?>
                        Konfirmasi Berkas
                    <?php else: ?>
                        Upload Dokumen
                    <?php endif; ?>
                </h2>

                <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin'])): ?>
                    <!-- Mode Dinas: Checkbox konfirmasi berkas tersedia -->
                    <input type="hidden" name="is_dinas_entry" value="1">
                    <div class="bg-sky-50 border border-sky-100 rounded-xl p-4 mb-6 text-sm text-sky-800 flex gap-3">
                        <i class="bi bi-info-circle-fill text-lg"></i>
                        <span class="mt-0.5">Centang dokumen yang sudah diserahkan secara fisik oleh pemohon.</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors group">
                            <input type="checkbox" name="has_suket_keluar" value="1" required
                                class="w-5 h-5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 transition">
                            <div>
                                <span class="font-semibold text-slate-700 group-hover:text-slate-900">Surat Keterangan Keluar</span>
                                <p class="text-xs text-slate-500 mt-0.5">Dari Kepala Sekolah Asal</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors group">
                            <input type="checkbox" name="has_suket_dapo" value="1" required
                                class="w-5 h-5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 transition">
                            <div>
                                <span class="font-semibold text-slate-700 group-hover:text-slate-900">Surat Keterangan Dapodik</span>
                                <p class="text-xs text-slate-500 mt-0.5">Dicetak dari Aplikasi Dapodik</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors group">
                            <input type="checkbox" name="has_suket_diterima" value="1" required
                                class="w-5 h-5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 transition">
                            <div>
                                <span class="font-semibold text-slate-700 group-hover:text-slate-900">Surat Keterangan Diterima</span>
                                <p class="text-xs text-slate-500 mt-0.5">Dari Kepala Sekolah Tujuan</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-4 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors group">
                            <input type="checkbox" name="has_rapor" value="1" required
                                class="w-5 h-5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500 transition">
                            <div>
                                <span class="font-semibold text-slate-700 group-hover:text-slate-900">Rapor Lengkap</span>
                                <p class="text-xs text-slate-500 mt-0.5">Rapor SMP lengkap</p>
                            </div>
                        </label>
                    </div>
                <?php else: ?>
                    <!-- Mode Normal: Upload File -->
                    <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 mb-6 text-sm text-amber-800 flex gap-3">
                        <i class="bi bi-exclamation-circle-fill text-lg"></i>
                        <span class="mt-0.5">Semua dokumen harus dalam format <strong>PDF</strong>. Maksimal 1MB per file (kecuali rapor 5MB).</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Surat Keterangan Keluar *</label>
                            <p class="text-xs text-rose-500 italic mb-2">Silahkan unggah PDF hasil scan dari dokumen asli Surat Keterangan Mutasi / Keluar dari Kepala Sekolah</p>
                            <input type="file" name="file_suket_keluar" accept=".pdf" required onchange="validateFile(this, 1)"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 text-slate-500 <?php $__errorArgs = ['file_suket_keluar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <?php $__errorArgs = ['file_suket_keluar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Surat Keterangan Dapodik *</label>
                            <p class="text-xs text-rose-500 italic mb-2">Silahkan unggah PDF hasil scan Surat Keterangan yang dicetak dari Aplikasi Dapodik (sudah TTD Kepala Sekolah)</p>
                            <input type="file" name="file_suket_dapo" accept=".pdf" required onchange="validateFile(this, 1)"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 text-slate-500 <?php $__errorArgs = ['file_suket_dapo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <?php $__errorArgs = ['file_suket_dapo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Surat Keterangan Diterima *</label>
                            <p class="text-xs text-rose-500 italic mb-2">Silahkan unggah PDF hasil scan Surat Keterangan Diterima dari Sekolah Tujuan (sudah TTD)</p>
                            <input type="file" name="file_suket_diterima" accept=".pdf" required
                                onchange="validateFile(this, 1)"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 text-slate-500 <?php $__errorArgs = ['file_suket_diterima'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <?php $__errorArgs = ['file_suket_diterima'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Rapor Lengkap *</label>
                            <p class="text-xs text-rose-500 italic mb-2">Upload Rapor SMP lengkap (Identitas, Nilai, Mutasi) yang sudah ditandatangani. Maks 5MB.</p>
                            <input type="file" name="file_rapor" accept=".pdf" required onchange="validateFile(this, 5)"
                                class="w-full px-4 py-2.5 bg-slate-50 border-0 rounded-xl focus:ring-2 focus:ring-sky-100 transition file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 text-slate-500 <?php $__errorArgs = ['file_rapor'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> ring-2 ring-rose-100 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <?php $__errorArgs = ['file_rapor'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-rose-500 text-xs mt-1 font-medium"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Submit -->
            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="<?php echo e(route('mutasi.keluar.index')); ?>"
                    class="px-6 py-2.5 border border-slate-300 text-slate-600 hover:bg-slate-50 hover:text-slate-800 rounded-xl transition font-medium">
                    Batal
                </a>
                <button type="submit"
                    class="px-6 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl transition font-medium flex items-center shadow-lg shadow-slate-900/20">
                    <i class="bi bi-send-fill mr-2"></i>
                    Kirim Pengajuan
                </button>
            </div>
        </form>
    </div>
    
    <style>
        .animate-fade-in-up { animation: fadeInUp 0.5s ease-out; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(asset('js/mutasi-keluar.js')); ?>"></script>
    <script>
        window.MUTASI_CONFIG = {
            routes: {
                index: '<?php echo e(route('mutasi.keluar.index')); ?>',
                apiNpsn: '<?php echo e(route('mutasi.keluar.api.npsn', ['npsn' => ':npsn'])); ?>',
                apiSatpen: '<?php echo e(route('satpen.api.find', ['npsn' => ':npsn'])); ?>',
                apiProvinces: '<?php echo e(route('mutasi.keluar.api.provinces')); ?>',
                apiRegencies: '<?php echo e(route('mutasi.keluar.api.regencies', ['provinceId' => ':provinceId'])); ?>'
            }
        };
    </script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\mutasi\keluar\create.blade.php ENDPATH**/ ?>