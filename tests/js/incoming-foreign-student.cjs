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
