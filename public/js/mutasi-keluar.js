/**
 * Mutasi Keluar Form Script
 * Extracted from create.blade.php
 */

// Config will be read inside listeners to ensure it's loaded

document.addEventListener('DOMContentLoaded', function () {
    const CONFIG = window.MUTASI_CONFIG || {};
    const npsnInput = document.getElementById('npsn_sekolah_tujuan');
    const namaSekolahInput = document.getElementById('nama_sekolah_tujuan');
    const provinsiSelect = document.getElementById('provinsi_tujuan_select');
    const provinsiHidden = document.getElementById('provinsi_tujuan');
    const kabkotaSelect = document.getElementById('kabupaten_kota_tujuan_select');
    const kabkotaHidden = document.getElementById('kabupaten_kota_tujuan');
    const alamatInput = document.getElementById('alamat_sekolah_tujuan');
    const npsnStatus = document.getElementById('npsn_status');
    const npsnLoading = document.getElementById('npsn_loading');

    let debounceTimer;

    // Load provinces
    async function loadProvinces() {
        if (!provinsiSelect) return;
        provinsiSelect.innerHTML = '<option value="">Pilih Provinsi</option>';

        try {
            const response = await fetch(CONFIG.routes.apiProvinces);
            const result = await response.json();

            if (result.success && result.data) {
                result.data.forEach(prov => {
                    const option = document.createElement('option');
                    option.value = prov.name;
                    option.dataset.id = prov.id;
                    option.textContent = prov.name;
                    provinsiSelect.appendChild(option);
                });
            }

            // Re-trigger L.N. check if exists
            const checkbox = document.getElementById('tujuan_luar_negeri');
            if (checkbox && checkbox.checked) {
                toggleTujuanLuarNegeri();
            }

        } catch (e) {
            console.error('Failed to load provinces', e);
        }
    }

    // Load kabupaten/kota when province changes
    if (provinsiSelect) {
        provinsiSelect.addEventListener('change', async function () {
            const selectedOption = this.options[this.selectedIndex];
            const selectedProv = this.value;
            const provId = selectedOption ? selectedOption.dataset.id : null;

            provinsiHidden.value = selectedProv; // Update hidden input
            kabkotaSelect.innerHTML = '<option value="">Pilih Kabupaten/Kota</option>';
            kabkotaHidden.value = '';

            // Handle Luar Negeri
            if (selectedProv === 'Luar Negeri') {
                const option = document.createElement('option');
                option.value = 'Luar Negeri';
                option.textContent = 'Luar Negeri';
                kabkotaSelect.appendChild(option);
                kabkotaSelect.value = 'Luar Negeri';
                kabkotaHidden.value = 'Luar Negeri';
                return;
            }

            if (provId) {
                kabkotaSelect.disabled = true;
                kabkotaSelect.innerHTML = '<option value="">Memuat...</option>';

                try {
                    const response = await fetch(CONFIG.routes.apiRegencies.replace(':provinceId', provId));
                    const result = await response.json();

                    kabkotaSelect.innerHTML = '<option value="">Pilih Kabupaten/Kota</option>';

                    if (result.success && result.data) {
                        result.data.forEach(kab => {
                            const option = document.createElement('option');
                            option.value = kab.name;
                            option.textContent = kab.name;
                            kabkotaSelect.appendChild(option);
                        });
                        kabkotaSelect.disabled = false;
                    }
                } catch (e) {
                    console.error('Failed to load regencies', e);
                    kabkotaSelect.innerHTML = '<option value="">Gagal memuat data</option>';
                    kabkotaSelect.disabled = false;
                }
            }
        });
    }

    // Update hidden when kabkota changes
    if (kabkotaSelect) {
        kabkotaSelect.addEventListener('change', function () {
            kabkotaHidden.value = this.value;
        });
    }

    // Initial Load
    loadProvinces();

    // Toggle Luar Negeri handler for sekolah tujuan
    window.toggleTujuanLuarNegeri = function () {
        const checkbox = document.getElementById('tujuan_luar_negeri');
        const npsnInput = document.getElementById('npsn_sekolah_tujuan');
        const npsnStatus = document.getElementById('npsn_status');
        const npsnReqLabel = document.getElementById('npsn_required_label');
        const hiddenFlag = document.getElementById('tujuan_luar_negeri_hidden');

        if (checkbox.checked) {
            // NPSN becomes optional for foreign schools
            npsnInput.removeAttribute('required');
            npsnInput.placeholder = 'Kosongkan jika tidak ada';
            if (npsnReqLabel) npsnReqLabel.style.display = 'none';
            if (npsnStatus) {
                npsnStatus.textContent = 'NPSN tidak wajib untuk sekolah luar negeri';
                npsnStatus.className = 'text-xs mt-1 text-blue-600';
            }
            if (hiddenFlag) hiddenFlag.value = '1';

            // Auto-set province to "Luar Negeri"
            if (provinsiSelect) {
                // Check if Luar Negeri option exists, if not add it
                let lnOption = [...provinsiSelect.options].find(o => o.value === 'Luar Negeri');
                if (!lnOption) {
                    lnOption = document.createElement('option');
                    lnOption.value = 'Luar Negeri';
                    lnOption.textContent = 'Luar Negeri';
                    provinsiSelect.appendChild(lnOption);
                }
                provinsiSelect.value = 'Luar Negeri';
                provinsiHidden.value = 'Luar Negeri';

                // Set KabKota to Luar Negeri manually
                kabkotaSelect.innerHTML = '<option value="Luar Negeri">Luar Negeri</option>';
                kabkotaSelect.value = 'Luar Negeri';
                kabkotaHidden.value = 'Luar Negeri';
                kabkotaSelect.disabled = false;
            }
        } else {
            // NPSN back to required
            npsnInput.setAttribute('required', 'required');
            npsnInput.placeholder = '8 digit NPSN atau P... untuk PKBM';
            if (npsnReqLabel) npsnReqLabel.style.display = '';
            if (npsnStatus) {
                npsnStatus.textContent = 'Contoh: 12345678 atau P1234567 (PKBM)';
                npsnStatus.className = 'text-xs mt-1 text-gray-500';
            }
            if (hiddenFlag) hiddenFlag.value = '0';

            // Reset province/kabkota
            loadProvinces();
            provinsiHidden.value = '';
            kabkotaHidden.value = '';
            kabkotaSelect.innerHTML = '<option value="">Pilih Kabupaten/Kota</option>';
        }
    };


    // Lookup NPSN from satuan_pendidikan
    if (npsnInput) {
        npsnInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const npsn = this.value.trim();

            if (npsn.length < 8) {
                npsnStatus.textContent = '';
                npsnStatus.className = 'text-xs mt-1';
                return;
            }

            debounceTimer = setTimeout(async () => {
                npsnLoading.classList.remove('hidden');
                npsnStatus.textContent = 'Mencari...';
                npsnStatus.className = 'text-xs mt-1 text-gray-500';

                try {
                    // Use CONFIG
                    const response = await fetch(CONFIG.routes.apiNpsn.replace(':npsn', npsn));
                    if (!response.ok) throw new Error('HTTP error');

                    const data = await response.json();

                    if (data.success && data.data) {
                        // === FOUND IN DATABASE ===
                        namaSekolahInput.value = data.data.nama;
                        alamatInput.value = data.data.alamat_lengkap || '';

                        // Set provinsi (only if data exists)
                        if (data.data.provinsi) {
                            provinsiSelect.innerHTML = `<option value="${data.data.provinsi}" selected>${data.data.provinsi}</option>`;
                            provinsiHidden.value = data.data.provinsi;
                            provinsiSelect.disabled = true;
                            provinsiSelect.classList.add('bg-gray-100');
                        } else {
                            provinsiSelect.disabled = false;
                            provinsiSelect.classList.remove('bg-gray-100');
                            loadProvinces(); // Reload if empty so user can pick
                        }

                        // Set kabkota (only if data exists)
                        if (data.data.kota) {
                            kabkotaSelect.innerHTML = `<option value="${data.data.kota}" selected>${data.data.kota}</option>`;
                            kabkotaHidden.value = data.data.kota;
                            kabkotaSelect.disabled = true;
                            kabkotaSelect.classList.add('bg-gray-100');
                        } else {
                            kabkotaSelect.disabled = false;
                            kabkotaSelect.classList.remove('bg-gray-100');
                            if (data.data.provinsi) {
                                // If prov exists but kota doesn't, we might need to load cities for that prov?
                                // Complicated because we need prov ID for API.
                                // Safer to just unlock both if incomplete.
                                provinsiSelect.disabled = false;
                                provinsiSelect.classList.remove('bg-gray-100');
                                loadProvinces();
                            }
                        }

                        const isKotaBogor = data.data.kota && data.data.kota.trim().toLowerCase() === 'kota bogor';
                        const locationType = isKotaBogor ? '(Lokal)' : '(Luar Kota)';

                        npsnStatus.textContent = `✓ Ditemukan: ${data.data.nama} ${locationType}`;
                        npsnStatus.className = 'text-xs mt-1 text-green-600';

                        // Make fields readonly (visual only)
                        namaSekolahInput.readOnly = true;
                        namaSekolahInput.classList.add('bg-gray-100');

                        if (data.data.alamat_lengkap) {
                            alamatInput.readOnly = true;
                            alamatInput.classList.add('bg-gray-100');
                        } else {
                            alamatInput.readOnly = false;
                            alamatInput.classList.remove('bg-gray-100');
                        }

                    } else {
                        // === NOT FOUND - ENABLE MANUAL INPUT ===
                        npsnStatus.innerHTML = '⚠ Tidak ditemukan. <a href="https://referensi.data.kemendikdasmen.go.id/pendidikan/dikdas" target="_blank" class="text-primary-600 hover:underline">Cari di Referensi Kemendikdasmen</a>';
                        npsnStatus.className = 'text-xs mt-1 text-yellow-600';

                        // Enable manual input
                        namaSekolahInput.readOnly = false;
                        namaSekolahInput.classList.remove('bg-gray-100');
                        namaSekolahInput.value = '';

                        // Enable alamat - wajib diisi manual
                        alamatInput.value = '';
                        alamatInput.readOnly = false;
                        alamatInput.classList.remove('bg-gray-100');

                        // Enable and load provinces
                        provinsiSelect.disabled = false;
                        provinsiSelect.classList.remove('bg-gray-100');
                        kabkotaSelect.innerHTML = '<option value="">Pilih Kabupaten/Kota</option>';
                        kabkotaSelect.disabled = false;
                        kabkotaSelect.classList.remove('bg-gray-100');

                        loadProvinces();
                    }
                } catch (error) {
                    console.error('NPSN lookup failed:', error);
                    npsnStatus.innerHTML = '⚠ Error. <a href="https://referensi.data.kemendikdasmen.go.id/pendidikan/dikdas" target="_blank" class="text-primary-600 hover:underline">Cari manual di Referensi Kemendikdasmen</a>';
                    npsnStatus.className = 'text-xs mt-1 text-yellow-600';

                    // Enable all fields for manual input
                    namaSekolahInput.readOnly = false;
                    namaSekolahInput.classList.remove('bg-gray-100');
                    alamatInput.readOnly = false;
                    alamatInput.classList.remove('bg-gray-100');
                    provinsiSelect.disabled = false;
                    kabkotaSelect.disabled = false;
                    loadProvinces();
                } finally {
                    npsnLoading.classList.add('hidden');
                }
            }, 500);
        });
    }

    // Initialize - check if there are old values to restore
    if (provinsiHidden && kabkotaHidden) {
        const oldProvinsi = provinsiHidden.value;
        const oldKabkota = kabkotaHidden.value;
        const npsn = npsnInput ? npsnInput.value.trim() : '';

        if (oldProvinsi && oldKabkota) {
            // Restore old values after validation error
            provinsiSelect.innerHTML = `<option value="${oldProvinsi}" selected>${oldProvinsi}</option>`;
            kabkotaSelect.innerHTML = `<option value="${oldKabkota}" selected>${oldKabkota}</option>`;
        } else if (npsn.length === 8 && npsnInput) {
            // Trigger NPSN lookup if already filled
            npsnInput.dispatchEvent(new Event('input'));
        } else if (provinsiSelect) {
            provinsiSelect.innerHTML = '<option value="">Masukkan NPSN dulu</option>';
            kabkotaSelect.innerHTML = '<option value="">Masukkan NPSN dulu</option>';
        }
    }

    // Real-time NISN validation
    const nisnInput = document.querySelector('input[name="nisn"]');
    if (nisnInput) {
        // Validate on blur
        nisnInput.addEventListener('blur', function () {
            validateNisnField(this);
        });

        // Also validate on input to clear errors when fixed
        nisnInput.addEventListener('input', function () {
            // Only validate if field has 10+ chars or has error
            if (this.value.length >= 10 || this.parentNode.querySelector('.nisn-error')) {
                validateNisnField(this);
            }
        });
    }

    function validateNisnField(input) {
        const value = input.value.replace(/\D/g, ''); // Get digits only
        const parentDiv = input.closest('div');
        const existingError = parentDiv.querySelector('.nisn-error');

        // Remove existing error first
        if (existingError) {
            existingError.remove();
            input.classList.remove('ring-2', 'ring-rose-300');
        }

        if (value.length > 0 && value.length !== 10) {
            input.classList.add('ring-2', 'ring-rose-300');
            const error = document.createElement('p');
            error.className = 'text-rose-500 text-xs mt-1 font-medium nisn-error';
            error.textContent = `NISN harus tepat 10 digit angka. Anda memasukkan ${value.length} digit.`;
            parentDiv.appendChild(error);
        } else if (value.length === 10) {
            input.classList.remove('ring-2', 'ring-rose-300');
            input.classList.add('ring-2', 'ring-emerald-300');
            // Remove green ring after a moment
            setTimeout(() => {
                input.classList.remove('ring-2', 'ring-emerald-300');
            }, 1500);
        }
    }
});

