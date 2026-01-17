<<<<<<< HEAD


<?php $__env->startSection('title', 'Detail Mutasi Masuk'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-8 animate-fade-in-up" x-data="{ activeTab: 0, activeDoc: null }">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="<?php echo e(route('mutasi.masuk.index')); ?>"
                class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:text-sky-600 hover:border-sky-200 transition shadow-sm">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-slate-900">Detail Mutasi Masuk</h1>
                <div class="flex items-center gap-2 mt-1 text-sm text-slate-500">
                    <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-600 font-mono">#<?php echo e($mutasi->id_mutasi); ?></span>
                    <span>•</span>
                    <span><?php echo e($mutasi->jumlah_siswa); ?> Siswa</span>
                </div>
            </div>

            <?php
                $user = auth()->user();
                $isOwner = ($user->role === 'Operator' && $mutasi->pengusul === $user->username)
                    || ($user->role === 'Sekolah' && $mutasi->npsn_sekolah_tujuan === $user->npsn);
                $canCancel = in_array($user->role, ['Operator', 'Sekolah'])
                    && $isOwner
                    && $mutasi->status === 'Menunggu Verifikasi Dinas';
            ?>
            <?php if($canCancel): ?>
                <form action="<?php echo e(route('mutasi.masuk.cancel', $mutasi->id_mutasi)); ?>" method="POST"
                    data-confirm="PERINGATAN: Apakah Anda yakin ingin MENGHAPUS usulan mutasi ini? Tindakan ini akan MENGHAPUS SELURUH data siswa dan dokumen secara PERMANEN dan tidak dapat dikembalikan."
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
                    'Disetujui Dinas' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                    'Proses TTD' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
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

        <!-- Info Surat -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Tanggal Pengajuan</p>
                    <p class="font-semibold text-slate-900 text-lg">
                        <?php echo e(\Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->translatedFormat('j F Y')); ?>

                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Nomor Surat</p>
                    <p class="font-medium text-slate-900"><?php echo e($mutasi->nomor_surat); ?></p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Pengusul</p>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
                            <i class="bi bi-person-fill text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 leading-tight">
                                <?php echo e(\App\Models\User::where('username', $mutasi->pengusul)->value('nama_pengguna') ?? $mutasi->pengusul); ?>

                            </p>
                            <p class="text-xs text-slate-500 font-mono mt-0.5">ID: <?php echo e($mutasi->pengusul); ?></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Layout Split untuk Verifikasi Dinas -->
        <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']) && $mutasi->status === 'Menunggu Verifikasi Dinas'): ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-data="verifikasiMasuk()">
                <!-- Kiri: Data Siswa & Preview Dokumen -->
                <div class="space-y-4">
                    
                    <div class="bg-gradient-to-r from-emerald-50 to-green-50 border border-emerald-200 rounded-xl p-4">
                        <h4 class="text-xs font-semibold text-emerald-800 mb-2 flex items-center gap-1">
                            <i class="bi bi-geo-alt-fill text-emerald-600"></i> Sekolah Tujuan (Penerima Mutasi)
                        </h4>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-emerald-700/70 shrink-0">Nama</span>
                                <span
                                    class="font-semibold text-emerald-900 text-right"><?php echo e($mutasi->nama_sekolah_tujuan); ?></span>
                            </div>
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-emerald-700/70">NPSN</span>
                                <span
                                    class="font-mono text-emerald-900 bg-white/50 px-1.5 py-0.5 rounded border border-emerald-200"><?php echo e($mutasi->npsn_sekolah_tujuan); ?></span>
                            </div>
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-emerald-700/70 shrink-0">Alamat</span>
                                <span
                                    class="text-emerald-900 text-right leading-snug"><?php echo e($mutasi->alamat_sekolah_tujuan ?? '-'); ?></span>
                            </div>
                        </div>
                    </div>

                    
                    <?php if($mutasi->file_surat_permohonan): ?>
                        <div class="bg-gradient-to-r from-sky-50 to-blue-50 border border-sky-200 rounded-xl p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-sky-600 rounded-lg flex items-center justify-center shadow-md shadow-sky-200">
                                        <i class="bi bi-file-earmark-text-fill text-white"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-sky-900 text-sm">Surat Permohonan Izin Mutasi</h4>
                                        <p class="text-xs text-sky-700">Diunggah oleh Pengusul</p>
                                    </div>
                                </div>
                                <button type="button"
                                    @click="activeDoc = activeDoc === 'surat_permohonan' ? null : 'surat_permohonan'"
                                    :style="activeDoc === 'surat_permohonan' ? 'background-color: #0284c7 !important; color: white !important;' : ''"
                                    :class="activeDoc === 'surat_permohonan' ? '!bg-sky-600 !text-white ring-2 ring-offset-1 ring-sky-500 font-bold shadow-md' : 'bg-white text-sky-700 hover:bg-sky-100 border border-sky-300'"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                                    <i class="bi bi-eye"></i> Lihat Surat
                                </button>
                            </div>
                            
                            <div x-show="activeDoc === 'surat_permohonan'" x-transition class="mt-4">
                                <div class="border border-slate-200 rounded-lg overflow-hidden shadow-sm">
                                    <iframe src="<?php echo e(route('dokumen.view', ['path' => $mutasi->file_surat_permohonan])); ?>"
                                        class="w-full bg-slate-100" style="height: 450px;"></iframe>
                                    <a href="<?php echo e(route('dokumen.view', ['path' => $mutasi->file_surat_permohonan])); ?>"
                                        target="_blank"
                                        class="block text-center py-2 bg-slate-50 text-sky-600 text-xs font-medium hover:bg-slate-100 transition border-t border-slate-200">
                                        <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Tab Baru
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tab Siswa -->
                    <div class="bg-white rounded-xl shadow-sm border p-4">
                        <div class="flex flex-wrap gap-2 mb-4">
                            <?php $__currentLoopData = $siswaList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $siswa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" @click="activeTab = <?php echo e($idx); ?>; activeDoc = null"
                                    :class="activeTab === <?php echo e($idx); ?> ? 'bg-primary-600 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition">
                                    Siswa <?php echo e($idx + 1); ?>

                                </button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <?php $__currentLoopData = $siswaList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $siswa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $birthDate = \Carbon\Carbon::parse($siswa->tanggal_lahir);
                                $age = $birthDate->diff(now());
                            ?>
                            <div x-show="activeTab === <?php echo e($idx); ?>" x-transition class="space-y-3">
                                
                                <div class="flex items-start gap-3 bg-gradient-to-r from-blue-50 to-slate-50 rounded-lg p-3">
                                    <div
                                        class="w-11 h-11 bg-blue-600 rounded-lg flex items-center justify-center shadow-md shadow-blue-200 shrink-0">
                                        <i class="bi bi-person-fill text-white text-lg"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-900 truncate"><?php echo e($siswa->nama_murid); ?></h4>
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-0.5 text-xs">
                                            <span
                                                class="inline-flex items-center gap-1 text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded font-mono">
                                                <i class="bi bi-hash"></i><?php echo e($siswa->nisn); ?>

                                            </span>
                                            <span class="text-gray-400">•</span>
                                            <span class="text-gray-600"><?php echo e($siswa->kelas); ?></span>
                                            <span class="text-gray-400">•</span>
                                            <span
                                                class="text-gray-600"><?php echo e($siswa->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="bg-white border border-slate-200 rounded-lg p-2">
                                        <p class="text-gray-500 uppercase tracking-wider mb-0.5" style="font-size: 10px;">TTL</p>
                                        <p class="font-medium text-gray-900"><?php echo e($siswa->tempat_lahir); ?>,
                                            <?php echo e($birthDate->translatedFormat('d F Y')); ?>

                                        </p>
                                    </div>
                                    <div class="bg-white border border-slate-200 rounded-lg p-2">
                                        <p class="text-gray-500 uppercase tracking-wider mb-0.5" style="font-size: 10px;">Usia</p>
                                        <p class="font-medium text-gray-900"><?php echo e($age->y); ?>th <?php echo e($age->m); ?>bln <?php echo e($age->d); ?>hr</p>
                                    </div>
                                </div>

                                
                                <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-lg p-3">
                                    <h5 class="text-xs font-semibold text-amber-800 mb-2 flex items-center gap-1">
                                        <i class="bi bi-building text-amber-600"></i> Sekolah Asal
                                    </h5>
                                    <div class="space-y-1.5 text-xs">
                                        <div class="flex justify-between items-start gap-2">
                                            <span class="text-amber-700/70 shrink-0">Nama</span>
                                            <span
                                                class="font-semibold text-amber-900 text-right"><?php echo e($siswa->nama_sekolah_asal); ?></span>
                                        </div>
                                        <div class="flex justify-between items-center gap-2">
                                            <span class="text-amber-700/70">NPSN</span>
                                            <span
                                                class="font-mono text-amber-900 bg-white/50 px-1.5 py-0.5 rounded border border-amber-200"><?php echo e($siswa->npsn_sekolah_asal ?? '-'); ?></span>
                                        </div>
                                        <div class="flex justify-between items-start gap-2">
                                            <span class="text-amber-700/70 shrink-0">Lokasi</span>
                                            <span class="text-amber-900 text-right"><?php echo e($siswa->kabupaten_kota_asal); ?>,
                                                <?php echo e($siswa->provinsi_asal); ?></span>
                                        </div>
                                        <div class="flex justify-between items-start gap-2">
                                            <span class="text-amber-700/70 shrink-0">Alamat</span>
                                            <span
                                                class="text-amber-900 text-right leading-snug"><?php echo e(Str::limit($siswa->alamat_sekolah_asal, 60)); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dokumen Siswa -->
                                <div class="space-y-2">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Dokumen Siswa</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <?php
                                            $docs = [
                                                ['key' => "siswa_{$idx}_pindah", 'label' => 'Surat Pindah', 'file' => $siswa->file_surat_pindah, 'color' => 'blue'],
                                                ['key' => "siswa_{$idx}_dapodik", 'label' => 'Dapodik', 'file' => $siswa->file_surat_dapodik, 'color' => 'blue'],
                                                ['key' => "siswa_{$idx}_rekom", 'label' => 'Rekomendasi', 'file' => $siswa->file_rekomendasi, 'color' => 'blue'],
                                                ['key' => "siswa_{$idx}_ijazah", 'label' => 'Ijazah SD', 'file' => $siswa->file_ijazah_sd, 'color' => 'blue'],
                                                ['key' => "siswa_{$idx}_rapor", 'label' => 'Rapor', 'file' => $siswa->file_rapor, 'color' => 'blue'],
                                                ['key' => "siswa_{$idx}_rekam_didik", 'label' => 'Rekam Didik', 'file' => $siswa->file_rekam_didik, 'color' => 'blue'],
                                            ];
                                        ?>
                                        <?php $__currentLoopData = $docs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php if($doc['file']): ?>
                                                <button type="button"
                                                    @click="activeDoc = activeDoc === '<?php echo e($doc['key']); ?>' ? null : '<?php echo e($doc['key']); ?>'"
                                                    :class="activeDoc === '<?php echo e($doc['key']); ?>' ? 'bg-blue-600 text-white ring-2 ring-offset-1 ring-blue-500' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'"
                                                    class="px-3 py-2 rounded-lg text-xs font-medium transition flex items-center justify-center gap-2 border border-transparent">
                                                    <i class="bi bi-file-earmark-pdf"></i> <?php echo e($doc['label']); ?>

                                                </button>
                                            <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>

                                <!-- Preview Dokumen -->
                                <?php $__currentLoopData = $docs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($doc['file']): ?>
                                        <div x-show="activeDoc === '<?php echo e($doc['key']); ?>'" x-transition
                                            class="mt-4 border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                                            <iframe src="<?php echo e(route('dokumen.view', ['path' => $doc['file']])); ?>"
                                                class="w-full h-[500px] bg-slate-100"></iframe>
                                            <a href="<?php echo e(route('dokumen.view', ['path' => $doc['file']])); ?>" target="_blank"
                                                class="block text-center py-3 bg-slate-50 text-sky-600 text-sm font-medium hover:bg-slate-100 transition border-t border-slate-200">
                                                <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Layar Penuh (Tab Baru)
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <!-- Kanan: Form Verifikasi -->
                <div class="bg-white rounded-xl shadow-sm border p-6 h-fit sticky top-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="bi bi-clipboard-check text-primary-600 mr-2"></i>
                        Verifikasi Berkas
                    </h2>

                    <form action="<?php echo e(route('mutasi.masuk.verify', $mutasi->id_mutasi)); ?>" method="POST" class="space-y-4">
                        <?php echo csrf_field(); ?>

                        
                        <div class="border border-sky-200 bg-sky-50/50 rounded-lg p-3">
                            <h4 class="font-medium text-sky-900 mb-2 text-sm flex items-center gap-1">
                                <i class="bi bi-file-earmark-text text-sky-600"></i>
                                Dokumen Permohonan
                            </h4>
                            <label class="flex items-center p-2 rounded hover:bg-sky-100 cursor-pointer">
                                <input type="checkbox" name="verifikasi_surat_permohonan" value="1"
                                    class="rounded text-primary-600 focus:ring-primary-500 mr-3" <?php echo e($mutasi->file_surat_permohonan ? 'checked' : 'disabled'); ?>

                                    @change="updateNote($el.checked, 'Surat Permohonan Izin Mutasi')">
                                <span class="text-sm text-gray-700">Surat Permohonan Izin Mutasi</span>
                                <?php if(!$mutasi->file_surat_permohonan): ?>
                                    <span class="ml-auto text-xs text-red-500 italic">Tidak ada file</span>
                                <?php endif; ?>
                            </label>
                        </div>

                        <!-- Checklist per Siswa -->
                        <?php $__currentLoopData = $siswaList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $siswa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="border rounded-lg p-3" x-show="activeTab === <?php echo e($idx); ?>" x-transition>
                                <h4 class="font-medium text-gray-900 mb-2 text-sm">
                                    <i class="bi bi-person text-blue-600 mr-1"></i>
                                    <?php echo e($siswa->nama_murid); ?>

                                </h4>
                                <?php
                                    $siswaDocList = [
                                        ['key' => "siswa_{$idx}_pindah", 'label' => 'Surat Pindah', 'file' => $siswa->file_surat_pindah],
                                        ['key' => "siswa_{$idx}_dapodik", 'label' => 'Surat Dapodik', 'file' => $siswa->file_surat_dapodik],
                                        ['key' => "siswa_{$idx}_rekom", 'label' => 'Rekomendasi', 'file' => $siswa->file_rekomendasi],
                                        ['key' => "siswa_{$idx}_ijazah", 'label' => 'Ijazah SD', 'file' => $siswa->file_ijazah_sd],
                                        ['key' => "siswa_{$idx}_rapor", 'label' => 'Rapor', 'file' => $siswa->file_rapor],
                                        ['key' => "siswa_{$idx}_rekam_didik", 'label' => 'Rekam Didik', 'file' => $siswa->file_rekam_didik],
                                    ];
                                ?>
                                <div class="grid grid-cols-1 gap-2">
                                    <?php $__currentLoopData = $siswaDocList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <label class="flex items-center p-2 rounded hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox" name="verifikasi[<?php echo e($siswa->id); ?>][<?php echo e($doc['key']); ?>]" value="1"
                                                class="rounded text-primary-600 focus:ring-primary-500 mr-3" <?php echo e($doc['file'] ? 'checked' : 'disabled'); ?>

                                                @change="updateNote($el.checked, '<?php echo e($doc['label']); ?> (<?php echo e($siswa->nama_murid); ?>)')">
                                            <span class="text-sm text-gray-700"><?php echo e($doc['label']); ?></span>
                                            <?php if(!$doc['file']): ?>
                                                <span class="ml-auto text-xs text-red-500 italic">Tidak ada file</span>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                            <textarea name="catatan" rows="3" x-model="catatan"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                                placeholder="Tambahkan catatan jika perlu perbaikan..."></textarea>
                        </div>

                        <div class="flex flex-col gap-2 pt-2">
                            <button type="submit" name="action" value="approve"
                                class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition shadow-sm">
                                <i class="bi bi-check-lg mr-2"></i> Setujui Berkas
                            </button>
                            <button type="submit" name="action" value="reject"
                                class="w-full py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-medium transition shadow-sm">
                                <i class="bi bi-x-lg mr-2"></i> Minta Perbaikan
                            </button>
                        </div>
                    </form>
                    <script>

                        function verif                                                ikasiMasuk() {
                            return {
                                activeTab: 0,
                                activeDoc: null,
                                catatan: '',
                                updateNote(checked, label) {
                                    // Default message to add
                                    const errorSuffix = "tidak sesuai,";
                                    const defaultMsg = `- ${label} ${errorSuffix}`;

                                    if (!checked) {
                                        // Unchecked: Add error note
                                        // We append default message. User can edit it later.
                                        if (!this.catatan.includes(defaultMsg)) {
                                            this.catatan = this.catatan ? this.catatan + '\n' + defaultMsg : defaultMsg;
                                        }
                                    } else {
                                        // Checked: Remove ANY line related to this label (even if edited)
                                        // We look for lines starting with "- {Label}"

                                        const escapedLabel = label.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                                        // Regex: Start of line, hyphen, space, label, any char until end of line
                                        const regex = new RegExp(`^-\\s+${escapedLabel}.*$`, 'gm');

                                        this.catatan = this.catatan.replace(regex, '');

                                        // Clean up empty lines
                                        this.catatan = this.catatan.replace(/^\s*[\r\n]/gm, '').trim();
                                    }
                                }
                            }
                        }
                    </script>
                </div>
            </div>
        <?php else: ?>
            <!-- Normal View -->
            <div class="space-y-4">
                
                <?php if($mutasi->file_surat_permohonan): ?>
                    <div
                        class="bg-gradient-to-r from-sky-50 to-blue-50 border border-sky-200 rounded-xl p-4 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-sky-600 rounded-lg flex items-center justify-center shadow-md shadow-sky-200">
                                <i class="bi bi-file-earmark-text-fill text-white"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-sky-900 text-sm">Surat Permohonan Izin Mutasi</h4>
                                <p class="text-xs text-sky-700">Dokumen Utama Pengajuan</p>
                            </div>
                        </div>
                        <button type="button"
                            @click="activeDoc = activeDoc === 'surat_permohonan_normal' ? null : 'surat_permohonan_normal'"
                            :style="activeDoc === 'surat_permohonan_normal' ? 'background-color: #0284c7 !important; color: white !important;' : ''"
                            :class="activeDoc === 'surat_permohonan_normal' ? '!bg-sky-600 !text-white ring-2 ring-offset-1 ring-sky-500 font-bold shadow-md' : 'bg-white text-sky-700 hover:bg-sky-100 border border-sky-300'"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                            <i class="bi bi-eye"></i> Lihat Surat
                        </button>
                    </div>
                    
                    <div x-show="activeDoc === 'surat_permohonan_normal'" x-transition
                        class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <iframe src="<?php echo e(route('dokumen.view', ['path' => $mutasi->file_surat_permohonan])); ?>"
                            class="w-full bg-slate-100" style="height: 500px;"></iframe>
                    </div>
                <?php endif; ?>

                <!-- Data Siswa dengan Tab -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 sm:p-6">
                    <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                            <i class="bi bi-people-fill text-blue-600 text-xl"></i>
                        </div>
                        Data Siswa
                    </h2>

                    <?php if(count($siswaList) > 1): ?>
                        <div class="flex flex-wrap gap-2 mb-4 border-b pb-4">
                            <?php $__currentLoopData = $siswaList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $siswa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" @click="activeTab = <?php echo e($idx); ?>"
                                    :class="activeTab === <?php echo e($idx); ?> ? 'bg-primary-600 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                                    class="px-4 py-2 rounded-lg font-medium transition text-sm">
                                    Siswa <?php echo e($idx + 1); ?>

                                </button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>

                    
                    <?php if($mutasi->status === 'Perlu Perbaikan'): ?>
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3 mb-6">
                            <i class="bi bi-exclamation-triangle-fill text-amber-500 text-xl flex-shrink-0"></i>
                            <div>
                                <h4 class="font-bold text-amber-800 text-sm">Perlu Perbaikan</h4>
                                <p class="text-sm text-amber-700 mt-1">Mohon perbaiki data atau dokumen sesuai catatan dari
                                    verifikator. Cek catatan pada setiap siswa atau dokumen.</p>
                                <?php if($mutasi->catatan_berkas): ?>
                                    <div class="mt-2 bg-white/60 p-2 rounded text-xs font-mono text-amber-900 border border-amber-100">
                                        <?php echo e($mutasi->catatan_berkas); ?>

                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php $__currentLoopData = $siswaList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $siswa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $birthDate = \Carbon\Carbon::parse($siswa->tanggal_lahir);
                            $age = $birthDate->diff(now());
                        ?>
                        <div x-show="activeTab === <?php echo e($idx); ?>" x-transition class="space-y-6">

                            
                            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">

                                
                                <div class="p-6 bg-slate-50 border-b border-slate-200 flex items-start gap-5">
                                    <div
                                        class="w-16 h-16 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg text-white shrink-0">
                                        <i class="bi bi-person-fill text-3xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-bold text-xl text-slate-900"><?php echo e($siswa->nama_murid); ?></h3>
                                        <div class="flex flex-wrap items-center gap-4 mt-2">
                                            <span class="inline-flex items-center gap-2 text-slate-700 font-medium text-sm">
                                                <i class="bi bi-gender-ambiguous text-blue-600"></i>
                                                <?php echo e($siswa->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan'); ?>

                                            </span>
                                            <span class="inline-flex items-center gap-2 text-slate-700 font-medium text-sm">
                                                <i class="bi bi-mortarboard-fill text-blue-600"></i>
                                                Kelas <?php echo e($siswa->kelas); ?>

                                            </span>
                                            <span class="inline-flex items-center gap-2 text-slate-700 font-medium text-sm">
                                                <i class="bi bi-upc-scan text-blue-600"></i>
                                                NISN: <span class="font-bold font-mono"><?php echo e($siswa->nisn); ?></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                
                                
                                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                                    
                                    <div class="space-y-6">
                                        <h4 class="font-bold text-slate-900 border-b border-slate-200 pb-2 mb-4">Biodata Siswa</h4>

                                        <div class="grid grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-500 mb-1">Tempat, Tanggal
                                                    Lahir</label>
                                                <div class="text-base font-medium text-slate-900">
                                                    <?php echo e($siswa->tempat_lahir); ?><br><?php echo e($birthDate->translatedFormat('j F Y')); ?>

                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-500 mb-1">Usia Saat Ini</label>
                                                <div class="text-base font-medium text-slate-900">
                                                    <?php echo e($age->y); ?> Tahun <?php echo e($age->m); ?> Bulan
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-slate-500 mb-1">Tanggal Diterima</label>
                                            <div
                                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-lg text-base font-bold">
                                                <i class="bi bi-calendar-check text-emerald-600"></i>
                                                <?php echo e(\Carbon\Carbon::parse($siswa->tanggal_diterima)->translatedFormat('j F Y')); ?>

                                            </div>
                                        </div>
                                    </div>

                                    
                                    <div class="space-y-6">
                                        <h4 class="font-bold text-slate-900 border-b border-slate-200 pb-2 mb-4">Asal Sekolah</h4>

                                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
                                            <div class="mb-4">
                                                <label class="block text-sm font-semibold text-amber-800 mb-1">Nama Sekolah</label>
                                                <div class="text-lg font-bold text-slate-900">
                                                    <?php echo e($siswa->nama_sekolah_asal); ?>

                                                </div>
                                                <div class="mt-1 flex items-center gap-2">
                                                    <span
                                                        class="text-sm font-bold font-mono text-slate-700 bg-white/50 px-2 py-0.5 rounded border border-amber-100">NPSN:
                                                        <?php echo e($siswa->npsn_sekolah_asal ?? '-'); ?></span>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-amber-800 mb-1">Alamat
                                                    Lengkap</label>
                                                <div class="text-sm font-medium text-slate-800 leading-relaxed">
                                                    <?php echo e($siswa->alamat_sekolah_asal); ?>

                                                    <div
                                                        class="mt-1 text-slate-600 pt-2 border-t border-amber-200/50 flex items-center gap-1.5">
                                                        <i class="bi bi-geo-alt"></i> <?php echo e($siswa->kabupaten_kota_asal); ?>,
                                                        <?php echo e($siswa->provinsi_asal); ?>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm p-6">
                                <h4 class="font-bold text-slate-900 border-b border-slate-200 pb-3 mb-4 flex items-center gap-2">
                                    <i class="bi bi-files text-blue-600"></i> Dokumen Lampiran
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <?php
                                        $docList = [
                                            ['key' => "normal_{$idx}_pindah", 'label' => 'Surat Pindah', 'file' => $siswa->file_surat_pindah, 'color' => 'rose'],
                                            ['key' => "normal_{$idx}_dapodik", 'label' => 'Dapodik', 'file' => $siswa->file_surat_dapodik, 'color' => 'blue'],
                                            ['key' => "normal_{$idx}_rekom", 'label' => 'Rekomendasi', 'file' => $siswa->file_rekomendasi, 'color' => 'purple'],
                                            ['key' => "normal_{$idx}_ijazah", 'label' => 'Ijazah SD', 'file' => $siswa->file_ijazah_sd, 'color' => 'emerald'],
                                            ['key' => "normal_{$idx}_rapor", 'label' => 'Rapor', 'file' => $siswa->file_rapor, 'color' => 'orange'],
                                            ['key' => "normal_{$idx}_rekam_didik", 'label' => 'Rekam Didik', 'file' => $siswa->file_rekam_didik, 'color' => 'cyan'],
                                        ];
                                    ?>

                                    <?php $__currentLoopData = $docList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($doc['file']): ?>
                                            <button @click="activeDoc = activeDoc === '<?php echo e($doc['key']); ?>' ? null : '<?php echo e($doc['key']); ?>'"
                                                :class="activeDoc === '<?php echo e($doc['key']); ?>' 
                                                                            ? 'ring-2 ring-<?php echo e($doc['color']); ?>-500 bg-<?php echo e($doc['color']); ?>-50' 
                                                                            : 'bg-white hover:bg-slate-50 border-slate-200'"
                                                class="flex items-center gap-4 p-4 rounded-xl border transition group text-left w-full shadow-sm hover:shadow-md">

                                                <div
                                                    class="w-12 h-12 rounded-lg bg-<?php echo e($doc['color']); ?>-100 text-<?php echo e($doc['color']); ?>-600 flex items-center justify-center shrink-0">
                                                    <i class="bi bi-file-earmark-pdf-fill text-2xl"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <p
                                                        class="text-sm font-bold text-slate-500 mb-0.5 group-hover:text-<?php echo e($doc['color']); ?>-600">
                                                        Dokumen</p>
                                                    <p class="font-bold text-slate-900 text-base truncate"><?php echo e($doc['label']); ?></p>
                                                </div>
                                                <div class="text-slate-400 group-hover:text-<?php echo e($doc['color']); ?>-600">
                                                    <i class="bi bi-eye-fill text-xl" x-show="activeDoc !== '<?php echo e($doc['key']); ?>'"></i>
                                                    <i class="bi bi-chevron-up text-xl font-bold"
                                                        x-show="activeDoc === '<?php echo e($doc['key']); ?>'"></i>
                                                </div>
                                            </button>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>

                            
                            <?php $__currentLoopData = $docList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($doc['file']): ?>
                                    <div x-show="activeDoc === '<?php echo e($doc['key']); ?>'" x-transition
                                        class="border-2 border-slate-300 rounded-xl overflow-hidden shadow-lg bg-white scroll-mt-24"
                                        x-ref="doc_<?php echo e($doc['key']); ?>">
                                        <div class="flex items-center justify-between px-6 py-4 bg-slate-100 border-b border-slate-200">
                                            <h5 class="font-bold text-lg text-slate-800"><?php echo e($doc['label']); ?> - <?php echo e($siswa->nama_murid); ?></h5>
                                            <button @click="activeDoc = null"
                                                class="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 flex items-center justify-center text-slate-600 transition">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <iframe src="<?php echo e(route('dokumen.view', ['path' => $doc['file']])); ?>"
                                            class="w-full h-[700px] bg-slate-200"></iframe>
                                        <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-center">
                                            <a href="<?php echo e(route('dokumen.view', ['path' => $doc['file']])); ?>" target="_blank"
                                                class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition shadow-md">
                                                <i class="bi bi-box-arrow-up-right"></i> Buka Dokumen Layar Penuh
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <?php if(count($siswaList) > 1): ?>
                        <div class="flex items-center justify-between pt-4 border-t mt-4">
                            <button @click="if(activeTab > 0) activeTab--" :disabled="activeTab === 0"
                                :class="activeTab === 0 ? 'opacity-50' : 'hover:bg-gray-200'"
                                class="px-4 py-2 bg-gray-100 rounded-lg text-sm">← Sebelumnya</button>
                            <span class="text-sm text-gray-500">Siswa <span x-text="activeTab + 1"></span> /
                                <?php echo e(count($siswaList)); ?></span>
                            <button @click="if(activeTab < <?php echo e(count($siswaList) - 1); ?>) activeTab++"
                                :disabled="activeTab === <?php echo e(count($siswaList) - 1); ?>"
                                :class="activeTab === <?php echo e(count($siswaList) - 1); ?> ? 'opacity-50' : 'hover:bg-gray-200'"
                                class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Berikutnya →</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Surat Izin -->
        <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']) && $mutasi->status === 'Disetujui Dinas' && !($mutasi->nomor_surat_izin ?? null)): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-blue-900 mb-4"><i
                        class="bi bi-file-earmark-text text-blue-600 mr-2"></i>Terbitkan Surat Izin</h2>
                <form action="<?php echo e(route('mutasi.masuk.issue-letter', $mutasi->id_mutasi)); ?>" method="POST" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-blue-900 mb-1">Nomor Surat Izin</label>
                            <input type="text" name="nomor_surat_izin" required
                                class="w-full rounded-lg border-blue-200 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Contoh: 421.3/123/Disdik">
                        </div>
                    </div>
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition shadow-sm">
                        <i class="bi bi-send-check mr-2"></i> Terbitkan Surat
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Info Surat Izin -->
        <?php if($mutasi->nomor_surat_izin): ?>
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
                <div class="grid grid-cols-2 gap-8 items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-emerald-900 mb-2">
                            <i class="bi bi-check-circle-fill text-emerald-600 mr-2"></i> Surat Izin Terbit
                        </h3>
                        <p class="text-emerald-700">Nomor: <span
                                class="font-mono font-bold"><?php echo e($mutasi->nomor_surat_izin); ?></span>
                        </p>
                        <p class="text-sm text-emerald-600 mt-1">Diterbitkan pada:
                            <?php echo e(\Carbon\Carbon::parse($mutasi->tanggal_surat_izin)->translatedFormat('d F Y')); ?>

                        </p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin'])): ?>
                            <a href="<?php echo e(route('mutasi.masuk.print-letter', $mutasi->id_mutasi)); ?>" target="_blank"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition shadow-sm">
                                <i class="bi bi-printer mr-2"></i> Cetak Surat Izin
                            </a>
                        <?php endif; ?>
                        <!-- Route missing/not implemented
                                    <a href="" target="_blank"
                                        class="px-4 py-2 bg-white text-emerald-700 border border-emerald-200 hover:bg-emerald-50 rounded-lg font-medium transition shadow-sm">
                                        <i class="bi bi-printer mr-2"></i> Cetak Surat Keterangan
                                    </a>
                                    -->
                    </div>
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
                    <form action="<?php echo e(route('mutasi.masuk.upload-arsip', $mutasi->id_mutasi)); ?>" method="POST"
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
                        || ($user->role === 'Sekolah' && $user->npsn === $mutasi->npsn_sekolah_tujuan);
                    $canRevise = in_array($user->role, ['Operator', 'Sekolah'])
                        && $isOwner
                        && in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan']);
                ?>

                <?php if($canRevise): ?>
                    <a href="<?php echo e(route('mutasi.masuk.revision', $mutasi->id_mutasi)); ?>"
                        class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-pencil-square mr-2"></i> Perbaiki & Kirim Ulang
                    </a>
                <?php endif; ?>
            </div>
        <?php elseif(in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan'])): ?>
            
            <?php
                $user = auth()->user();
                $isOwner = ($user->username === $mutasi->pengusul)
                    || ($user->role === 'Sekolah' && $user->npsn === $mutasi->npsn_sekolah_tujuan);
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
                        : 'Data mutasi Anda perlu diperbaiki. Silakan review dan kirim ulang.'); ?>

                    </p>
                    <a href="<?php echo e(route('mutasi.masuk.revision', $mutasi->id_mutasi)); ?>"
                        class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-pencil-square mr-2"></i> Perbaiki & Kirim Ulang
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
=======


<?php $__env->startSection('title', 'Detail Mutasi Masuk'); ?>

<?php $__env->startSection('content'); ?>
    <div class="space-y-8 animate-fade-in-up" x-data="{ activeTab: 0, activeDoc: null }">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <a href="<?php echo e(route('mutasi.masuk.index')); ?>"
                class="w-10 h-10 flex items-center justify-center bg-white border border-slate-200 rounded-xl text-slate-500 hover:text-sky-600 hover:border-sky-200 transition shadow-sm">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-slate-900">Detail Mutasi Masuk</h1>
                <div class="flex items-center gap-2 mt-1 text-sm text-slate-500">
                    <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-600 font-mono">#<?php echo e($mutasi->id_mutasi); ?></span>
                    <span>•</span>
                    <span><?php echo e($mutasi->jumlah_siswa); ?> Siswa</span>
                </div>
            </div>

            <?php
                $user = auth()->user();
                $isOwner = ($user->role === 'Operator' && $mutasi->pengusul === $user->username)
                    || ($user->role === 'Sekolah' && $mutasi->npsn_sekolah_tujuan === $user->npsn);
                $canCancel = in_array($user->role, ['Operator', 'Sekolah'])
                    && $isOwner
                    && $mutasi->status === 'Menunggu Verifikasi Dinas';
            ?>
            <?php if($canCancel): ?>
                <form action="<?php echo e(route('mutasi.masuk.cancel', $mutasi->id_mutasi)); ?>" method="POST"
                    data-confirm="PERINGATAN: Apakah Anda yakin ingin MENGHAPUS usulan mutasi ini? Tindakan ini akan MENGHAPUS SELURUH data siswa dan dokumen secara PERMANEN dan tidak dapat dikembalikan."
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
                    'Disetujui Dinas' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                    'Proses TTD' => 'bg-indigo-100 text-indigo-800 border border-indigo-200',
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

        <!-- Info Surat -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Tanggal Pengajuan</p>
                    <p class="font-semibold text-slate-900 text-lg">
                        <?php echo e(\Carbon\Carbon::parse($mutasi->tanggal_pengajuan)->translatedFormat('j F Y')); ?>

                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Nomor Surat</p>
                    <p class="font-medium text-slate-900"><?php echo e($mutasi->nomor_surat); ?></p>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Pengusul</p>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
                            <i class="bi bi-person-fill text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 leading-tight">
                                <?php echo e(\App\Models\User::where('username', $mutasi->pengusul)->value('nama_pengguna') ?? $mutasi->pengusul); ?>

                            </p>
                            <p class="text-xs text-slate-500 font-mono mt-0.5">ID: <?php echo e($mutasi->pengusul); ?></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Layout Split untuk Verifikasi Dinas -->
        <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']) && $mutasi->status === 'Menunggu Verifikasi Dinas'): ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-data="verifikasiMasuk()">
                <!-- Kiri: Data Siswa & Preview Dokumen -->
                <div class="space-y-4">
                    
                    <div class="bg-gradient-to-r from-emerald-50 to-green-50 border border-emerald-200 rounded-xl p-4">
                        <h4 class="text-xs font-semibold text-emerald-800 mb-2 flex items-center gap-1">
                            <i class="bi bi-geo-alt-fill text-emerald-600"></i> Sekolah Tujuan (Penerima Mutasi)
                        </h4>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-emerald-700/70 shrink-0">Nama</span>
                                <span
                                    class="font-semibold text-emerald-900 text-right"><?php echo e($mutasi->nama_sekolah_tujuan); ?></span>
                            </div>
                            <div class="flex justify-between items-center gap-2">
                                <span class="text-emerald-700/70">NPSN</span>
                                <span
                                    class="font-mono text-emerald-900 bg-white/50 px-1.5 py-0.5 rounded border border-emerald-200"><?php echo e($mutasi->npsn_sekolah_tujuan); ?></span>
                            </div>
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-emerald-700/70 shrink-0">Alamat</span>
                                <span
                                    class="text-emerald-900 text-right leading-snug"><?php echo e($mutasi->alamat_sekolah_tujuan ?? '-'); ?></span>
                            </div>
                        </div>
                    </div>

                    
                    <?php if($mutasi->file_surat_permohonan): ?>
                        <div class="bg-gradient-to-r from-sky-50 to-blue-50 border border-sky-200 rounded-xl p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-sky-600 rounded-lg flex items-center justify-center shadow-md shadow-sky-200">
                                        <i class="bi bi-file-earmark-text-fill text-white"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-sky-900 text-sm">Surat Permohonan Izin Mutasi</h4>
                                        <p class="text-xs text-sky-700">Diunggah oleh Pengusul</p>
                                    </div>
                                </div>
                                <button type="button"
                                    @click="activeDoc = activeDoc === 'surat_permohonan' ? null : 'surat_permohonan'"
                                    :style="activeDoc === 'surat_permohonan' ? 'background-color: #0284c7 !important; color: white !important;' : ''"
                                    :class="activeDoc === 'surat_permohonan' ? '!bg-sky-600 !text-white ring-2 ring-offset-1 ring-sky-500 font-bold shadow-md' : 'bg-white text-sky-700 hover:bg-sky-100 border border-sky-300'"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                                    <i class="bi bi-eye"></i> Lihat Surat
                                </button>
                            </div>
                            
                            <div x-show="activeDoc === 'surat_permohonan'" x-transition class="mt-4">
                                <div class="border border-slate-200 rounded-lg overflow-hidden shadow-sm">
                                    <iframe src="<?php echo e(route('dokumen.view', ['path' => $mutasi->file_surat_permohonan])); ?>"
                                        class="w-full bg-slate-100" style="height: 450px;"></iframe>
                                    <a href="<?php echo e(route('dokumen.view', ['path' => $mutasi->file_surat_permohonan])); ?>"
                                        target="_blank"
                                        class="block text-center py-2 bg-slate-50 text-sky-600 text-xs font-medium hover:bg-slate-100 transition border-t border-slate-200">
                                        <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Tab Baru
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tab Siswa -->
                    <div class="bg-white rounded-xl shadow-sm border p-4">
                        <div class="flex flex-wrap gap-2 mb-4">
                            <?php $__currentLoopData = $siswaList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $siswa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" @click="activeTab = <?php echo e($idx); ?>; activeDoc = null"
                                    :class="activeTab === <?php echo e($idx); ?> ? 'bg-primary-600 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition">
                                    Siswa <?php echo e($idx + 1); ?>

                                </button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <?php $__currentLoopData = $siswaList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $siswa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $birthDate = \Carbon\Carbon::parse($siswa->tanggal_lahir);
                                $age = $birthDate->diff(now());
                            ?>
                            <div x-show="activeTab === <?php echo e($idx); ?>" x-transition class="space-y-3">
                                
                                <div class="flex items-start gap-3 bg-gradient-to-r from-blue-50 to-slate-50 rounded-lg p-3">
                                    <div
                                        class="w-11 h-11 bg-blue-600 rounded-lg flex items-center justify-center shadow-md shadow-blue-200 shrink-0">
                                        <i class="bi bi-person-fill text-white text-lg"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-900 truncate"><?php echo e($siswa->nama_murid); ?></h4>
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-0.5 text-xs">
                                            <span
                                                class="inline-flex items-center gap-1 text-blue-700 bg-blue-100 px-1.5 py-0.5 rounded font-mono">
                                                <i class="bi bi-hash"></i><?php echo e($siswa->nisn); ?>

                                            </span>
                                            <span class="text-gray-400">•</span>
                                            <span class="text-gray-600"><?php echo e($siswa->kelas); ?></span>
                                            <span class="text-gray-400">•</span>
                                            <span
                                                class="text-gray-600"><?php echo e($siswa->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="bg-white border border-slate-200 rounded-lg p-2">
                                        <p class="text-gray-500 uppercase tracking-wider mb-0.5" style="font-size: 10px;">TTL</p>
                                        <p class="font-medium text-gray-900"><?php echo e($siswa->tempat_lahir); ?>,
                                            <?php echo e($birthDate->translatedFormat('d F Y')); ?>

                                        </p>
                                    </div>
                                    <div class="bg-white border border-slate-200 rounded-lg p-2">
                                        <p class="text-gray-500 uppercase tracking-wider mb-0.5" style="font-size: 10px;">Usia</p>
                                        <p class="font-medium text-gray-900"><?php echo e($age->y); ?>th <?php echo e($age->m); ?>bln <?php echo e($age->d); ?>hr</p>
                                    </div>
                                </div>

                                
                                <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-lg p-3">
                                    <h5 class="text-xs font-semibold text-amber-800 mb-2 flex items-center gap-1">
                                        <i class="bi bi-building text-amber-600"></i> Sekolah Asal
                                    </h5>
                                    <div class="space-y-1.5 text-xs">
                                        <div class="flex justify-between items-start gap-2">
                                            <span class="text-amber-700/70 shrink-0">Nama</span>
                                            <span
                                                class="font-semibold text-amber-900 text-right"><?php echo e($siswa->nama_sekolah_asal); ?></span>
                                        </div>
                                        <div class="flex justify-between items-center gap-2">
                                            <span class="text-amber-700/70">NPSN</span>
                                            <span
                                                class="font-mono text-amber-900 bg-white/50 px-1.5 py-0.5 rounded border border-amber-200"><?php echo e($siswa->npsn_sekolah_asal ?? '-'); ?></span>
                                        </div>
                                        <div class="flex justify-between items-start gap-2">
                                            <span class="text-amber-700/70 shrink-0">Lokasi</span>
                                            <span class="text-amber-900 text-right"><?php echo e($siswa->kabupaten_kota_asal); ?>,
                                                <?php echo e($siswa->provinsi_asal); ?></span>
                                        </div>
                                        <div class="flex justify-between items-start gap-2">
                                            <span class="text-amber-700/70 shrink-0">Alamat</span>
                                            <span
                                                class="text-amber-900 text-right leading-snug"><?php echo e(Str::limit($siswa->alamat_sekolah_asal, 60)); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dokumen Siswa -->
                                <div class="space-y-2">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Dokumen Siswa</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <?php
                                            $docs = [
                                                ['key' => "siswa_{$idx}_pindah", 'label' => 'Surat Pindah', 'file' => $siswa->file_surat_pindah, 'color' => 'blue'],
                                                ['key' => "siswa_{$idx}_dapodik", 'label' => 'Dapodik', 'file' => $siswa->file_surat_dapodik, 'color' => 'blue'],
                                                ['key' => "siswa_{$idx}_rekom", 'label' => 'Rekomendasi', 'file' => $siswa->file_rekomendasi, 'color' => 'blue'],
                                                ['key' => "siswa_{$idx}_ijazah", 'label' => 'Ijazah SD', 'file' => $siswa->file_ijazah_sd, 'color' => 'blue'],
                                                ['key' => "siswa_{$idx}_rapor", 'label' => 'Rapor', 'file' => $siswa->file_rapor, 'color' => 'blue'],
                                                ['key' => "siswa_{$idx}_rekam_didik", 'label' => 'Rekam Didik', 'file' => $siswa->file_rekam_didik, 'color' => 'blue'],
                                            ];
                                        ?>
                                        <?php $__currentLoopData = $docs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php if($doc['file']): ?>
                                                <button type="button"
                                                    @click="activeDoc = activeDoc === '<?php echo e($doc['key']); ?>' ? null : '<?php echo e($doc['key']); ?>'"
                                                    :class="activeDoc === '<?php echo e($doc['key']); ?>' ? 'bg-blue-600 text-white ring-2 ring-offset-1 ring-blue-500' : 'bg-blue-50 text-blue-700 hover:bg-blue-100'"
                                                    class="px-3 py-2 rounded-lg text-xs font-medium transition flex items-center justify-center gap-2 border border-transparent">
                                                    <i class="bi bi-file-earmark-pdf"></i> <?php echo e($doc['label']); ?>

                                                </button>
                                            <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>

                                <!-- Preview Dokumen -->
                                <?php $__currentLoopData = $docs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($doc['file']): ?>
                                        <div x-show="activeDoc === '<?php echo e($doc['key']); ?>'" x-transition
                                            class="mt-4 border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                                            <iframe src="<?php echo e(route('dokumen.view', ['path' => $doc['file']])); ?>"
                                                class="w-full h-[500px] bg-slate-100"></iframe>
                                            <a href="<?php echo e(route('dokumen.view', ['path' => $doc['file']])); ?>" target="_blank"
                                                class="block text-center py-3 bg-slate-50 text-sky-600 text-sm font-medium hover:bg-slate-100 transition border-t border-slate-200">
                                                <i class="bi bi-box-arrow-up-right mr-1"></i> Buka Layar Penuh (Tab Baru)
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <!-- Kanan: Form Verifikasi -->
                <div class="bg-white rounded-xl shadow-sm border p-6 h-fit sticky top-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="bi bi-clipboard-check text-primary-600 mr-2"></i>
                        Verifikasi Berkas
                    </h2>

                    <form action="<?php echo e(route('mutasi.masuk.verify', $mutasi->id_mutasi)); ?>" method="POST" class="space-y-4">
                        <?php echo csrf_field(); ?>

                        
                        <div class="border border-sky-200 bg-sky-50/50 rounded-lg p-3">
                            <h4 class="font-medium text-sky-900 mb-2 text-sm flex items-center gap-1">
                                <i class="bi bi-file-earmark-text text-sky-600"></i>
                                Dokumen Permohonan
                            </h4>
                            <label class="flex items-center p-2 rounded hover:bg-sky-100 cursor-pointer">
                                <input type="checkbox" name="verifikasi_surat_permohonan" value="1"
                                    class="rounded text-primary-600 focus:ring-primary-500 mr-3" <?php echo e($mutasi->file_surat_permohonan ? 'checked' : 'disabled'); ?>

                                    @change="updateNote($el.checked, 'Surat Permohonan Izin Mutasi')">
                                <span class="text-sm text-gray-700">Surat Permohonan Izin Mutasi</span>
                                <?php if(!$mutasi->file_surat_permohonan): ?>
                                    <span class="ml-auto text-xs text-red-500 italic">Tidak ada file</span>
                                <?php endif; ?>
                            </label>
                        </div>

                        <!-- Checklist per Siswa -->
                        <?php $__currentLoopData = $siswaList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $siswa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="border rounded-lg p-3" x-show="activeTab === <?php echo e($idx); ?>" x-transition>
                                <h4 class="font-medium text-gray-900 mb-2 text-sm">
                                    <i class="bi bi-person text-blue-600 mr-1"></i>
                                    <?php echo e($siswa->nama_murid); ?>

                                </h4>
                                <?php
                                    $siswaDocList = [
                                        ['key' => "siswa_{$idx}_pindah", 'label' => 'Surat Pindah', 'file' => $siswa->file_surat_pindah],
                                        ['key' => "siswa_{$idx}_dapodik", 'label' => 'Surat Dapodik', 'file' => $siswa->file_surat_dapodik],
                                        ['key' => "siswa_{$idx}_rekom", 'label' => 'Rekomendasi', 'file' => $siswa->file_rekomendasi],
                                        ['key' => "siswa_{$idx}_ijazah", 'label' => 'Ijazah SD', 'file' => $siswa->file_ijazah_sd],
                                        ['key' => "siswa_{$idx}_rapor", 'label' => 'Rapor', 'file' => $siswa->file_rapor],
                                        ['key' => "siswa_{$idx}_rekam_didik", 'label' => 'Rekam Didik', 'file' => $siswa->file_rekam_didik],
                                    ];
                                ?>
                                <div class="grid grid-cols-1 gap-2">
                                    <?php $__currentLoopData = $siswaDocList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <label class="flex items-center p-2 rounded hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox" name="verifikasi[<?php echo e($siswa->id); ?>][<?php echo e($doc['key']); ?>]" value="1"
                                                class="rounded text-primary-600 focus:ring-primary-500 mr-3" <?php echo e($doc['file'] ? 'checked' : 'disabled'); ?>

                                                @change="updateNote($el.checked, '<?php echo e($doc['label']); ?> (<?php echo e($siswa->nama_murid); ?>)')">
                                            <span class="text-sm text-gray-700"><?php echo e($doc['label']); ?></span>
                                            <?php if(!$doc['file']): ?>
                                                <span class="ml-auto text-xs text-red-500 italic">Tidak ada file</span>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                            <textarea name="catatan" rows="3" x-model="catatan"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                                placeholder="Tambahkan catatan jika perlu perbaikan..."></textarea>
                        </div>

                        <div class="flex flex-col gap-2 pt-2">
                            <button type="submit" name="action" value="approve"
                                class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition shadow-sm">
                                <i class="bi bi-check-lg mr-2"></i> Setujui Berkas
                            </button>
                            <button type="submit" name="action" value="reject"
                                class="w-full py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-medium transition shadow-sm">
                                <i class="bi bi-x-lg mr-2"></i> Minta Perbaikan
                            </button>
                        </div>
                    </form>
                    <script>

                        function verif                                                ikasiMasuk() {
                            return {
                                activeTab: 0,
                                activeDoc: null,
                                catatan: '',
                                updateNote(checked, label) {
                                    // Default message to add
                                    const errorSuffix = "tidak sesuai,";
                                    const defaultMsg = `- ${label} ${errorSuffix}`;

                                    if (!checked) {
                                        // Unchecked: Add error note
                                        // We append default message. User can edit it later.
                                        if (!this.catatan.includes(defaultMsg)) {
                                            this.catatan = this.catatan ? this.catatan + '\n' + defaultMsg : defaultMsg;
                                        }
                                    } else {
                                        // Checked: Remove ANY line related to this label (even if edited)
                                        // We look for lines starting with "- {Label}"

                                        const escapedLabel = label.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                                        // Regex: Start of line, hyphen, space, label, any char until end of line
                                        const regex = new RegExp(`^-\\s+${escapedLabel}.*$`, 'gm');

                                        this.catatan = this.catatan.replace(regex, '');

                                        // Clean up empty lines
                                        this.catatan = this.catatan.replace(/^\s*[\r\n]/gm, '').trim();
                                    }
                                }
                            }
                        }
                    </script>
                </div>
            </div>
        <?php else: ?>
            <!-- Normal View -->
            <div class="space-y-4">
                
                <?php if($mutasi->file_surat_permohonan): ?>
                    <div
                        class="bg-gradient-to-r from-sky-50 to-blue-50 border border-sky-200 rounded-xl p-4 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-sky-600 rounded-lg flex items-center justify-center shadow-md shadow-sky-200">
                                <i class="bi bi-file-earmark-text-fill text-white"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-sky-900 text-sm">Surat Permohonan Izin Mutasi</h4>
                                <p class="text-xs text-sky-700">Dokumen Utama Pengajuan</p>
                            </div>
                        </div>
                        <button type="button"
                            @click="activeDoc = activeDoc === 'surat_permohonan_normal' ? null : 'surat_permohonan_normal'"
                            :style="activeDoc === 'surat_permohonan_normal' ? 'background-color: #0284c7 !important; color: white !important;' : ''"
                            :class="activeDoc === 'surat_permohonan_normal' ? '!bg-sky-600 !text-white ring-2 ring-offset-1 ring-sky-500 font-bold shadow-md' : 'bg-white text-sky-700 hover:bg-sky-100 border border-sky-300'"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                            <i class="bi bi-eye"></i> Lihat Surat
                        </button>
                    </div>
                    
                    <div x-show="activeDoc === 'surat_permohonan_normal'" x-transition
                        class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <iframe src="<?php echo e(route('dokumen.view', ['path' => $mutasi->file_surat_permohonan])); ?>"
                            class="w-full bg-slate-100" style="height: 500px;"></iframe>
                    </div>
                <?php endif; ?>

                <!-- Data Siswa dengan Tab -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 sm:p-6">
                    <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                            <i class="bi bi-people-fill text-blue-600 text-xl"></i>
                        </div>
                        Data Siswa
                    </h2>

                    <?php if(count($siswaList) > 1): ?>
                        <div class="flex flex-wrap gap-2 mb-4 border-b pb-4">
                            <?php $__currentLoopData = $siswaList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $siswa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <button type="button" @click="activeTab = <?php echo e($idx); ?>"
                                    :class="activeTab === <?php echo e($idx); ?> ? 'bg-primary-600 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                                    class="px-4 py-2 rounded-lg font-medium transition text-sm">
                                    Siswa <?php echo e($idx + 1); ?>

                                </button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>

                    
                    <?php if($mutasi->status === 'Perlu Perbaikan'): ?>
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3 mb-6">
                            <i class="bi bi-exclamation-triangle-fill text-amber-500 text-xl flex-shrink-0"></i>
                            <div>
                                <h4 class="font-bold text-amber-800 text-sm">Perlu Perbaikan</h4>
                                <p class="text-sm text-amber-700 mt-1">Mohon perbaiki data atau dokumen sesuai catatan dari
                                    verifikator. Cek catatan pada setiap siswa atau dokumen.</p>
                                <?php if($mutasi->catatan_berkas): ?>
                                    <div class="mt-2 bg-white/60 p-2 rounded text-xs font-mono text-amber-900 border border-amber-100">
                                        <?php echo e($mutasi->catatan_berkas); ?>

                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php $__currentLoopData = $siswaList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $siswa): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $birthDate = \Carbon\Carbon::parse($siswa->tanggal_lahir);
                            $age = $birthDate->diff(now());
                        ?>
                        <div x-show="activeTab === <?php echo e($idx); ?>" x-transition class="space-y-6">

                            
                            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">

                                
                                <div class="p-6 bg-slate-50 border-b border-slate-200 flex items-start gap-5">
                                    <div
                                        class="w-16 h-16 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg text-white shrink-0">
                                        <i class="bi bi-person-fill text-3xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-bold text-xl text-slate-900"><?php echo e($siswa->nama_murid); ?></h3>
                                        <div class="flex flex-wrap items-center gap-4 mt-2">
                                            <span class="inline-flex items-center gap-2 text-slate-700 font-medium text-sm">
                                                <i class="bi bi-gender-ambiguous text-blue-600"></i>
                                                <?php echo e($siswa->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan'); ?>

                                            </span>
                                            <span class="inline-flex items-center gap-2 text-slate-700 font-medium text-sm">
                                                <i class="bi bi-mortarboard-fill text-blue-600"></i>
                                                Kelas <?php echo e($siswa->kelas); ?>

                                            </span>
                                            <span class="inline-flex items-center gap-2 text-slate-700 font-medium text-sm">
                                                <i class="bi bi-upc-scan text-blue-600"></i>
                                                NISN: <span class="font-bold font-mono"><?php echo e($siswa->nisn); ?></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                
                                
                                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                                    
                                    <div class="space-y-6">
                                        <h4 class="font-bold text-slate-900 border-b border-slate-200 pb-2 mb-4">Biodata Siswa</h4>

                                        <div class="grid grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-500 mb-1">Tempat, Tanggal
                                                    Lahir</label>
                                                <div class="text-base font-medium text-slate-900">
                                                    <?php echo e($siswa->tempat_lahir); ?><br><?php echo e($birthDate->translatedFormat('j F Y')); ?>

                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-500 mb-1">Usia Saat Ini</label>
                                                <div class="text-base font-medium text-slate-900">
                                                    <?php echo e($age->y); ?> Tahun <?php echo e($age->m); ?> Bulan
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-semibold text-slate-500 mb-1">Tanggal Diterima</label>
                                            <div
                                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-100 rounded-lg text-base font-bold">
                                                <i class="bi bi-calendar-check text-emerald-600"></i>
                                                <?php echo e(\Carbon\Carbon::parse($siswa->tanggal_diterima)->translatedFormat('j F Y')); ?>

                                            </div>
                                        </div>
                                    </div>

                                    
                                    <div class="space-y-6">
                                        <h4 class="font-bold text-slate-900 border-b border-slate-200 pb-2 mb-4">Asal Sekolah</h4>

                                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
                                            <div class="mb-4">
                                                <label class="block text-sm font-semibold text-amber-800 mb-1">Nama Sekolah</label>
                                                <div class="text-lg font-bold text-slate-900">
                                                    <?php echo e($siswa->nama_sekolah_asal); ?>

                                                </div>
                                                <div class="mt-1 flex items-center gap-2">
                                                    <span
                                                        class="text-sm font-bold font-mono text-slate-700 bg-white/50 px-2 py-0.5 rounded border border-amber-100">NPSN:
                                                        <?php echo e($siswa->npsn_sekolah_asal ?? '-'); ?></span>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-amber-800 mb-1">Alamat
                                                    Lengkap</label>
                                                <div class="text-sm font-medium text-slate-800 leading-relaxed">
                                                    <?php echo e($siswa->alamat_sekolah_asal); ?>

                                                    <div
                                                        class="mt-1 text-slate-600 pt-2 border-t border-amber-200/50 flex items-center gap-1.5">
                                                        <i class="bi bi-geo-alt"></i> <?php echo e($siswa->kabupaten_kota_asal); ?>,
                                                        <?php echo e($siswa->provinsi_asal); ?>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm p-6">
                                <h4 class="font-bold text-slate-900 border-b border-slate-200 pb-3 mb-4 flex items-center gap-2">
                                    <i class="bi bi-files text-blue-600"></i> Dokumen Lampiran
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <?php
                                        $docList = [
                                            ['key' => "normal_{$idx}_pindah", 'label' => 'Surat Pindah', 'file' => $siswa->file_surat_pindah, 'color' => 'rose'],
                                            ['key' => "normal_{$idx}_dapodik", 'label' => 'Dapodik', 'file' => $siswa->file_surat_dapodik, 'color' => 'blue'],
                                            ['key' => "normal_{$idx}_rekom", 'label' => 'Rekomendasi', 'file' => $siswa->file_rekomendasi, 'color' => 'purple'],
                                            ['key' => "normal_{$idx}_ijazah", 'label' => 'Ijazah SD', 'file' => $siswa->file_ijazah_sd, 'color' => 'emerald'],
                                            ['key' => "normal_{$idx}_rapor", 'label' => 'Rapor', 'file' => $siswa->file_rapor, 'color' => 'orange'],
                                            ['key' => "normal_{$idx}_rekam_didik", 'label' => 'Rekam Didik', 'file' => $siswa->file_rekam_didik, 'color' => 'cyan'],
                                        ];
                                    ?>

                                    <?php $__currentLoopData = $docList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($doc['file']): ?>
                                            <button @click="activeDoc = activeDoc === '<?php echo e($doc['key']); ?>' ? null : '<?php echo e($doc['key']); ?>'"
                                                :class="activeDoc === '<?php echo e($doc['key']); ?>' 
                                                                            ? 'ring-2 ring-<?php echo e($doc['color']); ?>-500 bg-<?php echo e($doc['color']); ?>-50' 
                                                                            : 'bg-white hover:bg-slate-50 border-slate-200'"
                                                class="flex items-center gap-4 p-4 rounded-xl border transition group text-left w-full shadow-sm hover:shadow-md">

                                                <div
                                                    class="w-12 h-12 rounded-lg bg-<?php echo e($doc['color']); ?>-100 text-<?php echo e($doc['color']); ?>-600 flex items-center justify-center shrink-0">
                                                    <i class="bi bi-file-earmark-pdf-fill text-2xl"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <p
                                                        class="text-sm font-bold text-slate-500 mb-0.5 group-hover:text-<?php echo e($doc['color']); ?>-600">
                                                        Dokumen</p>
                                                    <p class="font-bold text-slate-900 text-base truncate"><?php echo e($doc['label']); ?></p>
                                                </div>
                                                <div class="text-slate-400 group-hover:text-<?php echo e($doc['color']); ?>-600">
                                                    <i class="bi bi-eye-fill text-xl" x-show="activeDoc !== '<?php echo e($doc['key']); ?>'"></i>
                                                    <i class="bi bi-chevron-up text-xl font-bold"
                                                        x-show="activeDoc === '<?php echo e($doc['key']); ?>'"></i>
                                                </div>
                                            </button>
                                        <?php endif; ?>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>

                            
                            <?php $__currentLoopData = $docList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($doc['file']): ?>
                                    <div x-show="activeDoc === '<?php echo e($doc['key']); ?>'" x-transition
                                        class="border-2 border-slate-300 rounded-xl overflow-hidden shadow-lg bg-white scroll-mt-24"
                                        x-ref="doc_<?php echo e($doc['key']); ?>">
                                        <div class="flex items-center justify-between px-6 py-4 bg-slate-100 border-b border-slate-200">
                                            <h5 class="font-bold text-lg text-slate-800"><?php echo e($doc['label']); ?> - <?php echo e($siswa->nama_murid); ?></h5>
                                            <button @click="activeDoc = null"
                                                class="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 flex items-center justify-center text-slate-600 transition">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <iframe src="<?php echo e(route('dokumen.view', ['path' => $doc['file']])); ?>"
                                            class="w-full h-[700px] bg-slate-200"></iframe>
                                        <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-center">
                                            <a href="<?php echo e(route('dokumen.view', ['path' => $doc['file']])); ?>" target="_blank"
                                                class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition shadow-md">
                                                <i class="bi bi-box-arrow-up-right"></i> Buka Dokumen Layar Penuh
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <?php if(count($siswaList) > 1): ?>
                        <div class="flex items-center justify-between pt-4 border-t mt-4">
                            <button @click="if(activeTab > 0) activeTab--" :disabled="activeTab === 0"
                                :class="activeTab === 0 ? 'opacity-50' : 'hover:bg-gray-200'"
                                class="px-4 py-2 bg-gray-100 rounded-lg text-sm">← Sebelumnya</button>
                            <span class="text-sm text-gray-500">Siswa <span x-text="activeTab + 1"></span> /
                                <?php echo e(count($siswaList)); ?></span>
                            <button @click="if(activeTab < <?php echo e(count($siswaList) - 1); ?>) activeTab++"
                                :disabled="activeTab === <?php echo e(count($siswaList) - 1); ?>"
                                :class="activeTab === <?php echo e(count($siswaList) - 1); ?> ? 'opacity-50' : 'hover:bg-gray-200'"
                                class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Berikutnya →</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Surat Izin -->
        <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin']) && $mutasi->status === 'Disetujui Dinas' && !($mutasi->nomor_surat_izin ?? null)): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-blue-900 mb-4"><i
                        class="bi bi-file-earmark-text text-blue-600 mr-2"></i>Terbitkan Surat Izin</h2>
                <form action="<?php echo e(route('mutasi.masuk.issue-letter', $mutasi->id_mutasi)); ?>" method="POST" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-blue-900 mb-1">Nomor Surat Izin</label>
                            <input type="text" name="nomor_surat_izin" required
                                class="w-full rounded-lg border-blue-200 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Contoh: 421.3/123/Disdik">
                        </div>
                    </div>
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition shadow-sm">
                        <i class="bi bi-send-check mr-2"></i> Terbitkan Surat
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Info Surat Izin -->
        <?php if($mutasi->nomor_surat_izin): ?>
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
                <div class="grid grid-cols-2 gap-8 items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-emerald-900 mb-2">
                            <i class="bi bi-check-circle-fill text-emerald-600 mr-2"></i> Surat Izin Terbit
                        </h3>
                        <p class="text-emerald-700">Nomor: <span
                                class="font-mono font-bold"><?php echo e($mutasi->nomor_surat_izin); ?></span>
                        </p>
                        <p class="text-sm text-emerald-600 mt-1">Diterbitkan pada:
                            <?php echo e(\Carbon\Carbon::parse($mutasi->tanggal_surat_izin)->translatedFormat('d F Y')); ?>

                        </p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <?php if(in_array(auth()->user()->role, ['Dinas', 'SuperAdmin'])): ?>
                            <a href="<?php echo e(route('mutasi.masuk.print-letter', $mutasi->id_mutasi)); ?>" target="_blank"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition shadow-sm">
                                <i class="bi bi-printer mr-2"></i> Cetak Surat Izin
                            </a>
                        <?php endif; ?>
                        <!-- Route missing/not implemented
                                    <a href="" target="_blank"
                                        class="px-4 py-2 bg-white text-emerald-700 border border-emerald-200 hover:bg-emerald-50 rounded-lg font-medium transition shadow-sm">
                                        <i class="bi bi-printer mr-2"></i> Cetak Surat Keterangan
                                    </a>
                                    -->
                    </div>
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
                    <form action="<?php echo e(route('mutasi.masuk.upload-arsip', $mutasi->id_mutasi)); ?>" method="POST"
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
                        || ($user->role === 'Sekolah' && $user->npsn === $mutasi->npsn_sekolah_tujuan);
                    $canRevise = in_array($user->role, ['Operator', 'Sekolah'])
                        && $isOwner
                        && in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan']);
                ?>

                <?php if($canRevise): ?>
                    <a href="<?php echo e(route('mutasi.masuk.revision', $mutasi->id_mutasi)); ?>"
                        class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-pencil-square mr-2"></i> Perbaiki & Kirim Ulang
                    </a>
                <?php endif; ?>
            </div>
        <?php elseif(in_array($mutasi->status, ['Perlu Perbaikan', 'Dibatalkan'])): ?>
            
            <?php
                $user = auth()->user();
                $isOwner = ($user->username === $mutasi->pengusul)
                    || ($user->role === 'Sekolah' && $user->npsn === $mutasi->npsn_sekolah_tujuan);
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
                        : 'Data mutasi Anda perlu diperbaiki. Silakan review dan kirim ulang.'); ?>

                    </p>
                    <a href="<?php echo e(route('mutasi.masuk.revision', $mutasi->id_mutasi)); ?>"
                        class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition font-medium">
                        <i class="bi bi-pencil-square mr-2"></i> Perbaiki & Kirim Ulang
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
>>>>>>> 9466a07 (Upload project pertama kali)
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\antigravity\p_mutasi\LARAVEL_SIMAK\resources\views\mutasi\masuk\show.blade.php ENDPATH**/ ?>