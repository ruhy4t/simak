const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function element(saved = false) {
    return {
        required: true, checked: false, placeholder: '', textContent: '',
        previousElementSibling: {}, classList: { add() {}, remove() {} },
        getAttribute: name => name === 'data-has-file' && saved ? 'true' : null,
        removeAttribute(name) { if (name === 'required') this.required = false; },
        setAttribute(name) { if (name === 'required') this.required = true; },
    };
}

for (const dinas of [false, true]) {
    test(`foreign origin changes required fields and restores domestic rules (Dinas=${dinas})`, () => {
        const byId = new Map();
        const byName = new Map();
        for (const field of ['nisn', 'has_surat_pindah', 'has_surat_dapodik',
            'has_surat_penyaluran', 'file_surat_pindah', 'file_surat_dapodik']) {
            byName.set(`siswa[0][${field}]`, element());
        }
        for (const id of ['luar_negeri_0', 'nisn_prefill_status_0', 'penyaluran_container_0',
            'npsn_asal_0', 'npsn_status_0', 'file_rekomendasi_0', 'file_rekam_didik_0']) {
            byId.set(id, element());
        }
        // A previously uploaded letter must not require another upload on reopening.
        byId.set('file_surat_penyaluran_0', element(true));
        const window = { MUTASI_CONFIG: { isDinasEntry: dinas } };
        const document = {
            getElementById: id => byId.get(id) || null,
            querySelector: selector => byName.get(selector.match(/name="([^"]+)"/)?.[1]) || null,
            addEventListener() {},
        };
        vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/mutasi-masuk.js'), 'utf8'), {
            window, document, console, setTimeout, clearTimeout,
        });
        byId.get('luar_negeri_0').checked = true;
        window.toggleLuarNegeri(0);
        for (const field of ['nisn', 'has_surat_pindah', 'has_surat_dapodik',
            'file_surat_pindah', 'file_surat_dapodik']) {
            assert.equal(byName.get(`siswa[0][${field}]`).required, false, field);
        }
        assert.equal(byName.get('siswa[0][has_surat_penyaluran]').required, true);
        assert.equal(byId.get('file_surat_penyaluran_0').required, false);
        byId.get('luar_negeri_0').checked = false;
        window.toggleLuarNegeri(0);
        assert.equal(byName.get('siswa[0][nisn]').required, true);
        assert.equal(byName.get('siswa[0][has_surat_pindah]').required, true);
        assert.equal(byName.get('siswa[0][has_surat_dapodik]').required, true);
        assert.equal(byName.get('siswa[0][has_surat_penyaluran]').required, false);
    });
}

for (const dinas of [false, true]) {
    test(`draft navigation accepts foreign student with empty documents and repairs region (Dinas=${dinas})`, () => {
        const fields = new Map();
        for (const field of ['nama_murid', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
            'kelas', 'tanggal_diterima', 'nama_sekolah_asal', 'alamat_sekolah_asal']) {
            fields.set(field, { value: 'Terisi' });
        }
        for (const field of ['nisn', 'provinsi_asal', 'kabupaten_kota_asal']) fields.set(field, { value: '' });
        fields.set('asal_luar_negeri', { checked: true });
        for (const field of ['file_surat_penyaluran', 'file_ijazah_sd', 'file_rapor',
            'has_surat_penyaluran', 'has_ijazah_sd', 'has_rapor']) {
            fields.set(field, { value: '', checked: false, files: [], getAttribute() { return null; } });
        }
        const card = { querySelector: selector => fields.get(selector.match(/\[([a-z_]+)\]/)?.[1]) || null };
        const document = {
            querySelector: selector => selector.startsWith('.siswa-card') ? card : null,
            getElementById: id => ({
                luar_negeri_0: fields.get('asal_luar_negeri'),
                provinsi_asal_0: fields.get('provinsi_asal'),
                kabkota_asal_0: fields.get('kabupaten_kota_asal'),
            })[id] || null,
            addEventListener() {},
        };
        const window = { MUTASI_CONFIG: { isDinasEntry: dinas } };
        const source = fs.readFileSync(path.join(__dirname, '../../public/js/mutasi-masuk.js'), 'utf8');
        const context = vm.createContext({ window, document, console, setTimeout, clearTimeout, isDinasEntry: dinas });
        vm.runInContext(source, context);
        const start = source.indexOf('        function validateCurrentStudent(');
        const end = source.indexOf('        function showValidationError(', start);
        vm.runInContext(source.slice(start, end), context);
        const draft = vm.runInContext('validateCurrentStudent(0, true)', context);
        assert.equal(draft.valid, true);
        assert.equal(fields.get('provinsi_asal').value, 'Luar Negeri');
        assert.equal(fields.get('kabupaten_kota_asal').value, 'Luar Negeri');
        const submission = vm.runInContext('validateCurrentStudent(0)', context);
        assert.equal(submission.valid, false);
        assert.ok(submission.errors.includes(dinas ? 'Konfirmasi Surat Penyaluran' : 'Surat Penyaluran'));
    });
}

const regionSource = fs.readFileSync(path.join(__dirname, '../../public/js/mutasi-masuk.js'), 'utf8');
function regionLoader(fetch) {
    const start = regionSource.indexOf('    const regionRequests = new Map();');
    const end = regionSource.indexOf('    function loadProvinces(', start);
    const context = vm.createContext({ fetch });
    vm.runInContext(regionSource.slice(start, end), context);
    return url => context.fetchRegionData(url);
}

test('20 student cards share one reference request including subsequent reads', async () => {
    let calls = 0;
    const load = regionLoader(async () => {
        calls++;
        return { ok: true, json: async () => ({ success: true, data: [{ id: '32', name: 'Jawa Barat' }] }) };
    });
    const results = await Promise.all(Array.from({ length: 20 }, () => load('/provinces')));
    await load('/provinces');
    assert.equal(calls, 1);
    assert.equal(results.length, 20);
    assert.equal(results[19].data[0].name, 'Jawa Barat');
    await load('/regencies/32');
    assert.equal(calls, 2);
});

test('failed region requests can be retried', async () => {
    let calls = 0;
    const load = regionLoader(async () => {
        calls++;
        return { ok: calls > 1, json: async () => ({ success: true, data: [] }) };
    });
    await assert.rejects(load('/provinces'));
    await load('/provinces');
    assert.equal(calls, 2);
});