// Lookup NPSN untuk Sekolah Asal - DEFINED OUTSIDE DOMContentLoaded to be globally accessible
let lookupAsalTimeout;
window.lookupNpsnSekolahAsal = function (npsn) {
    const CONFIG = window.MUTASI_CONFIG || {};
    const statusEl = document.getElementById('npsn_asal_status');
    const namaEl = document.getElementById('nama_sekolah_asal');
    const alamatEl = document.getElementById('alamat_sekolah_asal');

    console.log('Lookup Asal Triggered:', npsn); // DEBUG LOG

    // Only proceed if elements exist (might be hidden/disabled for some roles)
    if (!namaEl || !alamatEl) return;

    if (npsn.length < 8) {
        statusEl.innerHTML = '';
        return;
    }

    clearTimeout(lookupAsalTimeout);
    statusEl.innerHTML = '<i class="bi bi-hourglass-split text-gray-400"></i>';

    lookupAsalTimeout = setTimeout(async () => {
        try {
            const response = await fetch(CONFIG.routes.apiSatpen.replace(':npsn', npsn));
            const data = await response.json();
            console.log('Lookup Asal Result:', data); // DEBUG LOG

            if (data.found) {
                // FOUND: Auto-fill and Lock
                namaEl.value = data.nama || '';
                namaEl.readOnly = true;
                namaEl.classList.add('bg-gray-100');

                if (data.alamat_lengkap) {
                    alamatEl.value = data.alamat_lengkap;
                    alamatEl.readOnly = true;
                    alamatEl.classList.add('bg-gray-100');
                } else {
                    // Allow manual address if missing
                    alamatEl.readOnly = false;
                    alamatEl.classList.remove('bg-gray-100');
                }

                statusEl.innerHTML = '<i class="bi bi-check-circle text-green-600"></i>';
            } else {
                // NOT FOUND: Unlock for manual input
                namaEl.readOnly = false;
                namaEl.classList.remove('bg-gray-100');
                namaEl.value = ''; // Reset or keep? Usually reset + manual

                alamatEl.readOnly = false;
                alamatEl.classList.remove('bg-gray-100');
                alamatEl.value = '';

                statusEl.innerHTML = '<span class="text-yellow-600 text-xs">Tidak ditemukan</span>';
            }
        } catch (error) {
            // ERROR: Fallback to manual
            namaEl.readOnly = false;
            namaEl.classList.remove('bg-gray-100');
            alamatEl.readOnly = false;
            alamatEl.classList.remove('bg-gray-100');

            statusEl.innerHTML = '<i class="bi bi-exclamation-circle text-red-500"></i>';
            console.error('NPSN lookup error:', error);
        }
    }, 500);
}

// Add validateFile function for security audit consistency
window.validateFile = function (input, maxMb) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileSize = file.size / 1024 / 1024; // in MB

        if (fileSize > maxMb) {
            alert(`Ukuran file terlalu besar! Maksimal ${maxMb}MB.`);
            input.value = ''; // clear input
            return false;
        }

        // Check extension - only PDF allowed
        const fileName = file.name;
        const idxDot = fileName.lastIndexOf(".") + 1;
        const extFile = fileName.substr(idxDot, fileName.length).toLowerCase();
        if (extFile !== "pdf") {
            alert("Hanya file PDF yang diperbolehkan!");
            input.value = '';
            return false;
        }
    }
    return true;
}