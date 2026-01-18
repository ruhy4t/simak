/**
 * Mutasi Masuk Form Script
 * Extracted from create.blade.php for optimization
 */

(function () {
    'use strict';

    // Configuration from Blade
    const CONFIG = window.MUTASI_CONFIG || {};

    // WILAYAH_DATA removed - fetching from API now
    const OLD_VALUES = CONFIG.oldValues || {};
    const OLD_JUMLAH = CONFIG.oldJumlah || 1;
    const isDinasEntry = CONFIG.isDinasEntry || false;
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // DOM Elements
    let form, jumlahInput, container, tabNav, navButtons, prevBtn, nextBtn;
    let totalSiswa = 1;
    let currentSiswa = 0;

    // --- Template Generator ---
    function generateSiswaForm(index) {
        return `
        <div class="siswa-card ${index > 0 ? 'hidden' : ''}" data-index="${index}">
            <div class="mb-4 pb-3 border-b">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-sm font-bold">${index + 1}</span>
                    Data Siswa #${index + 1}
                </h3>
            </div>

            <!-- Data Siswa -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Siswa *</label>
                    <input type="text" name="siswa[${index}][nama_murid]" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NISN *</label>
                    <input type="text" name="siswa[${index}][nisn]" maxlength="10" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none"
                        placeholder="10 digit">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">NIS/NIPD *</label>
                    <input type="text" name="siswa[${index}][nis]" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none"
                        placeholder="Nomor Induk Siswa">
                </div>
                <div>
                    <label class="block text-sm font-medium text-red-500 mb-1">Tanggal Diterima Disekolah *</label>
                    <input type="date" name="siswa[${index}][tanggal_diterima]" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tempat Lahir *</label>
                    <input type="text" name="siswa[${index}][tempat_lahir]" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir *</label>
                    <input type="date" name="siswa[${index}][tanggal_lahir]" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin *</label>
                    <select name="siswa[${index}][jenis_kelamin]" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                        <option value="">Pilih</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kelas *</label>
                    <select name="siswa[${index}][kelas]" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                        <option value="">Pilih</option>
                        <option value="VII">VII</option>
                        <option value="VIII">VIII</option>
                        <option value="IX">IX</option>
                    </select>
                </div>
            </div>

            <!-- Sekolah Asal -->
            <div class="mb-6 p-4 bg-orange-50 rounded-lg border border-orange-200">
                <h4 class="text-md font-semibold text-orange-800 mb-3 flex items-center gap-2">
                    <i class="bi bi-building"></i> Sekolah Asal
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NPSN Sekolah Asal</label>
                        <div class="relative">
                            <input type="text" name="siswa[${index}][npsn_sekolah_asal]" id="npsn_asal_${index}" 
                                maxlength="8" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none"
                                placeholder="8 karakter (contoh: 12345678 atau P1234567)" oninput="window.lookupNpsnAsal(${index}, this.value)">
                            <span id="npsn_loading_${index}" class="hidden absolute right-3 top-2.5 text-gray-400">
                                <i class="bi bi-arrow-repeat animate-spin"></i>
                            </span>
                            <span id="npsn_status_icon_${index}" class="absolute right-3 top-2.5"></span>
                        </div>
                        <p id="npsn_status_${index}" class="text-xs mt-1 text-gray-500">Contoh: 12345678 atau P1234567 (PKBM)</p>
                        <input type="hidden" name="siswa[${index}][npsn_ditemukan]" id="npsn_ditemukan_${index}" value="0">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Sekolah Asal *</label>
                        <input type="text" name="siswa[${index}][nama_sekolah_asal]" id="nama_asal_${index}" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi Asal *</label>
                        <select id="provinsi_asal_select_${index}" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                            <option value="">Pilih Provinsi</option>
                        </select>
                        <input type="hidden" name="siswa[${index}][provinsi_asal]" id="provinsi_asal_${index}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kab/Kota Asal *</label>
                        <select id="kabkota_asal_select_${index}" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none">
                            <option value="">Pilih Kab/Kota</option>
                        </select>
                        <input type="hidden" name="siswa[${index}][kabupaten_kota_asal]" id="kabkota_asal_${index}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Sekolah Asal *</label>
                        <textarea name="siswa[${index}][alamat_sekolah_asal]" id="alamat_asal_${index}" rows="2" required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-100 focus:border-primary-500 outline-none"></textarea>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="siswa[${index}][asal_luar_negeri]" id="luar_negeri_${index}" value="1"
                            class="w-5 h-5 rounded text-primary-600" onchange="window.toggleLuarNegeri(${index})">
                        <span class="text-sm font-medium text-gray-700">Siswa berasal dari Luar Negeri</span>
                    </label>
                </div>
            </div>

            <!-- Dokumen -->
            <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                <h4 class="text-md font-semibold text-red-800 mb-3 flex items-center gap-2">
                    <i class="bi bi-file-earmark-pdf"></i> ${isDinasEntry ? 'Konfirmasi Berkas' : 'Dokumen'} Siswa #${index + 1}
                </h4>
                ${isDinasEntry ? `
                <!-- Mode Dinas: Checkbox -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3 bg-white border rounded-lg cursor-pointer">
                        <input type="checkbox" name="siswa[${index}][has_surat_pindah]" value="1" required class="w-5 h-5 text-green-600 rounded">
                        <div><span class="font-medium text-sm">Surat Pindah</span><p class="text-xs text-gray-500">Dari Kepala Sekolah Asal</p></div>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-white border rounded-lg cursor-pointer">
                        <input type="checkbox" name="siswa[${index}][has_surat_dapodik]" value="1" required class="w-5 h-5 text-green-600 rounded">
                        <div><span class="font-medium text-sm">Surat Dapodik/Emis</span><p class="text-xs text-gray-500">Dicetak dari Dapodik/Emis</p></div>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-white border rounded-lg cursor-pointer">
                        <input type="checkbox" name="siswa[${index}][has_rekomendasi]" value="1" class="w-5 h-5 text-green-600 rounded">
                        <div><span class="font-medium text-sm">Rekomendasi Dinas/Kemenag</span><p class="text-xs text-gray-500">Jika NPSN asal tidak terdaftar</p></div>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-white border rounded-lg cursor-pointer">
                        <input type="checkbox" name="siswa[${index}][has_ijazah_sd]" value="1" required class="w-5 h-5 text-green-600 rounded">
                        <div><span class="font-medium text-sm">Ijazah SD</span><p class="text-xs text-gray-500">Scan ijazah dari dokumen asli</p></div>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-white border rounded-lg cursor-pointer">
                        <input type="checkbox" name="siswa[${index}][has_rapor]" value="1" required class="w-5 h-5 text-green-600 rounded">
                        <div><span class="font-medium text-sm">Rapor Lengkap</span><p class="text-xs text-gray-500">Rapor SMP lengkap</p></div>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-white border rounded-lg cursor-pointer">
                        <input type="checkbox" name="siswa[${index}][has_surat_penyaluran]" value="1" class="w-5 h-5 text-green-600 rounded">
                        <div><span class="font-medium text-sm">Surat Penyaluran Kemdikdasmen</span><p class="text-xs text-gray-500">Jika dari luar negeri</p></div>
                    </label>
                </div>
                ` : `
                <!-- Mode Normal: File Upload -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Surat Pindah dari Sekolah Asal *</label>
                        <input type="file" name="siswa[${index}][file_surat_pindah]" accept=".pdf" required
                            class="w-full px-4 py-2 border rounded-lg bg-white" onchange="window.validateFile(this, 1)">
                        <p class="text-xs text-gray-500 italic mt-1">Surat keterangan pindah asli. Maks 1MB.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Surat Pindah dari Dapodik/Emis*</label>
                        <input type="file" name="siswa[${index}][file_surat_dapodik]" accept=".pdf" required
                            class="w-full px-4 py-2 border rounded-lg bg-white" onchange="window.validateFile(this, 1)">
                        <p class="text-xs text-gray-500 italic mt-1">Dicetak dari Dapodik/Emis. Maks 1MB.</p>
                    </div>
                    <div id="rekomendasi_container_${index}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Rekomendasi Dinas/Kemenag <span id="rekomendasi_required_${index}" class="text-red-500">*</span>
                        </label>
                        <input type="file" name="siswa[${index}][file_rekomendasi]" id="file_rekomendasi_${index}" accept=".pdf" required
                            class="w-full px-4 py-2 border rounded-lg bg-white" onchange="window.validateFile(this, 1)">
                        <p class="text-xs text-gray-500 italic mt-1">Wajib diunggah. Silahkan unggah rekomendasi/keterangan mutasi keluar yang diterbitkan oleh Dinas Pendidikan / Kemenag asal. Maks 1MB.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ijazah SD *</label>
                        <input type="file" name="siswa[${index}][file_ijazah_sd]" accept=".pdf" required
                            class="w-full px-4 py-2 border rounded-lg bg-white" onchange="window.validateFile(this, 1)">
                        <p class="text-xs text-gray-500 italic mt-1">Scan ijazah SD dari dokumen asli. Maks 1MB.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rapor SMP Lengkap *</label>
                        <input type="file" name="siswa[${index}][file_rapor]" accept=".pdf" required
                            class="w-full px-4 py-2 border rounded-lg bg-white" onchange="window.validateFile(this, 2)">
                        <p class="text-xs text-gray-500 italic mt-1">Semester 1 s.d terakhir. Maks 2MB.</p>
                    </div>
                    <div id="penyaluran_container_${index}" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Surat Penyaluran Kemdikdasmen *</label>
                        <input type="file" name="siswa[${index}][file_surat_penyaluran]" id="file_surat_penyaluran_${index}" accept=".pdf"
                            class="w-full px-4 py-2 border rounded-lg bg-white" onchange="window.validateFile(this, 1)">
                        <p class="text-xs text-gray-500 italic mt-1">Wajib untuk siswa luar negeri. Maks 1MB.</p>
                    </div>
                    <div id="rekam_didik_container_${index}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Rekam Didik dari Dapodik <span id="rekam_didik_required_${index}" class="text-red-500">*</span>
                        </label>
                        <input type="file" name="siswa[${index}][file_rekam_didik]" id="file_rekam_didik_${index}" accept=".pdf,.jpg,.jpeg,.png" required
                            class="w-full px-4 py-2 border rounded-lg bg-white" onchange="window.validateFile(this, 2)">
                        <p class="text-xs text-gray-500 italic mt-1">Silahkan koordinasi dengan bagian dapodik Dinas Pendidikan Kota Bogor untuk screenshot Rekam Didik. PDF/JPEG/PNG. Maks 2MB.</p>
                    </div>
                </div>`}
            </div>
        </div>
        `;
    }

    // Load provinces
    async function loadProvinces(selectId) {
        const select = document.getElementById(selectId);
        if (!select) return;
        select.innerHTML = '<option value="">Pilih Provinsi</option>';

        try {
            const response = await fetch(CONFIG.routes.apiProvinces);
            const result = await response.json();

            if (result.success && result.data) {
                result.data.forEach(prov => {
                    const option = document.createElement('option');
                    option.value = prov.name;
                    option.dataset.id = prov.id;
                    option.textContent = prov.name;
                    select.appendChild(option);
                });
            }
        } catch (e) {
            console.error('Failed to load provinces', e);
        }
    }

    function setupSiswaEvents(index) {
        loadProvinces(`provinsi_asal_select_${index}`);

        const provSelect = document.getElementById(`provinsi_asal_select_${index}`);
        const kabSelect = document.getElementById(`kabkota_asal_select_${index}`);
        const provHidden = document.getElementById(`provinsi_asal_${index}`);
        const kabHidden = document.getElementById(`kabkota_asal_${index}`);

        if (provSelect) {
            provSelect.addEventListener('change', async function () {
                var val = this.value;
                const selectedOption = this.options[this.selectedIndex];
                const provId = selectedOption ? selectedOption.dataset.id : null;

                if (provHidden) provHidden.value = val;
                if (kabSelect) {
                    kabSelect.innerHTML = '<option value="">Pilih Kab/Kota</option>';
                    kabSelect.disabled = true;
                }
                if (kabHidden) kabHidden.value = '';

                // Handle Luar Negeri
                if (val === 'Luar Negeri') {
                    const option = document.createElement('option');
                    option.value = 'Luar Negeri';
                    option.textContent = 'Luar Negeri';
                    if (kabSelect) {
                        kabSelect.appendChild(option);
                        kabSelect.value = 'Luar Negeri';
                        kabSelect.disabled = false;
                    }
                    if (kabHidden) kabHidden.value = 'Luar Negeri';
                    return;
                }

                if (provId && kabSelect) {
                    try {
                        const response = await fetch(CONFIG.routes.apiRegencies.replace(':provinceId', provId));
                        const result = await response.json();

                        if (result.success && result.data) {
                            result.data.forEach(kab => {
                                const option = document.createElement('option');
                                option.value = kab.name;
                                option.textContent = kab.name;
                                kabSelect.appendChild(option);
                            });
                        }
                        kabSelect.disabled = false;
                    } catch (e) {
                        console.error('Failed to load regencies', e);
                        kabSelect.disabled = false;
                    }
                } else if (kabSelect) {
                    kabSelect.disabled = false;
                }
            });
            // Trigger if value exists (e.g. from autosave) - simplified as async might race
            // if (provSelect.value) provSelect.dispatchEvent(new Event('change'));
        }

        if (kabSelect) {
            kabSelect.addEventListener('change', function () {
                if (kabHidden) kabHidden.value = this.value;
            });
        }
    }

    function resetSekolahAsal(index) {
        const namaInput = document.getElementById(`nama_asal_${index}`);
        const alamatInput = document.getElementById(`alamat_asal_${index}`);
        const provSelect = document.getElementById(`provinsi_asal_select_${index}`);
        const kabSelect = document.getElementById(`kabkota_asal_select_${index}`);
        const npsnDitemukan = document.getElementById(`npsn_ditemukan_${index}`);
        const rekomendasiRequired = document.getElementById(`rekomendasi_required_${index}`);
        const fileRekomendasi = document.getElementById(`file_rekomendasi_${index}`);
        const statusIcon = document.getElementById(`npsn_status_icon_${index}`);
        const statusText = document.getElementById(`npsn_status_${index}`);

        if (namaInput) {
            namaInput.readOnly = false;
            namaInput.classList.remove('bg-gray-100');
            namaInput.value = '';
        }
        if (alamatInput) {
            alamatInput.readOnly = false;
            alamatInput.classList.remove('bg-gray-100');
            alamatInput.value = '';
        }
        if (provSelect) {
            provSelect.disabled = false;
            provSelect.classList.remove('bg-gray-100');
            provSelect.value = '';
        }
        if (kabSelect) {
            kabSelect.disabled = false;
            kabSelect.classList.remove('bg-gray-100');
            kabSelect.innerHTML = '<option value="">Pilih Kab/Kota</option>';
        }

        if (npsnDitemukan) npsnDitemukan.value = '0';
        if (rekomendasiRequired) rekomendasiRequired.classList.remove('hidden');
        if (fileRekomendasi) fileRekomendasi.setAttribute('required', 'required');

        if (statusIcon) statusIcon.innerHTML = '';
        if (statusText) statusText.innerHTML = 'Contoh: 12345678 atau P1234567';
    }

    // --- Global Functions (Exposed to Window) ---

    // Toggle Luar Negeri Logic
    window.toggleLuarNegeri = function (index) {
        const checkbox = document.getElementById(`luar_negeri_${index}`);
        const container = document.getElementById(`penyaluran_container_${index}`);
        const fileInput = document.getElementById(`file_surat_penyaluran_${index}`);
        const npsnInput = document.getElementById(`npsn_asal_${index}`);
        const npsnStatus = document.getElementById(`npsn_status_${index}`);
        const provSelect = document.getElementById(`provinsi_asal_select_${index}`);
        const kabSelect = document.getElementById(`kabkota_asal_select_${index}`);
        const provHidden = document.getElementById(`provinsi_asal_${index}`);
        const kabHidden = document.getElementById(`kabkota_asal_${index}`);
        const suratPindahInput = document.querySelector(`input[name="siswa[${index}][file_surat_pindah]"]`);
        const suratDapodikInput = document.querySelector(`input[name="siswa[${index}][file_surat_dapodik]"]`);
        // NEW: Elements for Rekomendasi
        const fileRekomendasi = document.getElementById(`file_rekomendasi_${index}`);
        const rekomendasiRequired = document.getElementById(`rekomendasi_required_${index}`);
        // NEW: Elements for Rekam Didik
        const fileRekamDidik = document.getElementById(`file_rekam_didik_${index}`);
        const rekamDidikRequired = document.getElementById(`rekam_didik_required_${index}`);

        if (checkbox.checked) {
            // Show surat penyaluran
            if (container) container.classList.remove('hidden');
            if (fileInput) fileInput.setAttribute('required', 'required');

            // NPSN Optional
            if (npsnInput) {
                npsnInput.removeAttribute('required');
                npsnInput.placeholder = 'Kosongkan jika tidak ada';
            }
            if (npsnStatus) {
                npsnStatus.textContent = 'NPSN tidak wajib untuk sekolah luar negeri';
                npsnStatus.className = 'text-xs mt-1 text-blue-600';
            }

            // Set Province to Luar Negeri
            if (provSelect && provHidden) {
                // Add Luar Negeri option if not exists
                if (![...provSelect.options].some(o => o.value === 'Luar Negeri')) {
                    const opt = document.createElement('option');
                    opt.value = 'Luar Negeri';
                    opt.text = 'Luar Negeri';
                    provSelect.add(opt);
                }
                provSelect.value = 'Luar Negeri';
                provHidden.value = 'Luar Negeri';

                // Trigger change to populate/clear kabkota
                provSelect.dispatchEvent(new Event('change'));

                // Set KabKota to Luar Negeri
                if (kabSelect) {
                    kabSelect.innerHTML = '<option value="Luar Negeri">Luar Negeri</option>';
                    kabSelect.value = 'Luar Negeri';
                }
                if (kabHidden) kabHidden.value = 'Luar Negeri';
            }

            // Files Optional
            if (suratPindahInput) suratPindahInput.removeAttribute('required');
            if (suratDapodikInput) suratDapodikInput.removeAttribute('required');

            // NEW: Rekomendasi Optional for Luar Negeri
            if (fileRekomendasi) fileRekomendasi.removeAttribute('required');
            if (rekomendasiRequired) rekomendasiRequired.classList.add('hidden');

            // NEW: Rekam Didik Optional for Luar Negeri
            if (fileRekamDidik) fileRekamDidik.removeAttribute('required');
            if (rekamDidikRequired) rekamDidikRequired.classList.add('hidden');

        } else {
            // Hide surat penyaluran
            if (container) container.classList.add('hidden');
            if (fileInput) fileInput.removeAttribute('required');

            // NPSN Required
            if (npsnInput) npsnInput.placeholder = '8 digit NPSN';
            if (npsnStatus) npsnStatus.textContent = '';

            // Reset Province
            if (provSelect && provHidden) {
                loadProvinces(`provinsi_asal_select_${index}`);
                provHidden.value = '';
                kabHidden.value = '';
            }

            // Files Required
            if (suratPindahInput) suratPindahInput.setAttribute('required', 'required');
            if (suratDapodikInput) suratDapodikInput.setAttribute('required', 'required');

            // NEW: Reset Rekomendasi to Default (ALWAYS REQUIRED for Dalam Negeri)
            if (fileRekomendasi) fileRekomendasi.setAttribute('required', 'required');
            if (rekomendasiRequired) rekomendasiRequired.classList.remove('hidden');

            // NEW: Rekam Didik Required for Dalam Negeri (with NISN)
            if (fileRekamDidik) fileRekamDidik.setAttribute('required', 'required');
            if (rekamDidikRequired) rekamDidikRequired.classList.remove('hidden');
        }
    };

    // File Validation & Compression
    window.validateFile = async function (input, maxMB) {
        if (input.files && input.files[0]) {
            let file = input.files[0];

            // Check if image and compress if needed
            if (file.type.match(/image.*/) && window.ImageCompressor) {
                // Show loading state (optional UI enhancement, simplistic here)
                const originalText = input.nextElementSibling ? input.nextElementSibling.textContent : '';
                if (input.nextElementSibling) input.nextElementSibling.textContent = 'Mengompres...';

                try {
                    const compressedFile = await window.ImageCompressor.compress(file);

                    // Replace file in input (need DataTransfer for FileList)
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(compressedFile);
                    input.files = dataTransfer.files;
                    file = compressedFile; // update reference

                    console.log(`File compressed: ${file.size} bytes`);
                } catch (e) {
                    console.error('Compression failed:', e);
                } finally {
                    if (input.nextElementSibling) input.nextElementSibling.textContent = originalText;
                }
            }

            const fileSize = file.size;
            const maxSize = maxMB * 1024 * 1024;

            if (fileSize > maxSize) {
                alert(`⚠️ Ukuran file terlalu besar!\n\nMaksimal: ${maxMB} MB\nUkuran file Anda: ${(fileSize / (1024 * 1024)).toFixed(2)} MB`);
                input.value = '';
            }
            updateTotalSize();
        }
    };

    function updateTotalSize() {
        let total = 0;
        document.querySelectorAll('input[type="file"]').forEach(input => {
            if (input.files[0]) total += input.files[0].size;
        });
        const sizeDisplay = document.getElementById('totalUploadSize');
        if (sizeDisplay) {
            const sizeMB = total / (1024 * 1024);
            sizeDisplay.textContent = sizeMB.toFixed(2) + ' MB';
            if (total > 128 * 1024 * 1024) { // 128MB warning
                sizeDisplay.classList.add('text-red-600', 'font-bold');
                sizeDisplay.innerText += ' (⚠️ Batas Server)';
            } else {
                sizeDisplay.classList.remove('text-red-600', 'font-bold');
            }
        }
    }

    // NPSN Lookup
    let npsnTimeouts = {};
    window.lookupNpsnAsal = function (index, npsn) {
        const statusIconEl = document.getElementById(`npsn_status_icon_${index}`);
        const statusEl = document.getElementById(`npsn_status_${index}`);
        const namaEl = document.getElementById(`nama_asal_${index}`);
        const alamatEl = document.getElementById(`alamat_asal_${index}`);
        const npsnDitemukanEl = document.getElementById(`npsn_ditemukan_${index}`);
        const provSelect = document.getElementById(`provinsi_asal_select_${index}`);
        const kabSelect = document.getElementById(`kabkota_asal_select_${index}`);
        const provHidden = document.getElementById(`provinsi_asal_${index}`);
        const kabHidden = document.getElementById(`kabkota_asal_${index}`);
        const rekomendasiRequired = document.getElementById(`rekomendasi_required_${index}`);
        const fileRekomendasi = document.getElementById(`file_rekomendasi_${index}`);

        if (npsn.length < 8) {
            if (statusIconEl) statusIconEl.innerHTML = '';
            if (statusEl) statusEl.innerHTML = '8 digit NPSN';
            return;
        }

        clearTimeout(npsnTimeouts[index]);
        if (statusIconEl) statusIconEl.innerHTML = '<i class="bi bi-hourglass-split text-gray-400"></i>';

        npsnTimeouts[index] = setTimeout(async () => {
            try {
                // Use the API route from Config
                const response = await fetch(`${CONFIG.routes.apiNpsn}/${npsn}`);
                const data = await response.json();

                if (data.found && data.success && data.data) {
                    const result = data.data;

                    // Success Found
                    if (namaEl) {
                        namaEl.value = result.nama || '';
                        namaEl.readOnly = true;
                        namaEl.classList.add('bg-gray-100');
                    }
                    if (alamatEl) {
                        alamatEl.value = result.alamat_lengkap || '';
                        if (result.alamat_lengkap) {
                            alamatEl.readOnly = true;
                            alamatEl.classList.add('bg-gray-100');
                        } else {
                            alamatEl.readOnly = false;
                            alamatEl.classList.remove('bg-gray-100');
                        }
                    }

                    // Auto Select Province & City
                    if (result.provinsi && provSelect) {
                        // Match province with loaded options
                        let provOptions = [...provSelect.options];
                        let exactProvOpt = provOptions.find(o => o.text.trim().toLowerCase() === result.provinsi.trim().toLowerCase());

                        if (exactProvOpt) {
                            provSelect.value = exactProvOpt.value;
                            if (provHidden) provHidden.value = exactProvOpt.value;

                            // Trigger change to load cities
                            provSelect.dispatchEvent(new Event('change'));

                            // Wait for cities to load from API
                            const checkCityInterval = setInterval(() => {
                                if (kabSelect && kabSelect.options.length > 1 && !kabSelect.disabled) {
                                    clearInterval(checkCityInterval);

                                    // Match city
                                    let cityOptions = [...kabSelect.options];
                                    let exactCityOpt = cityOptions.find(o => o.text.trim().toLowerCase() === result.kota.trim().toLowerCase());

                                    if (exactCityOpt) {
                                        kabSelect.value = exactCityOpt.value;
                                        if (kabHidden) kabHidden.value = exactCityOpt.value;
                                    }

                                    // Lock fields
                                    if (provSelect) { provSelect.disabled = true; provSelect.classList.add('bg-gray-100'); }
                                    if (kabSelect) { kabSelect.disabled = true; kabSelect.classList.add('bg-gray-100'); }
                                }
                            }, 100);

                            // Timeout fallback to stop checking
                            setTimeout(() => clearInterval(checkCityInterval), 5000);
                        }
                    }

                    if (npsnDitemukanEl) npsnDitemukanEl.value = '1';

                    // Rekomendasi tetap WAJIB untuk semua siswa dalam negeri
                    // (tidak perlu toggle berdasarkan lokasi lagi)

                    if (statusIconEl) statusIconEl.innerHTML = '<i class="bi bi-check-circle text-green-600"></i>';

                    // Securely set text content to prevent XSS from API data
                    if (statusEl) {
                        statusEl.innerHTML = '';
                        const checkSpan = document.createElement('span');
                        checkSpan.className = 'text-green-600';
                        checkSpan.textContent = `✓ Ditemukan: ${result.nama || ''}`;
                        statusEl.appendChild(checkSpan);
                    }
                } else {
                    // Not Found -> Manual Mode
                    resetSekolahAsal(index);
                    // Unlock everything
                    if (namaEl) { namaEl.readOnly = false; namaEl.classList.remove('bg-gray-100'); }
                    if (alamatEl) { alamatEl.readOnly = false; alamatEl.classList.remove('bg-gray-100'); }
                    if (provSelect) { provSelect.disabled = false; provSelect.classList.remove('bg-gray-100'); }
                    if (kabSelect) { kabSelect.disabled = false; kabSelect.classList.remove('bg-gray-100'); }

                    // Keep the NPSN in input
                    const npsnInput = document.getElementById(`npsn_asal_${index}`);
                    if (npsnInput) npsnInput.value = npsn;

                    if (statusIconEl) statusIconEl.innerHTML = '<i class="bi bi-exclamation-circle text-yellow-500"></i>';
                    if (statusEl) statusEl.innerHTML = '<span class="text-yellow-600">⚠ Tidak ditemukan. Silakan isi manual & upload rekomendasi.</span>';
                }
            } catch (error) {
                console.error('NPSN Lookup Error:', error);

                // Error -> Unlock everything for manual
                resetSekolahAsal(index);
                if (namaEl) { namaEl.readOnly = false; namaEl.classList.remove('bg-gray-100'); }
                if (alamatEl) { alamatEl.readOnly = false; alamatEl.classList.remove('bg-gray-100'); }
                if (provSelect) { provSelect.disabled = false; provSelect.classList.remove('bg-gray-100'); }
                if (kabSelect) { kabSelect.disabled = false; kabSelect.classList.remove('bg-gray-100'); }

                if (statusIconEl) statusIconEl.innerHTML = '<i class="bi bi-x-circle text-red-500"></i>';
                if (statusEl) statusEl.innerHTML = '<span class="text-red-500">Gagal terhubung server</span>';
            }
        }, 500);
    };

    // Lookup NPSN Tujuan (For Dinas/SuperAdmin in Mutasi Masuk)
    let npsnTujuanTimeout;
    window.lookupNpsnTujuan = function (npsn) {
        const npsnInput = document.getElementById('npsn_tujuan_input');
        const statusEl = document.getElementById('npsn_tujuan_status');
        const namaEl = document.getElementById('nama_tujuan_input');
        const alamatEl = document.getElementById('alamat_tujuan_input');
        const provEl = document.getElementById('provinsi_tujuan_input'); // Optional
        const kabEl = document.getElementById('kabupaten_kota_tujuan_input'); // Optional

        // Return if elements don't exist
        if (!namaEl || !alamatEl) return;

        if (npsn.length < 8) {
            statusEl.textContent = '';
            statusEl.className = 'absolute right-3 top-1/2 -translate-y-1/2';
            return;
        }

        clearTimeout(npsnTujuanTimeout);
        statusEl.innerHTML = '<i class="bi bi-hourglass-split text-gray-400"></i>';

        npsnTujuanTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`${CONFIG.routes.apiNpsn}/${npsn}`);
                const data = await response.json();

                if (data.found && data.success && data.data) {
                    const result = data.data;

                    // FOUND: Auto-fill and Lock
                    statusEl.innerHTML = '<i class="bi bi-check-circle text-green-600"></i>';

                    namaEl.value = result.nama || '';
                    namaEl.readOnly = true;
                    namaEl.classList.add('bg-gray-100');

                    alamatEl.value = result.alamat_lengkap || '';
                    if (result.alamat_lengkap) {
                        alamatEl.readOnly = true;
                        alamatEl.classList.add('bg-gray-100');
                    } else {
                        alamatEl.readOnly = false;
                        alamatEl.classList.remove('bg-gray-100');
                    }

                    // Optional: Province & City (if elements exist)
                    if (provEl) {
                        provEl.value = result.provinsi || '';
                        if (result.provinsi) {
                            provEl.readOnly = true;
                            provEl.classList.add('bg-gray-100');
                        } else {
                            provEl.readOnly = false;
                            provEl.classList.remove('bg-gray-100');
                        }
                    }
                    if (kabEl) {
                        kabEl.value = result.kota || '';
                        if (result.kota) {
                            kabEl.readOnly = true;
                            kabEl.classList.add('bg-gray-100');
                        } else {
                            kabEl.readOnly = false;
                            kabEl.classList.remove('bg-gray-100');
                        }
                    }

                } else {
                    // NOT FOUND: Unlock for Manual Input
                    statusEl.innerHTML = '<i class="bi bi-exclamation-circle text-yellow-500"></i>';

                    namaEl.readOnly = false;
                    namaEl.classList.remove('bg-gray-100');
                    namaEl.value = '';

                    alamatEl.readOnly = false;
                    alamatEl.classList.remove('bg-gray-100');
                    alamatEl.value = '';

                    if (provEl) {
                        provEl.readOnly = false;
                        provEl.classList.remove('bg-gray-100');
                        provEl.value = '';
                    }
                    if (kabEl) {
                        kabEl.readOnly = false;
                        kabEl.classList.remove('bg-gray-100');
                        kabEl.value = '';
                    }
                }
            } catch (error) {
                console.error('NPSN Tujuan Lookup Error:', error);
                statusEl.innerHTML = '<i class="bi bi-x-circle text-red-500"></i>';

                // Error Fallback: Unlock
                namaEl.readOnly = false;
                namaEl.classList.remove('bg-gray-100');

                alamatEl.readOnly = false;
                alamatEl.classList.remove('bg-gray-100');

                if (provEl) { provEl.readOnly = false; provEl.classList.remove('bg-gray-100'); }
                if (kabEl) { kabEl.readOnly = false; kabEl.classList.remove('bg-gray-100'); }
            }
        }, 500);
    };

    // --- Main Logic on Load ---
    document.addEventListener('DOMContentLoaded', () => {
        form = document.getElementById('formMutasiMasuk');
        jumlahInput = document.getElementById('jumlah_siswa');
        container = document.getElementById('siswaContainer');
        tabNav = document.getElementById('siswaTabNav');
        navButtons = document.getElementById('siswaNavButtons');
        prevBtn = document.getElementById('prevSiswa');
        nextBtn = document.getElementById('nextSiswa');

        function renderTabs(count) {
            if (!tabNav) return;
            tabNav.innerHTML = '';
            for (let i = 0; i < count; i++) {
                const tab = document.createElement('button');
                tab.type = 'button';
                tab.className = `siswa-tab w-8 h-8 rounded-full text-sm font-medium transition ${i === 0 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'}`;
                tab.textContent = i + 1;
                tab.onclick = () => showSiswa(i);
                tabNav.appendChild(tab);
            }
        }

        function showSiswa(index) {
            document.querySelectorAll('.siswa-card').forEach((card, i) => {
                card.classList.toggle('hidden', i !== index);
            });
            document.querySelectorAll('.siswa-tab').forEach((tab, i) => {
                if (i === index) {
                    tab.classList.remove('bg-gray-200', 'text-gray-700');
                    tab.classList.add('bg-primary-600', 'text-white');
                } else {
                    tab.classList.remove('bg-primary-600', 'text-white');
                    tab.classList.add('bg-gray-200', 'text-gray-700');
                }
            });
            currentSiswa = index;
            updateNavButtons();
        }

        function updateNavButtons() {
            if (!prevBtn || !nextBtn) return;
            prevBtn.disabled = currentSiswa === 0;
            prevBtn.classList.toggle('opacity-50', currentSiswa === 0);
            nextBtn.disabled = currentSiswa >= totalSiswa - 1;
            nextBtn.classList.toggle('opacity-50', currentSiswa >= totalSiswa - 1);
        }

        function renderSiswaForms(count) {
            if (!container) return;

            // Optimization: Build string first then inject once
            let htmlContent = '';
            for (let i = 0; i < count; i++) {
                htmlContent += generateSiswaForm(i);
            }
            container.innerHTML = htmlContent;

            for (let i = 0; i < count; i++) {
                setupSiswaEvents(i);
            }
            totalSiswa = count;
            currentSiswa = 0;
            renderTabs(count);
            if (navButtons) navButtons.classList.toggle('hidden', count <= 1);
            updateNavButtons();

            // Re-apply old values if any
            if (OLD_VALUES && Object.keys(OLD_VALUES).length > 0) {
                restoreOldValues();
            }
        }

        function restoreOldValues() {
            Object.keys(OLD_VALUES).forEach(idx => {
                const siswa = OLD_VALUES[idx];
                // Restore logic here (simplified for brevity but covering key fields)
                // Text Inputs
                ['nama_murid', 'nisn', 'nis', 'nama_sekolah_asal', 'alamat_sekolah_asal'].forEach(f => {
                    const el = document.querySelector(`[name="siswa[${idx}][${f}]"]`);
                    if (el && siswa[f]) el.value = siswa[f];
                });
                // Checkboxes
                if (siswa['asal_luar_negeri'] == '1') {
                    const cb = document.getElementById(`luar_negeri_${idx}`);
                    if (cb) { cb.checked = true; window.toggleLuarNegeri(idx); }
                }
                // Province/City (manual trigger required)
                if (siswa['provinsi_asal']) {
                    const sel = document.getElementById(`provinsi_asal_select_${idx}`);
                    if (sel) {
                        sel.value = siswa['provinsi_asal'];
                        sel.dispatchEvent(new Event('change'));
                        setTimeout(() => {
                            const kab = document.getElementById(`kabkota_asal_select_${idx}`);
                            if (kab && siswa['kabupaten_kota_asal']) kab.value = siswa['kabupaten_kota_asal'];
                        }, 100);
                    }
                }
            });
        }

        // Initialize Form
        if (jumlahInput) {
            // Restore jumlah from OLD_JUMLAH
            jumlahInput.value = OLD_JUMLAH;
            renderSiswaForms(parseInt(OLD_JUMLAH) || 1);

            jumlahInput.addEventListener('change', function () {
                const count = Math.min(Math.max(parseInt(this.value) || 1, 1), 20);
                this.value = count;
                renderSiswaForms(count);
            });
        }

        if (prevBtn) prevBtn.addEventListener('click', () => { if (currentSiswa > 0) showSiswa(currentSiswa - 1); });
        if (nextBtn) nextBtn.addEventListener('click', () => { if (currentSiswa < totalSiswa - 1) showSiswa(currentSiswa + 1); });

        // Prevent double submit
        if (form) {
            form.addEventListener('submit', function () {
                const btn = document.getElementById('btnSubmit');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-2"></i> Memproses...';
                }
            });
        }

    });

})();