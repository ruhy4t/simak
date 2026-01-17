

<?php $__env->startSection('title', 'Detail Mutasi Keluar'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-8 animate-fade-in-up" x-data="{ activeDoc: null }">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="<?php echo e(route('mutasi.keluar.index')); ?>"
                class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:text-sky-600 hover:border-sky-200 transition shadow-sm">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-slate-900">Detail Mutasi Keluar</h1>
                <div class="flex items-center gap-2 mt-1 text-sm text-slate-500">
                    <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-600 font-mono">#<?php echo e($mutasi->id_mutasi); ?></span>
                </div>
            </div>

            <?php
                $user = auth()->user();
                $isOwner = ($user->role === 'Operator' && $mutasi->pengusul === $user->username)
                    || ($user->role === 'Sekolah' && $mutasi->npsn_sekolah_asal === $user->npsn);
                $canCancel = in_array($user->role, ['Operator', 'Sekolah'])
                    && $isOwner
                    && $mutasi->status === 'Menunggu Verifikasi Dinas';
            ?>
            <?php if($canCancel): ?>
                <form action="<?php echo e(route('mutasi.keluar.cancel', $mutasi->id_mutasi)); ?>" method="POST"
                    data-confirm="PERINGATAN: Apakah Anda yakin ingin MENGHAPUS usulan mutasi ini? Tindakan ini akan MENGHAPUS SELURUH data dan dokumen secara PERMANEN dan tidak dapat dikembalikan."
                    data-confirm-title="Hapus Usulan Secara Permanen" data-confirm-type="danger">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <button type="submit"
                        class="px-4 py-2 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 rounded-xl text-sm font-medium transition flex items-center gap-2 shadow-sm">
                        <i class="bi bi-trash"></i> Batalkan & Hapus
                    </button>
                </form>
            <?php endif; ?>

            <?php
                $statusMap = [
                    'Menunggu Verifikasi Dinas' => 'bg-amber-100 text-amber-800 border border-amber-200',
                    'Proses' => 'bg-blue-100 text-blue-800 border border-blue-200',
                    'Proses TTD' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
                    'Disetujui Dinas' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                    'Selesai' => 'bg-sky-100 text-sky-800 border border-sky-200',
                    'Perlu Perbaikan' => 'bg-rose-100 text-rose-800 border border-rose-200',
                    'Dibatalkan' => 'bg-slate-200 text-slate-600 border border-slate-300',
                ];
                $statusClass = $statusMap[$mutasi->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200';
            ?>
            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-semibold <?php echo e($statusClass); ?>">
                <?php echo e($mutasi->status); ?>

            </span>
        </div>

        <!-- Layout Split untuk Verifikasi Dinas -->
        <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']) && $mutasi->status === 'Menunggu Verifikasi Dinas'): ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Kiri: Data & Dokumen dengan Preview -->
                <div class="space-y-4">
                    <!-- Info Siswa Lengkap -->
                    <div class="bg-white rounded-xl shadow-sm border p-4">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="bi bi-person text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900"><?php echo e($mutasi->nama_murid); ?></h3>
                                <p class="text-sm text-gray-500">NISN: <?php echo e($mutasi->nisn); ?> | NIS: <?php echo e($mutasi->nis ?? '-'); ?></p>
                            </div>
                        </div>

                        <!-- Detail Siswa -->
                        <div class="grid grid-cols-2 gap-4 text-sm mb-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div>
                                <span class="text-slate-500 text-xs uppercase tracking-wide font-semibold">TTL</span>
                                <p class="font-medium text-slate-900 mt-1"><?php echo e($mutasi->tempat_lahir); ?>,
                                    <?php echo e(\Carbon\Carbon::parse($mutasi->tanggal_lahir)->translatedFormat('d F Y')); ?>

                                </p>
                            </div>
                            <div>
                                <span class="text-slate-500 text-xs uppercase tracking-wide font-semibold">Jenis Kelamin</span>
                                <p class="font-medium text-slate-900 mt-1">
                                    <?php echo e($mutasi->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan'); ?>

                                </p>
                            </div>
                            <div>
                                <span class="text-slate-500 text-xs uppercase tracking-wide font-semibold">Kelas</span>
                                <p class="font-medium text-slate-900 mt-1"><?php echo e($mutasi->kelas); ?></p>
                            </div>
                            <div>
                                <span class="text-slate-500 text-xs uppercase tracking-wide font-semibold">Alasan Pindah</span>
                                <p class="font-medium text-slate-900 mt-1"><?php echo e($mutasi->alasan_pindah); ?></p>
                            </div>
                        </div>
                        <!-- Di dalam dl class="space-y-3" bagian Sekolah Asal, tambahkan: -->
                        <div class="grid grid-cols-2 gap-4 text-sm mb-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div>
                                <span class="text-slate-500 text-xs uppercase tracking-wide font-semibold">Nomor Surat
                                    Mutasi</span>
                                <dd class="font-medium text-slate-900 mt-1"><?php echo e($mutasi->nomor_surat_sekolah ?? '-'); ?></dd>
                            </div>
                            <div>
                                <span class="text-slate-500 text-xs uppercase tracking-wide font-semibold">Tanggal Surat
                                    Mutasi</span>
                                <dd class="font-medium text-slate-900 mt-1">
                                    <?php echo e($mutasi->tanggal_surat_sekolah ? \Carbon\Carbon::parse($mutasi->tanggal_surat_sekolah)->translatedFormat('j F Y') : '-'); ?>

                                </dd>
                            </div>
                        </div>
                        <!-- Sekolah Asal & Tujuan -->
                        <div class="grid grid-cols-1 gap-4 text-sm">
                            <div class="p-4 bg-orange-50 rounded-xl border border-orange-100">
                                <span class="text-orange-800 font-semibold text-xs uppercase tracking-wide block mb-2">Sekolah
                                    Asal</span>
                                <p class="font-bold text-slate-900 text-lg"><?php echo e($mutasi->nama_sekolah_asal); ?></p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span
                                        class="text-xs font-mono bg-white px-2 py-0.5 rounded border border-orange-200 text-orange-700"><?php echo e($mutasi->npsn_sekolah_asal); ?></span>
                                </div>
                                <p class="text-sm text-slate-600 mt-2"><?php echo e($mutasi->alamat_sekolah_asal); ?></p>
                            </div>
                            <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                                <span class="text-emerald-800 font-semibold text-xs uppercase tracking-wide block mb-2">Sekolah
                                    Tujuan</span>
                                <p class="font-bold text-slate-900 text-lg"><?php echo e($mutasi->nama_sekolah_tujuan); ?></p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span
                                        class="text-xs font-mono bg-white px-2 py-0.5 rounded border border-emerald-200 text-emerald-700"><?php echo e($mutasi->npsn_sekolah_tujuan); ?></span>
                                </div>
                                <p class="text-sm text-slate-600 mt-2"><?php echo e($mutasi->alamat_sekolah_tujuan); ?></p>
                                <p class="text-sm text-slate-600"><?php echo e($mutasi->kabupaten_kota_tujuan); ?>,
                                    <?php echo e($mutasi->provinsi_tujuan); ?>

                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Preview Dokumen dengan iframe -->
                    <div class="bg-white rounded-xl shadow-sm border p-4">
                        <h4 class="font-medium text-gray-900 mb-3">
                            <i class="bi bi-file-earmark-pdf text-red-600 mr-1"></i> Dokumen
                        </h4>
                        <?php
                            $docs = [
                                ['key' => 'file_suket_keluar', 'label' => 'Surat Keluar', 'file' => $mutasi->file_suket_keluar],
                                ['key' => 'file_suket_dapo', 'label' => 'Surat Dapodik', 'file' => $mutasi->file_suket_dapo],
                                ['key' => 'file_suket_diterima', 'label' => 'Surat Diterima', 'file' => $mutasi->file_suket_diterima],
                                ['key' => 'file_rapor', 'label' => 'Rapor', 'file' => $mutasi->file_rapor],
                            ];
                        ?>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <?php $__currentLoopData = $docs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($doc['file']): ?>
                                    <button type="button"
                                        @click="activeDoc = activeDoc === '<?php echo e($doc['key']); ?>' ? null : '<?php echo e($doc['key']); ?>'"
                                        :class="activeDoc === '<?php echo e($doc['key']); ?>' ? 'bg-primary-600 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition">
                                        <?php echo e($doc['label']); ?>

                                    </button>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php $__currentLoopData = $docs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($doc['file']): ?>
                                <div x-show="activeDoc === '<?php echo e($doc['key']); ?>'" x-transition
                                    class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                                    <iframe src="<?php echo e(route('dokumen.view', ['path' => $doc['file']])); ?>"
                                        class="w-full h-[500px] bg-slate-100"></iframe>
                                    <a href="<?php echo e(route('dokumen.view', ['path' => $doc['file']])); ?>" target="_blank"
                                        class="block text-center py-3 bg-slate-50 text-sky-600 text-sm font-medium hover:bg-slate-100 transition border-t border-slate-200">
                                        <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Layar Penuh (Tab Baru)
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <div x-show="!activeDoc" class="text-center py-8 text-gray-400">
                            <i class="bi bi-file-earmark-pdf text-4xl"></i>
                            <p class="mt-2 text-sm">Klik dokumen untuk preview</p>
                        </div>
                    </div>
                </div>

                <!-- Kanan: Form Verifikasi -->
                <div class="bg-white rounded-xl shadow-sm border p-6 h-fit sticky top-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="bi bi-clipboard-check text-primary-600 mr-2"></i>
                        Verifikasi Berkas
                    </h2>

                    <form action="<?php echo e(route('mutasi.keluar.verify', $mutasi->id_mutasi)); ?>" method="POST" class="space-y-4"
                        x-data="verifikasiKeluar()">
                        <?php echo csrf_field(); ?>

                        <!-- Checklist Dokumen -->
                        <div class="space-y-3">
                            <?php $__currentLoopData = $docs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($doc['file']): ?>
                                    <label class="flex items-center gap-3 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="<?php echo e($doc['key']); ?>_ok" value="1" checked
                                            class="w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500"
                                            data-label="<?php echo e($doc['label']); ?>"
                                            @change="toggleDoc('<?php echo e($doc['label']); ?>', $event.target.checked)">
                                        <span class="flex-1 font-medium"><?php echo e($doc['label']); ?></span>
                                        <span class="text-xs text-green-600">✓ Sesuai</span>
                                    </label>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <!-- Catatan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                            <textarea name="catatan_berkas" rows="3" x-model="catatan"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none text-sm"
                                placeholder="Catatan jika ada dokumen tidak sesuai..."></textarea>
                        </div>

                        <!-- Tombol Aksi -->
                        <!-- Tombol Aksi -->
                        <div class="flex gap-4 pt-4 border-t border-slate-100">
                            <button type="submit" name="action" value="approve"
                                class="flex-1 px-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition font-bold text-sm shadow-lg shadow-emerald-600/20">
                                <i class="bi bi-check-lg mr-2"></i> Setujui
                            </button>
                            <button type="submit" name="action" value="revision"
                                class="flex-1 px-4 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl transition font-bold text-sm shadow-lg shadow-amber-500/20">
                                <i class="bi bi-arrow-repeat mr-2"></i> Perbaikan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function verifikasiKeluar() {
                    return {
                        catatan: '',
                        toggleDoc(label, isChecked) {
                            const line = `- ${label} tidak sesuai`;
                            if (!isChecked) {
                                // Uncheck - tambah ke catatan jika belum ada
                                if (this.catatan.indexOf(label) === -1) {
                                    this.catatan = this.catatan ? this.catatan + '\n' + line : line;
                                }
                            } else {
                                // Check - hapus dari catatan
                                const lines = this.catatan.split('\n').filter(l => l.indexOf(label) === -1);
                                this.catatan = lines.join('\n');
                            }
                        }
                    }
                }
            </script>
        <?php else: ?>
            <!-- Normal View (non-verification) -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Data Siswa -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Data Siswa</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm text-gray-500">Nama Siswa</dt>
                                <dd class="font-medium"><?php echo e($mutasi->nama_murid); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">NISN</dt>
                                <dd class="font-medium"><?php echo e($mutasi->nisn); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Kelas</dt>
                                <dd class="font-medium"><?php echo e($mutasi->kelas ?? '-'); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">TTL</dt>
                                <dd class="font-medium">
                                    <?php echo e($mutasi->tempat_lahir ?? '-'); ?>,
                                    <?php echo e($mutasi->tanggal_lahir ? \Carbon\Carbon::parse($mutasi->tanggal_lahir)->translatedFormat('d F Y') : '-'); ?>

                                </dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Data Perpindahan -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b">Perpindahan</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm text-gray-500">Tanggal Pengajuan</dt>
                                <dd class="font-medium">
                                    <?php echo e($mutasi->tanggal_pengajuan ? \Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->translatedFormat('d F Y') : '-'); ?>

                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Sekolah Asal</dt>
                                <dd class="font-medium"><?php echo e($mutasi->nama_sekolah_asal); ?></dd>
                                <dd class="text-sm text-gray-500">NPSN: <?php echo e($mutasi->npsn_sekolah_asal); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Sekolah Tujuan</dt>
                                <dd class="font-medium"><?php echo e($mutasi->nama_sekolah_tujuan); ?></dd>
                                <dd class="text-sm text-gray-500">NPSN: <?php echo e($mutasi->npsn_sekolah_tujuan); ?></dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Alasan & Dokumen -->
                <div class="mt-6 pt-6 border-t">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Alasan Pindah</h3>
                    <p class="text-gray-700"><?php echo e($mutasi->alasan_pindah ?? '-'); ?></p>
                </div>

                <div class="mt-6 pt-6 border-t">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Dokumen</h3>
                    <?php
                        $normalDocs = [
                            ['key' => 'normal_suket_keluar', 'label' => 'Surat Keluar', 'file' => $mutasi->file_suket_keluar],
                            ['key' => 'normal_suket_dapo', 'label' => 'Surat Dapodik', 'file' => $mutasi->file_suket_dapo],
                            ['key' => 'normal_suket_diterima', 'label' => 'Surat Diterima', 'file' => $mutasi->file_suket_diterima],
                            ['key' => 'normal_rapor', 'label' => 'Rapor', 'file' => $mutasi->file_rapor],
                        ];
                    ?>
                    <div class="flex flex-wrap gap-2">
                        <?php $__currentLoopData = $normalDocs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($doc['file']): ?>
                                <button type="button"
                                    @click="activeDoc = activeDoc === '<?php echo e($doc['key']); ?>' ? null : '<?php echo e($doc['key']); ?>'"
                                    :class="activeDoc === '<?php echo e($doc['key']); ?>' ? 'bg-red-600 text-white ring-2 ring-offset-1 ring-red-500 font-bold shadow-md' : 'bg-red-50 text-red-600 hover:bg-red-100 border border-red-200'"
                                    class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                                    <i class="bi bi-file-pdf"></i> <?php echo e($doc['label']); ?>

                                </button>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    
                    <?php $__currentLoopData = $normalDocs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($doc['file']): ?>
                            <div x-show="activeDoc === '<?php echo e($doc['key']); ?>'" x-transition
                                class="mt-4 border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                                <iframe src="<?php echo e(route('dokumen.view', ['path' => $doc['file']])); ?>" class="w-full bg-slate-100"
                                    style="height: 500px;"></iframe>
                                <a href="<?php echo e(route('dokumen.view', ['path' => $doc['file']])); ?>" target="_blank"
                                    class="block text-center py-3 bg-slate-50 text-sky-600 text-sm font-medium hover:bg-slate-100 transition border-t border-slate-200">
                                    <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Layar Penuh (Tab Baru)
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Input Surat Izin (setelah disetujui) -->
        <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']) && $mutasi->status === 'Disetujui Dinas' && !($mutasi->nomor_surat_izin ?? null)): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-blue-900 mb-4">
                    <i class="bi bi-file-earmark-text text-blue-600 mr-2"></i>
                    Terbitkan Surat Keterangan Mutasi
                </h2>
                <form action="<?php echo e(route('mutasi.keluar.issue-letter', $mutasi->id_mutasi)); ?>" method="POST" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Surat *</label>
                            <input type="text" name="nomor_surat_izin" required
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 outline-none"
                                placeholder="421/XXX/DISDIK/2024">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Surat *</label>
                            <input type="date" name="tanggal_surat_izin" required value="<?php echo e(date('Y-m-d')); ?>"
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-500 outline-none">
                        </div>
                    </div>
                    <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-file-check mr-1"></i> Terbitkan Surat
                    </button>
                </form>
            </div>
        <?php endif; ?>

        
        <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']) && $mutasi->status === 'Proses TTD'): ?>
            <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-indigo-900 mb-4">
                    <i class="bi bi-pen text-indigo-600 mr-2"></i>
                    Konfirmasi Tanda Tangan Selesai
                </h2>
                <p class="text-sm text-indigo-700 mb-4">
                    Surat sudah dicetak dan siap ditandatangani. Setelah TTD selesai, klik tombol di bawah untuk mengkonfirmasi.
                </p>
                <div class="flex flex-wrap gap-3 mb-4">
                    <a href="<?php echo e(route('mutasi.keluar.print-letter', $mutasi->id_mutasi)); ?>" target="_blank"
                        class="inline-flex items-center px-4 py-2 bg-white border border-indigo-300 text-indigo-700 rounded-lg hover:bg-indigo-50 transition">
                        <i class="bi bi-printer mr-2"></i> Cetak Surat
                    </a>
                </div>
                <form action="<?php echo e(route('mutasi.keluar.complete-letter', $mutasi->id_mutasi)); ?>" method="POST" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <div class="max-w-xs">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai TTD *</label>
                        <input type="date" name="tanggal_selesai" required value="<?php echo e(date('Y-m-d')); ?>"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 outline-none">
                    </div>
                    <button type="submit"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-check-circle mr-1"></i> Konfirmasi TTD Selesai
                    </button>
                </form>
            </div>
        <?php endif; ?>

        
        <?php if($mutasi->nomor_surat_izin ?? null): ?>
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-emerald-900 mb-3">
                    <i class="bi bi-file-earmark-check text-emerald-600 mr-2"></i>
                    Surat Keterangan Mutasi Terbit
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <p class="text-sm text-gray-600">Nomor Surat</p>
                        <p class="font-medium text-gray-900"><?php echo e($mutasi->nomor_surat_izin); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Tanggal Surat</p>
                        <p class="font-medium text-gray-900">
                            <?php echo e(\Carbon\Carbon::parse($mutasi->tanggal_surat_izin)->translatedFormat('d F Y')); ?>

                        </p>
                    </div>
                    <?php if($mutasi->tanggal_selesai ?? null): ?>
                        <div>
                            <p class="text-sm text-gray-600">Tanggal Selesai</p>
                            <p class="font-medium text-gray-900">
                                <?php echo e(\Carbon\Carbon::parse($mutasi->tanggal_selesai)->translatedFormat('d F Y')); ?>

                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap gap-3">
                    <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']) && in_array($mutasi->status, ['Proses TTD', 'Selesai'])): ?>
                        <a href="<?php echo e(route('mutasi.keluar.print-letter', $mutasi->id_mutasi)); ?>" target="_blank"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition font-medium">
                            <i class="bi bi-printer mr-2"></i> Cetak Surat Keterangan
                        </a>
                    <?php endif; ?>
                    <?php if(session('whatsapp_link')): ?>
                        <a href="<?php echo e(session('whatsapp_link')); ?>" target="_blank"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-medium">
                            <i class="bi bi-whatsapp mr-2"></i> Kirim Notifikasi WA ke <?php echo e(session('pengusul_name')); ?>

                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        
        <?php if($mutasi->status === 'Selesai' && ($mutasi->file_arsip_scan || in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']))): ?>
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">
                    <i class="bi bi-archive text-gray-600 mr-2"></i>
                    Arsip Digital (Scan Surat Asli)
                </h3>

                <?php if($mutasi->file_arsip_scan): ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="bi bi-file-earmark-pdf text-red-500 text-2xl"></i>
                            <div>
                                <p class="font-medium text-green-900">Arsip Tersedia</p>
                                <p class="text-sm text-green-700">Diupload pada:
                                    <?php echo e(\Carbon\Carbon::parse($mutasi->updated_at)->translatedFormat('d F Y H:i')); ?>

                                </p>
                            </div>
                        </div>
                        <button type="button" @click="activeDoc = activeDoc === 'arsip_scan' ? null : 'arsip_scan'"
                            :style="activeDoc === 'arsip_scan' ? 'background-color: #15803d !important; color: white !important;' : ''"
                            :class="activeDoc === 'arsip_scan' ? '!bg-green-700 !text-white ring-2 ring-offset-1 ring-green-600 font-bold shadow-md' : 'bg-green-600 text-white hover:bg-green-700 border border-green-700'"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                            <i class="bi bi-file-earmark-pdf mr-1"></i> Lihat Dokumen
                        </button>
                    </div>

                    
                    <div x-show="activeDoc === 'arsip_scan'" x-transition
                        class="mb-4 border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <iframe src="<?php echo e(route('dokumen.view', ['path' => $mutasi->file_arsip_scan])); ?>" class="w-full bg-slate-100"
                            style="height: 500px;"></iframe>
                        <a href="<?php echo e(route('dokumen.view', ['path' => $mutasi->file_arsip_scan])); ?>" target="_blank"
                            class="block text-center py-3 bg-slate-50 text-sky-600 text-sm font-medium hover:bg-slate-100 transition border-t border-slate-200">
                            <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Layar Penuh (Tab Baru)
                        </a>
                    </div>
                    <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin'])): ?>
                        <p class="text-sm text-gray-500 mb-2">Ingin mengganti file arsip? Upload ulang di bawah ini.</p>
                    <?php endif; ?>
                <?php elseif(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin'])): ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center gap-3">
                            <i class="bi bi-exclamation-circle text-yellow-600 text-xl"></i>
                            <p class="text-sm text-yellow-800">
                                Belum ada arsip digital yang diunggah. Silakan unggah hasil scan surat yang telah ditandatangani.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin'])): ?>
                    <form action="<?php echo e(route('mutasi.keluar.upload-arsip', $mutasi->id_mutasi)); ?>" method="POST"
                        enctype="multipart/form-data" class="flex items-end gap-4">
                        <?php echo csrf_field(); ?>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload File Scan (PDF, Max 2MB)</label>
                            <input type="file" name="file_arsip_scan" required accept=".pdf"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                        </div>
                        <button type="submit"
                            class="px-6 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-lg transition font-medium">
                            <i class="bi bi-upload mr-1"></i> Upload Arsip
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        
        <?php if($mutasi->catatan_berkas): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-yellow-800 mb-2">
                    <i class="bi bi-exclamation-triangle text-yellow-600 mr-2"></i>
                    Catatan dari Dinas
                </h3>
                <p class="text-yellow-900 whitespace-pre-line mb-4"><?php echo e($mutasi->catatan_berkas); ?></p>

                <?php
                    $user = auth()->user();
                    $isOwner = ($user->username === $mutasi->pengusul)
                        || ($user->role === 'Sekolah' && $user->npsn === $mutasi->npsn_sekolah_asal);
                    $canRevise = in_array($user->role, ['Operator', 'Sekolah'])
                        && $isOwner
                        && in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan']);
                ?>

                <?php if($canRevise): ?>
                    <a href="<?php echo e(route('mutasi.keluar.revision', $mutasi->id_mutasi)); ?>"
                        class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-pencil-square mr-2"></i> Perbaiki & Kirim Ulang
                    </a>
                <?php endif; ?>
            </div>
        <?php elseif(in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan'])): ?>
            
            <?php
                $user = auth()->user();
                $isOwner = ($user->username === $mutasi->pengusul)
                    || ($user->role === 'Sekolah' && $user->npsn === $mutasi->npsn_sekolah_asal);
                $canRevise = in_array($user->role, ['Operator', 'Sekolah']) && $isOwner;
            ?>

            <?php if($canRevise): ?>
                <div class="bg-orange-50 border border-orange-200 rounded-xl p-6">
                    <h3 class="text-lg font-semibold text-orange-800 mb-3">
                        <i class="bi bi-arrow-repeat text-orange-600 mr-2"></i>
                        <?php echo e($mutasi->status === 'Dibatalkan' ? 'Usulan Dibatalkan' : 'Data Perlu Diperbaiki'); ?>

                    </h3>
                    <p class="text-orange-700 mb-4">
                        <?php echo e($mutasi->status === 'Dibatalkan'
                        ? 'Usulan ini telah dibatalkan. Anda dapat mengedit dan mengirim ulang data ini (semua berkas wajib diupload ulang).'
                        : 'Data mutasi Anda perlu diperbaiki. Silakan edit dan kirim ulang.'); ?>

                    </p>
                    <a href="<?php echo e(route('mutasi.keluar.revision', $mutasi->id_mutasi)); ?>"
                        class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-pencil-square mr-2"></i> Perbaiki & Kirim Ulang
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\mutasi\keluar\show.blade.php ENDPATH**/ ?>