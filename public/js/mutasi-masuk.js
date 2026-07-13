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

    // Draft API State
    let draftId = CONFIG.draftId || null;
    let isSaving = false;
    let savedStudents = new Set();

    // API Endpoints
    const API = {
        createDraft: CONFIG.routes?.createDraft || '/mutasi-masuk/draft',
        addStudent: (id) => `/mutasi-masuk/draft/${id}/siswa`,
        finalize: (id) => `/mutasi-masuk/draft/${id}/finalize`
    };

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
                            class="w-full px-4 py-2 border rounded-lg bg-white" onchange="window.validateFile(this, 1)">
                        <p class="text-xs text-gray-500 italic mt-1">Silahkan koordinasi dengan bagian dapodik Dinas Pendidikan Kota Bogor untuk screenshot Rekam Didik. PDF/JPEG/PNG. Maks 1MB.</p>
                    </div>
                </div>`}
            </div>
        </div>
        `;
    }

    // Load provinces
    function loadProvinces(selectId) {
        const select = document.getElementById(selectId);
        if (!select) return Promise.resolve();

        // If already loaded (more than default option), return immediately
        if (select.options.length > 1) return Promise.resolve();

        select.innerHTML = '<option value="">Pilih Provinsi</option>';

        return fetch(CONFIG.routes.apiProvinces)
            .then(response => response.json())
            .then(result => {
                if (result.success && result.data) {
                    result.data.forEach(prov => {
                        const option = document.createElement('option');
                        option.value = prov.name;
                        option.dataset.id = prov.id;
                        option.textContent = prov.name;
                        select.appendChild(option);
                    });
                }
            })
            .catch(e => {
                console.error('Failed to load provinces', e);
            });
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
                                const kabName = kab.name.replace(/Kabupaten/g, 'Kab.');
                                option.value = kabName;
                                option.textContent = kabName;
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

            // Files Required (ONLY IF NOT PREVIOUSLY SAVED)
            const setRequiredIfNoFile = (input) => {
                if (input && input.getAttribute('data-has-file') !== 'true') {
                    input.setAttribute('required', 'required');
                }
            };

            setRequiredIfNoFile(suratPindahInput);
            setRequiredIfNoFile(suratDapodikInput);

            // NEW: Reset Rekomendasi to Default (ALWAYS REQUIRED for Dalam Negeri)
            setRequiredIfNoFile(fileRekomendasi);
            if (rekomendasiRequired) rekomendasiRequired.classList.remove('hidden');

            // NEW: Rekam Didik Required for Dalam Negeri (with NISN)
            setRequiredIfNoFile(fileRekamDidik);
            if (rekamDidikRequired) rekamDidikRequired.classList.remove('hidden');
        }
    };

    // File Validation & Immediate Upload (Upload-on-Select Pattern)
    // This uploads files to temp storage immediately when selected, avoiding payload limits
    window.validateFile = async function (input, maxMB) {
        if (input.files && input.files[0]) {
            let file = input.files[0];

            // Check if image and compress if needed
            if (file.type.match(/image.*/) && window.ImageCompressor) {
                const originalText = input.nextElementSibling ? input.nextElementSibling.textContent : '';
                if (input.nextElementSibling) input.nextElementSibling.textContent = 'Mengompres...';

                try {
                    const compressedFile = await window.ImageCompressor.compress(file);
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(compressedFile);
                    input.files = dataTransfer.files;
                    file = compressedFile;
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
                return;
            }

            // ============================================
            // ASYNC UPLOAD: Upload immediately to server
            // ============================================
            await uploadFileImmediately(input, file);

            updateTotalSize();
        }
    };

    /**
     * Upload file immediately to /api/upload-temp
     * Creates hidden input with temp path, marks file input as uploaded
     */
    /**
     * Upload file immediately
     * Supports Direct Upload to B2 (bypass server) or Legacy Upload (local)
     */
    async function uploadFileImmediately(input, file) {
        const parentNode = input.parentNode;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Create/update status indicator
        let statusEl = parentNode.querySelector('.upload-status-indicator');
        if (!statusEl) {
            statusEl = document.createElement('div');
            statusEl.className = 'upload-status-indicator mt-2 text-xs font-mono bg-gray-50 border rounded p-2 overflow-auto max-h-48';
            parentNode.insertBefore(statusEl, input.nextSibling);
        }

        // Helper to log to UI
        const log = (msg, type = 'info') => {
            const colors = { info: 'text-gray-600', success: 'text-green-600 font-bold', error: 'text-red-600 font-bold', warning: 'text-orange-600' };
            const time = new Date().toLocaleTimeString('en-GB', { hour12: false });
            statusEl.innerHTML += `<div class="${colors[type]} mb-1">[${time}] ${msg}</div>`;
            statusEl.scrollTop = statusEl.scrollHeight;
        };

        // Reset Log
        statusEl.innerHTML = '<div class="font-bold border-b mb-2 pb-1">Upload Diagnostics</div>';

        input.classList.add('opacity-50');
        input.disabled = true;

        try {
            log(`File: ${file.name} (${(file.size / 1024).toFixed(1)} KB)`);

            // --- Step 1: Initialize Upload (Get URL) ---
            log('Initializing upload...');
            const initResponse = await fetch('/ajax/upload-temp-url', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    filename: file.name,
                    content_type: file.type
                })
            });

            if (!initResponse.ok) {
                const errorMessage = await readErrorMessage(initResponse);
                throw new Error(errorMessage || `Gagal menyiapkan upload (${initResponse.status})`);
            }

            const initResult = await initResponse.json();
            log('Upload initialized.', 'success');

            let finalPath = '';
            let uploadSuccess = false;

            // --- Step 2: Attempt Direct Upload (if available) ---
            if (initResult.success === true && initResult.upload_url && initResult.method === 'PUT') {
                log('Attempting Direct Cloud Upload...');

                try {
                    // NOTE: removing unneeded headers that might trigger CORS if not allowed
                    // The 'Content-Type' is usually required and signed.
                    const headers = initResult.headers || {};

                    const uploadResponse = await fetch(initResult.upload_url, {
                        method: 'PUT',
                        headers: headers,
                        body: file
                    });

                    if (!uploadResponse.ok) throw new Error(`Status ${uploadResponse.status}`);

                    finalPath = initResult.relative_path;
                    uploadSuccess = true;
                    log('Direct Upload Success!', 'success');

                } catch (directError) {
                    log(`Direct Upload Failed: ${directError.message}`, 'warning');
                    log('Switching to Server Relay (Fallback)...', 'warning');
                    // Fallback will proceed below
                }
            }


            // --- Step 3: Fallback / Legacy Upload ---
            // If direct upload was not attempted OR failed
            if (!uploadSuccess) {
                if (!initResult.is_local && !initResult.use_check_upload) {
                    // Direct upload failed, and we shouldn't use server upload if it's large
                }

                log(`Uploading via Server Relay...`);

                // VERCEL PAYLOAD LIMIT CHECK
                // If file is > 4MB, server upload will fail with 413.
                // We must abort here if direct upload failed and file is too large.
                if (file.size > 4 * 1024 * 1024) {
                    throw new Error('Direct Upload gagal & File terlalu besar untuk server (>4MB). Mohon cek koneksi atau hubungi admin (CORS issue?).');
                }

                const formData = new FormData();
                formData.append('file', file);

                const legacyResponse = await fetch('/ajax/upload-temp', {
                    method: 'POST', // Use POST for server upload
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: formData
                });


                if (!legacyResponse.ok) {
                    const errorMessage = await readErrorMessage(legacyResponse);
                    throw new Error(errorMessage || `Upload gagal di server (${legacyResponse.status})`);
                }

                const legacyResult = await legacyResponse.json();
                if (!legacyResult.success) throw new Error(legacyResult.message || 'Unknown server error');

                // If the server upload returns a path
                finalPath = legacyResult.path;
                uploadSuccess = true;
                log('Server Upload Success!', 'success');
            }

            // --- Step 4: Finalize ---
            if (uploadSuccess && finalPath) {
                // Finalize Input
                const existingHidden = document.querySelector(`input[type="hidden"][name="${input.name}"]`);
                if (existingHidden) existingHidden.remove();

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = input.name;
                hiddenInput.value = finalPath;
                hiddenInput.dataset.tempPath = 'true';
                parentNode.appendChild(hiddenInput);

                input.setAttribute('data-has-file', 'true');
                input.removeAttribute('required');

                // Simplified Success UI
                statusEl.innerHTML = `
                       <div class="flex items-center gap-2 text-emerald-600 bg-emerald-50 px-3 py-2 rounded-lg mt-2">
                           <i class="bi bi-check-circle-fill"></i>
                           <div class="flex flex-col">
                             <span class="font-bold text-sm">Upload Berhasil</span>
                             <span class="text-xs text-slate-500">Berkas tersimpan sementara</span>
                           </div>
                       </div>
                   `;
            } else {
                throw new Error('Upload verification failed (no path returned)');
            }

        } catch (error) {
            console.error(error);
            log(`CRITICAL ERROR: ${error.message}`, 'error');
            input.value = ''; // Reset

            // Show more helpful message for common errors
            if (error.message.includes('Failed to fetch')) {
                log('Hint: Koneksi terputus atau diblokir browser.', 'warning');
            }
        } finally {
            input.classList.remove('opacity-50');
            input.disabled = false;
        }
    }

    async function readErrorMessage(response) {
        try {
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const payload = await response.json();
                return payload.message || (payload.errors ? Object.values(payload.errors).flat()[0] : null);
            }
            const text = await response.text();
            return text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 180);
        } catch (e) {
            return null;
        }
    }

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
                                    let kotaNormalized = result.kota.replace(/Kabupaten/g, 'Kab.');
                                    let exactCityOpt = cityOptions.find(o => o.text.trim().toLowerCase() === kotaNormalized.trim().toLowerCase());

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

    // --- Sequential Upload Logic ---
    async function uploadFilesSequentially() {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        const filesToUpload = [];

        // Identify files
        fileInputs.forEach(input => {
            if (input.files.length > 0) {
                // Skip if already has a hidden input replacement (double submit protection)
                if (!document.querySelector(`input[name="${input.name}"][type="hidden"]`)) {
                    filesToUpload.push(input);
                }
            }
        });

        if (filesToUpload.length === 0) return true;

        // Show Progress Modal
        const modalHtml = `
            <div id="uploadProgressModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 z-50 flex items-center justify-center">
                <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full text-center">
                    <div class="mb-4">
                        <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin mx-auto"></div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Mengunggah Berkas...</h3>
                    <p class="text-gray-600 mb-6">Mohon jangan tutup halaman ini.</p>
                    
                    <div class="w-full bg-gray-200 rounded-full h-4 mb-2 overflow-hidden">
                        <div id="uploadProgressBar" class="bg-blue-600 h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p id="uploadProgressText" class="text-sm font-medium text-gray-700">0 / ${filesToUpload.length}</p>
                    <p id="currentFileText" class="text-xs text-gray-400 mt-2 truncate"></p>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);

        const progressBar = document.getElementById('uploadProgressBar');
        const progressText = document.getElementById('uploadProgressText');
        const currentFileText = document.getElementById('currentFileText');
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let successCount = 0;

        for (let i = 0; i < filesToUpload.length; i++) {
            const input = filesToUpload[i];
            const file = input.files[0];

            // Update UI
            const percent = Math.round((i / filesToUpload.length) * 100);
            progressBar.style.width = `${percent}%`;
            progressText.textContent = `${i + 1} / ${filesToUpload.length}`;
            currentFileText.textContent = `Mengunggah: ${file.name}`;

            try {
                const formData = new FormData();
                formData.append('file', file);

                const response = await fetch('/api/upload-temp', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    body: formData
                });

                if (!response.ok) throw new Error('Upload failed');

                const result = await response.json();

                if (result.success && result.path) {
                    // Create hidden input with temp path
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = input.name; // Keep same name for controller
                    hiddenInput.value = result.path;
                    form.appendChild(hiddenInput);

                    // Disable original input so it's not sent
                    input.disabled = true;
                    successCount++;
                } else {
                    throw new Error(result.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Upload Error:', error);
                alert(`Gagal mengunggah file: ${file.name}\n${error.message}`);
                const modal = document.getElementById('uploadProgressModal');
                if (modal) modal.remove();

                // Re-enable all inputs if failed
                filesToUpload.forEach(inp => inp.disabled = false);
                document.querySelectorAll(`input[type="hidden"][value^="temp_uploads/"]`).forEach(el => el.remove());

                return false; // Stop submission
            }
        }

        // Finish
        progressBar.style.width = '100%';
        currentFileText.textContent = 'Selesai! Mengirim data...';
        await new Promise(r => setTimeout(r, 500)); // Brief pause

        return true;
    }

    // --- Main Logic on Load ---
    document.addEventListener('DOMContentLoaded', () => {
        form = document.getElementById('formMutasiMasuk');
        jumlahInput = document.getElementById('jumlah_siswa');
        container = document.getElementById('siswaContainer');
        tabNav = document.getElementById('siswaTabNav');
        navButtons = document.getElementById('siswaNavButtons');
        prevBtn = document.getElementById('prevSiswa');
        nextBtn = document.getElementById('nextSiswa');

        if (form) {
            form.addEventListener('submit', async function (e) {
                // Prevent default submission
                e.preventDefault();

                // Validate form (native HTML validation check)
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                // Check for pending uploads (still showing "Mengunggah...")
                const pendingUploads = document.querySelectorAll('.upload-status-indicator .bi-arrow-repeat');
                if (pendingUploads.length > 0) {
                    alert('⏳ Mohon tunggu hingga semua file selesai diunggah.');
                    return;
                }

                // Check for failed uploads
                const failedUploads = document.querySelectorAll('.upload-status-indicator .bi-x-circle-fill');
                if (failedUploads.length > 0) {
                    alert('❌ Ada file yang gagal diunggah. Silakan pilih ulang file yang gagal.');
                    return;
                }

                if (isSaving) return; // Prevent double click
                isSaving = true;

                // Show loading modal
                const modal = document.getElementById('loadingModal');
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                // Disable file inputs to prevent them being sent (hidden inputs will be sent instead)
                document.querySelectorAll('input[type="file"][data-has-file="true"]').forEach(input => {
                    input.disabled = true;
                });

                // Submit form (files already uploaded via temp path hidden inputs)
                form.submit();
            });
        }


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

        async function showSiswa(index) {
            // Don't do anything if already on this tab or currently saving
            if (index === currentSiswa) return;
            if (isSaving) {
                showSaveIndicator('⏳ Mohon tunggu, sedang menyimpan...', 'info');
                return;
            }

            // VALIDATION: Check if current student data is complete before switching forward
            if (index > currentSiswa && totalSiswa > 1) {
                const validationResult = validateCurrentStudent(currentSiswa);
                if (!validationResult.valid) {
                    showValidationError(validationResult.errors, currentSiswa);
                    return; // Block switch
                }
            }

            // BLOCKING SAVE: Save current student before switching (wait for completion)
            if (totalSiswa > 1) {
                // Ensure draft exists first
                if (!draftId) {
                    showSaveIndicator('Membuat draft...', 'info');
                    draftId = await createDraftIfNeeded();
                    if (!draftId) {
                        showSaveIndicator('❌ Gagal membuat draft. Pastikan data header sudah lengkap.', 'error');
                        return; // Block switch if draft creation failed
                    }
                }

                // Save current student and WAIT for it to complete
                const saveSuccess = await saveCurrentStudent(currentSiswa);
                if (!saveSuccess) {
                    // Check if the student has any data (skip empty students)
                    const card = document.querySelector(`.siswa-card[data-index="${currentSiswa}"]`);
                    const namaInput = card?.querySelector('[name*="[nama_murid]"]');
                    if (namaInput?.value?.trim()) {
                        // Has data but failed to save - block navigation
                        showSaveIndicator('❌ Gagal menyimpan data. Silakan coba lagi.', 'error');
                        return;
                    }
                    // Empty student, allow navigation
                }
            }

            // Switch tabs visually
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

        /**
         * Validate current student's required fields
         * Returns { valid: boolean, errors: string[] }
         */
        function validateCurrentStudent(index) {
            const card = document.querySelector(`.siswa-card[data-index="${index}"]`);
            if (!card) return { valid: true, errors: [] };

            const errors = [];

            // Basic student info - always required for all roles
            const basicFields = [
                { name: 'nama_murid', label: 'Nama Siswa' },
                { name: 'nisn', label: 'NISN' },
                { name: 'tempat_lahir', label: 'Tempat Lahir' },
                { name: 'tanggal_lahir', label: 'Tanggal Lahir' },
                { name: 'jenis_kelamin', label: 'Jenis Kelamin' },
                { name: 'kelas', label: 'Kelas' },
                { name: 'tanggal_diterima', label: 'Tanggal Diterima' }
            ];

            basicFields.forEach(field => {
                const input = card.querySelector(`[name*="[${field.name}]"]`);
                if (input && !input.value.trim()) {
                    errors.push(field.label);
                }
            });

            // School origin - always required
            const schoolFields = [
                { name: 'nama_sekolah_asal', label: 'Nama Sekolah Asal' },
                { name: 'provinsi_asal', label: 'Provinsi Asal' },
                { name: 'kabupaten_kota_asal', label: 'Kab/Kota Asal' },
                { name: 'alamat_sekolah_asal', label: 'Alamat Sekolah Asal' }
            ];

            schoolFields.forEach(field => {
                // Check hidden inputs for province/city
                let input = card.querySelector(`[name*="[${field.name}]"]`);
                if (!input) {
                    input = document.getElementById(`${field.name.replace('_asal', '')}_asal_${index}`);
                }
                if (input && !input.value.trim()) {
                    errors.push(field.label);
                }
            });

            // File uploads - required for non-Dinas roles (Sekolah, Operator)
            if (!isDinasEntry) {
                const isLuarNegeri = card.querySelector(`[name*="[asal_luar_negeri]"]`)?.checked;

                const fileFields = [
                    { name: 'file_surat_pindah', label: 'Surat Pindah', required: !isLuarNegeri },
                    { name: 'file_surat_dapodik', label: 'Surat Dapodik', required: !isLuarNegeri },
                    { name: 'file_rekomendasi', label: 'Rekomendasi Dinas/Kemenag', required: !isLuarNegeri },
                    { name: 'file_ijazah_sd', label: 'Ijazah SD', required: true },
                    { name: 'file_rapor', label: 'Rapor', required: true },
                    { name: 'file_surat_penyaluran', label: 'Surat Penyaluran', required: isLuarNegeri },
                    { name: 'file_rekam_didik', label: 'Rekam Didik', required: !isLuarNegeri }
                ];

                fileFields.forEach(field => {
                    if (field.required) {
                        const input = card.querySelector(`[name*="[${field.name}]"]`);
                        // Fix: Check if file already exists (data-has-file) OR new file is selected
                        const hasExistingFile = input && input.getAttribute('data-has-file') === 'true';
                        const hasNewFile = input && input.files && input.files.length > 0;

                        if (input && !hasExistingFile && !hasNewFile) {
                            errors.push(field.label);
                        }
                    }
                });
            } else {
                // Dinas Entry - check checkboxes
                const requiredCheckboxes = [
                    { name: 'has_surat_pindah', label: 'Konfirmasi Surat Pindah' },
                    { name: 'has_surat_dapodik', label: 'Konfirmasi Surat Dapodik' },
                    { name: 'has_ijazah_sd', label: 'Konfirmasi Ijazah SD' },
                    { name: 'has_rapor', label: 'Konfirmasi Rapor' }
                ];

                requiredCheckboxes.forEach(field => {
                    const input = card.querySelector(`[name*="[${field.name}]"]`);
                    if (input && !input.checked) {
                        errors.push(field.label);
                    }
                });
            }

            return {
                valid: errors.length === 0,
                errors: errors
            };
        }

        /**
         * Show validation error modal
         */
        function showValidationError(errors, studentIndex) {
            const errorList = errors.map(e => `• ${e}`).join('\n');
            const message = `Data Siswa #${studentIndex + 1} belum lengkap!\n\nField yang harus diisi:\n${errorList}\n\nSilakan lengkapi data sebelum melanjutkan ke siswa berikutnya.`;

            // Show using existing notification system or alert
            if (typeof showSaveIndicator === 'function') {
                showSaveIndicator(`❌ Data Siswa #${studentIndex + 1} belum lengkap!`, 'error');
            }

            alert(message);
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

        async function restoreOldValues() {
            const indices = Object.keys(OLD_VALUES);
            for (const idx of indices) {
                const siswa = OLD_VALUES[idx];

                // 1. Text Inputs (Restore standard fields)
                ['nama_murid', 'nisn', 'nis', 'nama_sekolah_asal', 'alamat_sekolah_asal'].forEach(f => {
                    const el = document.querySelector(`[name="siswa[${idx}][${f}]"]`);
                    if (el && siswa[f]) el.value = siswa[f];
                });

                // 2. Checkboxes (Luar Negeri)
                if (siswa['asal_luar_negeri'] == '1') {
                    const cb = document.getElementById(`luar_negeri_${idx}`);
                    if (cb) {
                        cb.checked = true;
                        window.toggleLuarNegeri(idx);
                    }
                }

                // 3. Province & City (Async Restoration)
                const provSelect = document.getElementById(`provinsi_asal_select_${idx}`);
                if (provSelect && siswa['provinsi_asal']) {
                    // Wait for options to be populated (if not already)
                    if (provSelect.options.length <= 1) {
                        await loadProvinces(`provinsi_asal_select_${idx}`);
                    }

                    // Try to find the option by value or text
                    let match = Array.from(provSelect.options).find(o => o.value === siswa['provinsi_asal']);
                    if (!match) {
                        // Try text match
                        match = Array.from(provSelect.options).find(o => o.text.trim().toLowerCase() === siswa['provinsi_asal'].trim().toLowerCase());
                    }

                    if (match) {
                        provSelect.value = match.value;
                    } else {
                        // If not found in list (e.g. data mismatch), force add it to preserve value
                        const opt = document.createElement('option');
                        opt.value = siswa['provinsi_asal'];
                        opt.textContent = siswa['provinsi_asal'];
                        provSelect.appendChild(opt);
                        provSelect.value = siswa['provinsi_asal'];
                    }

                    // Trigger change to load cities
                    provSelect.dispatchEvent(new Event('change'));

                    // 4. Restore City (Wait for city options)
                    if (siswa['kabupaten_kota_asal']) {
                        const kabSelect = document.getElementById(`kabkota_asal_select_${idx}`);
                        if (kabSelect) {
                            // Poll for city options to load
                            let attempts = 0;
                            const checkCityInterval = setInterval(() => {
                                attempts++;
                                // Stop if too many attempts (5s) to prevent infinite loop
                                if (attempts > 50) {
                                    clearInterval(checkCityInterval);
                                    // Fallback: Force add city option if it didn't load
                                    if (kabSelect.options.length <= 1) {
                                        const opt = document.createElement('option');
                                        opt.value = siswa['kabupaten_kota_asal'];
                                        opt.textContent = siswa['kabupaten_kota_asal'];
                                        kabSelect.appendChild(opt);
                                        kabSelect.value = siswa['kabupaten_kota_asal'];
                                    }
                                    return;
                                }

                                if (kabSelect.options.length > 1 && !kabSelect.disabled) {
                                    clearInterval(checkCityInterval);

                                    let kabMatch = Array.from(kabSelect.options).find(o => o.value === siswa['kabupaten_kota_asal']);
                                    if (!kabMatch) {
                                        kabMatch = Array.from(kabSelect.options).find(o => o.text.trim().toLowerCase() === siswa['kabupaten_kota_asal'].trim().toLowerCase());
                                    }

                                    if (kabMatch) {
                                        kabSelect.value = kabMatch.value;
                                    } else {
                                        const opt = document.createElement('option');
                                        opt.value = siswa['kabupaten_kota_asal'];
                                        opt.textContent = siswa['kabupaten_kota_asal'];
                                        kabSelect.appendChild(opt);
                                        kabSelect.value = siswa['kabupaten_kota_asal'];
                                    }
                                }
                            }, 100);
                        }
                    }
                }
            }
        }

        /**
         * Populate form fields from CONFIG.students (when resuming draft)
         */
        /**
         * Populate form fields from CONFIG.students (when resuming draft)
         */
        async function populateDraftStudents() {
            const students = CONFIG.students || [];
            if (!students.length) {
                return;
            }

            // Loop through sequential indices
            for (let idx = 0; idx < students.length; idx++) {
                const student = students[idx];

                // SKIPPING IF OLD VALUES EXIST (Validation Error Priority)
                if (OLD_VALUES && OLD_VALUES[idx]) {
                    console.log(`Skipping draft population for index ${idx} because OLD_VALUES exist.`);
                    continue;
                }

                // Determine if manual entry (not found via NPSN)
                // "npsn_ditemukan" is 1/true if found, 0/false if manual
                const isManual = student.npsn_ditemukan == 0 || student.npsn_ditemukan === '0' || student.npsn_ditemukan === false;

                // 0. FILES HANDLING (Priority - Do this first so it's visible immediately)
                const fileFields = [
                    'file_surat_pindah', 'file_surat_dapodik', 'file_rekomendasi',
                    'file_ijazah_sd', 'file_rapor', 'file_surat_penyaluran', 'file_rekam_didik'
                ];

                fileFields.forEach(field => {
                    const filePath = student[field];
                    if (filePath && typeof filePath === 'string' && filePath.length > 5) {
                        const input = document.querySelector(`[name="siswa[${idx}][${field}]"]`);
                        if (input) {
                            input.setAttribute('data-has-file', 'true');
                            input.removeAttribute('required');

                            // 1. Visual Indicator
                            const existingStatus = input.parentNode.querySelector('.file-status-indicator');
                            if (!existingStatus) {
                                const statusDiv = document.createElement('div');
                                statusDiv.className = 'file-status-indicator mt-2 flex items-center gap-2 text-xs text-emerald-600 bg-emerald-50 p-2 rounded-lg border border-emerald-100';
                                statusDiv.innerHTML = `
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span class="font-bold">File Tersimpan</span>
                                    <span class="text-slate-500">(Upload baru jika ingin mengubah)</span>
                                `;
                                input.parentNode.insertBefore(statusDiv, input.nextSibling);
                            }

                            // 2. HIDDEN INPUT FOR SUBMISSION (Fix for Silent Failure)
                            const existingHidden = document.querySelector(`input[name="siswa[${idx}][${field}]"][type="hidden"]`);
                            if (existingHidden) existingHidden.remove();

                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = `siswa[${idx}][${field}]`;
                            hiddenInput.value = filePath;
                            form.appendChild(hiddenInput);
                        }
                    }
                });

                // 1. TEXT FIELDS
                const textFields = [
                    'nama_murid', 'nisn', 'nis', 'tempat_lahir', 'tanggal_lahir',
                    'tanggal_diterima', 'npsn_sekolah_asal', 'nama_sekolah_asal',
                    'alamat_sekolah_asal'
                ];
                textFields.forEach(field => {
                    const el = document.querySelector(`[name="siswa[${idx}][${field}]"]`);
                    if (el && student[field]) {
                        el.value = student[field];
                    }
                });

                // 2. SELECT FIELDS (Gender, Class)
                ['jenis_kelamin', 'kelas'].forEach(field => {
                    const el = document.querySelector(`[name="siswa[${idx}][${field}]"]`);
                    if (el && student[field]) {
                        el.value = student[field];
                    }
                });

                // 3. MANUAL ENTRY HANDLING (Unlock fields if manual)
                if (isManual) {
                    const namaEl = document.getElementById(`nama_asal_${idx}`);
                    const alamatEl = document.getElementById(`alamat_asal_${idx}`);
                    const provEl = document.getElementById(`provinsi_asal_select_${idx}`); // SELECT
                    const kabEl = document.getElementById(`kabkota_asal_select_${idx}`); // SELECT

                    if (namaEl) {
                        namaEl.readOnly = false;
                        namaEl.classList.remove('bg-gray-100');
                    }
                    if (alamatEl) {
                        alamatEl.readOnly = false;
                        alamatEl.classList.remove('bg-gray-100');
                    }
                    if (provEl) {
                        provEl.readOnly = false;
                        provEl.classList.remove('bg-gray-100');
                    }
                    if (kabEl) {
                        kabEl.readOnly = false;
                        kabEl.classList.remove('bg-gray-100');
                    }

                    // Set status to "Manual Input" visual
                    const statusEl = document.getElementById(`npsn_status_${idx}`);
                    if (statusEl) {
                        statusEl.innerHTML = '<span class="text-yellow-600 text-xs">Input Manual (Restore)</span>';
                    }
                } else {
                    // If found, ensure lock visual is applied (re-apply just in case)
                    const namaEl = document.getElementById(`nama_asal_${idx}`);
                    if (namaEl && namaEl.value) {
                        namaEl.readOnly = true;
                        namaEl.classList.add('bg-gray-100');
                    }
                    const alamatEl = document.getElementById(`alamat_asal_${idx}`);
                    if (alamatEl && alamatEl.value) {
                        alamatEl.readOnly = true;
                        alamatEl.classList.add('bg-gray-100');
                    }
                }

                // 4. PROVINCE & CITY POPULATION (Async & Robust)
                if (student.provinsi_asal) {
                    const provSelect = document.getElementById(`provinsi_asal_select_${idx}`);
                    const provHidden = document.getElementById(`provinsi_asal_${idx}`);

                    if (provSelect) {
                        // Wait for options to be populated (if not already)
                        if (provSelect.options.length <= 1) {
                            await loadProvinces(`provinsi_asal_select_${idx}`);
                        }

                        // Try to find the option by value or text
                        let match = Array.from(provSelect.options).find(o => o.value === student.provinsi_asal);
                        if (!match) {
                            // Try text match (Case insensitive)
                            match = Array.from(provSelect.options).find(o => o.text.trim().toLowerCase() === student.provinsi_asal.trim().toLowerCase());
                        }

                        if (match) {
                            provSelect.value = match.value;
                            if (provHidden) provHidden.value = match.value;
                        } else {
                            // If not found in list (e.g. data mismatch), force add it to preserve value
                            const opt = document.createElement('option');
                            opt.value = student.provinsi_asal;
                            opt.textContent = student.provinsi_asal;
                            provSelect.appendChild(opt);
                            provSelect.value = student.provinsi_asal;
                            if (provHidden) provHidden.value = student.provinsi_asal;
                        }

                        // Trigger change to load cities
                        provSelect.dispatchEvent(new Event('change'));

                        // 5. CITY POPULATION (Wait for city options)
                        if (student.kabupaten_kota_asal) {
                            const kabSelect = document.getElementById(`kabkota_asal_select_${idx}`);
                            const kabHidden = document.getElementById(`kabkota_asal_${idx}`);

                            if (kabSelect) {
                                // Poll for city options to load
                                await new Promise(resolve => {
                                    let attempts = 0;
                                    const checkCityInterval = setInterval(() => {
                                        attempts++;
                                        if (attempts > 50) { // 5 seconds
                                            clearInterval(checkCityInterval);
                                            resolve(); // Stop waiting
                                            return;
                                        }

                                        if (kabSelect.options.length > 1 && !kabSelect.disabled) {
                                            clearInterval(checkCityInterval);
                                            resolve(); // Loaded!
                                        }
                                    }, 100);
                                });

                                // Try to Select or Force Add
                                let kabMatch = Array.from(kabSelect.options).find(o => o.value === student.kabupaten_kota_asal);
                                if (!kabMatch) {
                                    kabMatch = Array.from(kabSelect.options).find(o => o.text.trim().toLowerCase() === student.kabupaten_kota_asal.trim().toLowerCase());
                                }

                                if (kabMatch) {
                                    kabSelect.value = kabMatch.value;
                                    if (kabHidden) kabHidden.value = kabMatch.value;
                                } else {
                                    const opt = document.createElement('option');
                                    opt.value = student.kabupaten_kota_asal;
                                    opt.textContent = student.kabupaten_kota_asal;
                                    kabSelect.appendChild(opt);
                                    kabSelect.value = student.kabupaten_kota_asal;
                                    if (kabHidden) kabHidden.value = student.kabupaten_kota_asal;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Initialize Form
        if (jumlahInput) {
            // Use draft.jumlah_siswa (user's intended slots) NOT students.length (actual saved count)
            let studentCount;
            if (CONFIG.draft && CONFIG.draft.jumlah_siswa) {
                studentCount = parseInt(CONFIG.draft.jumlah_siswa);
                console.log(`Using draft jumlah_siswa: ${studentCount}`);
            } else {
                studentCount = OLD_JUMLAH;
            }

            jumlahInput.value = studentCount;
            renderSiswaForms(parseInt(studentCount) || 1);

            // Populate from draft students if available
            if (CONFIG.students && CONFIG.students.length > 0) {
                setTimeout(() => populateDraftStudents(), 500);
            }

            // NEW: Populate Header File Indicator
            if (CONFIG.draft && CONFIG.draft.file_surat_permohonan) {
                const headerFileInput = document.querySelector('input[name="file_surat_permohonan"]');
                if (headerFileInput) {
                    const filePath = CONFIG.draft.file_surat_permohonan;
                    if (filePath && typeof filePath === 'string' && filePath.length > 5) {
                        headerFileInput.setAttribute('data-has-file', 'true');
                        // For header, we might want to keep it required if user wants to change it? 
                        // Usually if saved, not required.
                        headerFileInput.removeAttribute('required');

                        const existingStatus = headerFileInput.parentNode.querySelector('.file-status-indicator');
                        if (!existingStatus) {
                            const statusDiv = document.createElement('div');
                            statusDiv.className = 'file-status-indicator mt-2 flex items-center gap-2 text-xs text-emerald-600 bg-emerald-50 p-2 rounded-lg border border-emerald-100';
                            statusDiv.innerHTML = `
                                <i class="bi bi-check-circle-fill"></i>
                                <span class="font-bold">File Tersimpan (Surat Permohonan)</span>
                                <span class="text-slate-500">(Upload baru jika ingin mengubah)</span>
                            `;
                            headerFileInput.parentNode.insertBefore(statusDiv, headerFileInput.nextSibling);
                        }

                        // HIDDEN INPUT FOR SUBMISSION
                        const existingHidden = document.querySelector(`input[name="file_surat_permohonan"][type="hidden"]`);
                        if (existingHidden) existingHidden.remove();

                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'file_surat_permohonan';
                        hiddenInput.value = filePath;
                        form.appendChild(hiddenInput);
                    }
                }
            }

            jumlahInput.addEventListener('change', function () {
                const newCount = Math.min(Math.max(parseInt(this.value) || 1, 1), 20);
                this.value = newCount;

                // If draft mode and reducing count, warn user
                if (draftId && newCount < totalSiswa) {
                    if (!confirm(`Mengurangi jumlah siswa dari ${totalSiswa} ke ${newCount}. Data siswa #${newCount + 1} dst akan tersimpan di database tapi tidak ditampilkan. Lanjutkan?`)) {
                        this.value = totalSiswa;
                        return;
                    }
                }

                // Re-render forms but preserve existing students data for previously populated forms
                renderSiswaForms(newCount);

                // Re-populate from draft students if available
                if (CONFIG.students && CONFIG.students.length > 0) {
                    setTimeout(() => populateDraftStudents(), 300);
                }

                // Sync Header if Draft exists
                if (draftId) {
                    updateDraftHeader(draftId, { jumlah_siswa: newCount });
                }
            });
        }

        if (prevBtn) prevBtn.addEventListener('click', () => { if (currentSiswa > 0) showSiswa(currentSiswa - 1); });
        if (nextBtn) nextBtn.addEventListener('click', () => { if (currentSiswa < totalSiswa - 1) showSiswa(currentSiswa + 1); });

        // Prevent double submit
        if (form) {
            form.addEventListener('submit', function (e) {
                // If draft mode and more than 1 student, use AJAX finalize
                if (draftId && totalSiswa > 1) {
                    e.preventDefault();
                    finalizeDraftSubmit();
                    return;
                }

                const btn = document.getElementById('btnSubmit');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-2"></i> Memproses...';
                }
            });
        }

    });

    // ======================================================================
    // AUTO-SAVE DRAFT FUNCTIONS
    // ======================================================================

    /**
     * Create a draft if jumlah_siswa > 1 and header fields are filled
     */
    async function createDraftIfNeeded() {
        if (draftId) return draftId; // Already created

        const jumlah = parseInt(document.getElementById('jumlah_siswa')?.value) || 1;
        if (jumlah <= 1) return null; // No draft needed for single student

        const npsn = document.querySelector('[name="npsn_sekolah_tujuan"]')?.value;
        const nama = document.querySelector('[name="nama_sekolah_tujuan"]')?.value;
        const alamat = document.querySelector('[name="alamat_sekolah_tujuan"]')?.value;

        if (!npsn || !nama || !alamat) {
            console.log('Header fields incomplete, skipping draft creation');
            return null;
        }

        isSaving = true;
        showSaveIndicator('Membuat draft...');

        try {
            const formData = new FormData();
            formData.append('jumlah_siswa', jumlah);
            formData.append('npsn_sekolah_tujuan', npsn);
            formData.append('nama_sekolah_tujuan', nama);
            formData.append('alamat_sekolah_tujuan', alamat);

            const nomor = document.querySelector('[name="nomor_surat"]')?.value;
            const tanggal = document.querySelector('[name="tanggal_surat"]')?.value;
            if (nomor) formData.append('nomor_surat', nomor);
            if (tanggal) formData.append('tanggal_surat', tanggal);

            // Upload surat permohonan if exists
            const fileInput = document.querySelector('[name="file_surat_permohonan"]');
            if (fileInput?.files[0]) {
                formData.append('file_surat_permohonan', fileInput.files[0]);
            }

            const response = await fetch(API.createDraft, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: formData
            });

            if (!response.ok) {
                throw new Error(await readErrorMessage(response) || 'Gagal membuat draft');
            }

            const result = await response.json();

            if (result.success && result.id_mutasi) {
                draftId = result.id_mutasi;
                // Store in hidden input for form
                let hiddenInput = document.getElementById('draft_id');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.id = 'draft_id';
                    hiddenInput.name = 'draft_id';
                    form.appendChild(hiddenInput);
                }
                hiddenInput.value = draftId;
                showSaveIndicator('Draft dibuat: ' + draftId, 'success');
                console.log('Draft created:', draftId);
                return draftId;
            } else {
                throw new Error(result.message || 'Failed to create draft');
            }
        } catch (e) {
            console.error('Create draft error:', e);
            showSaveIndicator('Gagal membuat draft', 'error');
            return null;
        } finally {
            isSaving = false;
        }
    }

    /**
     * Update Draft Header Only
     */
    async function updateDraftHeader(id, data) {
        try {
            const response = await fetch(`/mutasi-masuk/draft/${id}/update-header`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            if (!response.ok) {
                console.warn('Failed to update draft header:', await readErrorMessage(response));
                return;
            }
            const result = await response.json();
            if (!result.success) {
                console.warn('Failed to update draft header:', result.message);
            }
        } catch (e) {
            console.error('Update header error:', e);
        }
    }

    /**
     * Save current student data to draft via AJAX
     */
    async function saveCurrentStudent(index) {
        if (!draftId) {
            draftId = await createDraftIfNeeded();
            if (!draftId) return false;
        }

        const card = document.querySelector(`.siswa-card[data-index="${index}"]`);
        if (!card) return false;

        // Check if required fields are filled
        const namaInput = card.querySelector('[name*="[nama_murid]"]');
        if (!namaInput?.value?.trim()) {
            console.log(`Student ${index} has no name, skipping save`);
            return false;
        }

        isSaving = true;
        showSaveIndicator('Menyimpan Siswa #' + (index + 1) + '...');

        try {
            const formData = new FormData();
            formData.append('student_index', index);

            // Collect all fields from this student card
            let totalPayloadSize = 0; // Estimate
            const inputs = card.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                const name = input.name;
                if (!name) return;

                // Extract field name from siswa[0][field_name]
                const match = name.match(/siswa\[\d+\]\[(.+)\]/);
                if (match) {
                    const fieldName = match[1];
                    if (input.type === 'file') {
                        if (input.files[0]) {
                            formData.append(fieldName, input.files[0]);
                            totalPayloadSize += input.files[0].size;
                        }
                    } else if (input.type === 'checkbox') {
                        formData.append(fieldName, input.checked ? '1' : '0');
                    } else {
                        formData.append(fieldName, input.value);
                    }
                }
            });

            // ------------------------------------------------------------------
            // SMART UPLOAD STRATEGY (Prevents 413 Payload Too Large)
            // ------------------------------------------------------------------
            const SERVER_LIMIT = 4.0 * 1024 * 1024; // 4.0MB Absolute Max per Request
            const SEQUENTIAL_THRESHOLD = 3.0 * 1024 * 1024; // 3.0MB Threshold for splitting

            // 1. Check for File Size Limits (Business Rules)
            let oversizedFile = null;
            let limitMsg = '';

            inputs.forEach(input => {
                if (input.type === 'file' && input.files[0]) {
                    const fileSize = input.files[0].size;
                    const fieldName = input.name;

                    // Rapor: Max 2MB
                    if (fieldName.includes('file_rapor')) {
                        if (fileSize > 2 * 1024 * 1024) {
                            oversizedFile = input.files[0].name;
                            limitMsg = 'Maksimal 2 MB untuk file Rapor.';
                        }
                    }
                    // Others: Max 1MB
                    else {
                        if (fileSize > 1 * 1024 * 1024) {
                            oversizedFile = input.files[0].name;
                            limitMsg = 'Maksimal 1 MB untuk dokumen ini.';
                        }
                    }

                    // Absolute Server Safe Limit (4MB)
                    if (fileSize > 4 * 1024 * 1024) {
                        oversizedFile = input.files[0].name;
                        limitMsg = 'File terlalu besar (Server Limit 4MB).';
                    }
                }
            });

            if (oversizedFile) {
                const sizeMB = (SERVER_LIMIT / (1024 * 1024)).toFixed(1);
                alert(`⚠️ Gagal Menyimpan: File "${oversizedFile}" melebihi batas.\n\n${limitMsg}`);
                showSaveIndicator('Gagal: File terlalu besar', 'error');
                isSaving = false;
                return false;
            }

            // 2. Decide Strategy
            if (totalPayloadSize > SEQUENTIAL_THRESHOLD) {
                console.info(`Total payload ${totalPayloadSize} bytes > ${SEQUENTIAL_THRESHOLD}. Switching to sequential upload.`);

                // 1. Upload Text Data + First File (to create/update record)
                // 2. Upload remaining files one by one

                let filesToSend = [];
                inputs.forEach(input => {
                    if (input.type === 'file' && input.files[0]) {
                        const match = input.name.match(/siswa\[\d+\]\[(.+)\]/);
                        if (match) filesToSend.push({ field: match[1], file: input.files[0] });
                    }
                });

                // Helper to build base FormData (Text only)
                const buildBaseFormData = () => {
                    const fd = new FormData();
                    fd.append('student_index', index);
                    inputs.forEach(input => {
                        const name = input.name;
                        if (!name || input.type === 'file') return; // Skip files
                        const match = name.match(/siswa\[\d+\]\[(.+)\]/);
                        if (match) {
                            const fieldName = match[1];
                            if (input.type === 'checkbox') fd.append(fieldName, input.checked ? '1' : '0');
                            else fd.append(fieldName, input.value);
                        }
                    });
                    return fd;
                };

                // Execute Sequential Uploads
                let successCount = 0;
                for (let i = 0; i < filesToSend.length; i++) {
                    const fileItem = filesToSend[i];
                    showSaveIndicator(`Mengupload file ${i + 1}/${filesToSend.length} (${(fileItem.file.size / 1024 / 1024).toFixed(1)} MB)...`);

                    const fd = buildBaseFormData();
                    fd.append(fileItem.field, fileItem.file);

                    const response = await fetch(API.addStudent(draftId), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                        body: fd
                    });

                    if (!response.ok) {
                        throw new Error(await readErrorMessage(response) || `Gagal upload file ${fileItem.field}`);
                    }

                    const result = await response.json();
                    if (!result.success) throw new Error(result.message || `Gagal upload file ${fileItem.field}`);
                    successCount++;
                }

                savedStudents.add(index);
                markTabAsSaved(index);
                showSaveIndicator('Semua data berhasil disimpan ✓', 'success');
                return true;

            } else {
                // STANDARD UPLOAD (Small Payload)
                const response = await fetch(API.addStudent(draftId), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(await readErrorMessage(response) || 'Gagal menyimpan siswa');
                }

                const result = await response.json();
                if (result.success) {
                    savedStudents.add(index);
                    markTabAsSaved(index);
                    showSaveIndicator('Siswa #' + (index + 1) + ' tersimpan ✓', 'success');
                    return true;
                } else {
                    throw new Error(result.message || 'Failed to save student');
                }
            }
        } catch (e) {
            console.error('Save student error:', e);
            const errorMsg = e.message === 'Failed to save student' ? 'Gagal menyimpan siswa' : e.message;
            showSaveIndicator(errorMsg, 'error');
            alert(`Gagal menyimpan data siswa #${index + 1}:\n${errorMsg}`);
            if (typeof window.resetMutasiMasukSubmitting === 'function') {
                window.resetMutasiMasukSubmitting();
            }
            return false;
        } finally {
            isSaving = false;
        }
    }

    /**
     * Finalize draft and submit for verification
     */
    async function finalizeDraftSubmit() {
        if (!draftId) {
            // Fallback to normal form submit
            form.requestSubmit ? form.requestSubmit() : form.submit();
            return;
        }

        // First, save current student
        const saved = await saveCurrentStudent(currentSiswa);
        if (!saved) {
            const modal = document.getElementById('loadingModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            alert('Pengajuan belum dikirim karena data siswa terakhir gagal disimpan. Mohon perbaiki pesan error yang tampil, lalu klik Kirim Pengajuan lagi.');
            if (typeof window.resetMutasiMasukSubmitting === 'function') {
                window.resetMutasiMasukSubmitting();
            }
            return;
        }

        const modal = document.getElementById('loadingModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        try {
            const response = await fetch(API.finalize(draftId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            });

            if (!response.ok) {
                throw new Error(await readErrorMessage(response) || 'Gagal mengirim pengajuan');
            }

            const result = await response.json();

            if (result.success) {
                if (result.redirect) {
                    window.location.href = result.redirect;
                } else {
                    window.location.href = CONFIG.routes?.index || '/mutasi-masuk';
                }
            } else {
                throw new Error(result.message || 'Gagal mengirim');
            }
        } catch (e) {
            console.error('Finalize error:', e);
            alert('Gagal mengirim pengajuan: ' + e.message);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
            if (typeof window.resetMutasiMasukSubmitting === 'function') {
                window.resetMutasiMasukSubmitting();
            }
        }
    }

    /**
     * UI: Show save indicator
     */
    function showSaveIndicator(message, type = 'info') {
        let indicator = document.getElementById('autoSaveIndicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'autoSaveIndicator';
            indicator.className = 'fixed bottom-4 right-4 px-4 py-2 rounded-lg shadow-lg text-sm font-medium z-50 transition-all duration-300';
            document.body.appendChild(indicator);
        }

        indicator.className = 'fixed bottom-4 right-4 px-4 py-2 rounded-lg shadow-lg text-sm font-medium z-50 transition-all duration-300';
        if (type === 'success') {
            indicator.classList.add('bg-green-100', 'text-green-800', 'border', 'border-green-200');
        } else if (type === 'error') {
            indicator.classList.add('bg-red-100', 'text-red-800', 'border', 'border-red-200');
        } else {
            indicator.classList.add('bg-blue-100', 'text-blue-800', 'border', 'border-blue-200');
        }

        indicator.textContent = message;
        indicator.style.opacity = '1';

        clearTimeout(indicator._hideTimeout);
        indicator._hideTimeout = setTimeout(() => {
            indicator.style.opacity = '0';
        }, 3000);
    }

    /**
     * UI: Mark tab as saved
     */
    function markTabAsSaved(index) {
        const tabs = document.querySelectorAll('.siswa-tab');
        if (tabs[index]) {
            if (!tabs[index].querySelector('.save-check')) {
                const check = document.createElement('span');
                check.className = 'save-check absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full border border-white';
                tabs[index].style.position = 'relative';
                tabs[index].appendChild(check);
            }
        }
    }

    // Expose for external calls
    window.createDraftIfNeeded = createDraftIfNeeded;
    window.saveCurrentStudent = saveCurrentStudent;

    /**
     * Save all current data as Draft and redirect to index
     * Called by "Simpan Draft" button
     */
    window.saveDraftAndRedirect = async function () {
        const btn = document.getElementById('btnSaveDraft');
        if (btn) {
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split animate-spin mr-2"></i> Menyimpan...';
            btn.disabled = true;
        }

        try {
            // Create draft if not exists
            await createDraftIfNeeded();

            if (!draftId) {
                alert('Gagal membuat draft. Pastikan data header sudah diisi (Jumlah Siswa, NPSN, Nama Sekolah, Alamat).');
                if (btn) {
                    btn.innerHTML = '<i class="bi bi-save mr-2"></i><span>Simpan Draft</span>';
                    btn.disabled = false;
                }
                return;
            }

            // Save ALL students to ensure nothing is missed (Fix for Missing 5th Student)
            if (totalSiswa > 0) {
                for (let i = 0; i < totalSiswa; i++) {
                    await saveCurrentStudent(i);
                }
            }

            showSaveIndicator('📝 Draft tersimpan! Mengalihkan...', 'success');

            // Redirect to index with success message
            setTimeout(() => {
                window.location.href = CONFIG.routes?.index || '/mutasi-masuk';
            }, 1000);
        } catch (error) {
            console.error('Draft save failed:', error);
            showSaveIndicator('❌ Gagal menyimpan draft', 'error');
            if (btn) {
                btn.innerHTML = '<i class="bi bi-save mr-2"></i><span>Simpan Draft</span>';
                btn.disabled = false;
            }
        }
    };

})();



