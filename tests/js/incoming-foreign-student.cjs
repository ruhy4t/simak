const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

async function submitAfterValidationFailure(failure) {
    const source = fs.readFileSync(path.join(__dirname, '../../public/js/mutasi-masuk.js'), 'utf8');
    const start = source.indexOf("form.addEventListener('submit', async function (e) {");
    const close = source.indexOf('\n            });', start) + '\n            });'.length;
    assert.ok(start >= 0 && close > start, 'submit listener should be found');

    let submitHandler;
    let resetCalls = 0;
    let formSubmitCalls = 0;
    const form = {
        addEventListener: (event, handler) => { submitHandler = handler; },
        checkValidity: () => failure !== 'native-validation',
        reportValidity() {},
        submit: () => { formSubmitCalls++; },
    };
    const context = {
        form,
        totalSiswa: 1,
        validateCurrentStudent: () => failure === 'student-validation'
            ? { valid: false, errors: ['NISN'] }
            : { valid: true, errors: [] },
        showValidationError() {},
        window: { resetMutasiMasukSubmitting: () => { resetCalls++; } },
        document: {
            querySelectorAll: selector => selector.includes('arrow-repeat') && failure === 'pending-upload'
                ? [{}]
                : selector.includes('x-circle-fill') && failure === 'failed-upload' ? [{}] : [],
            getElementById: () => null,
        },
        alert() {},
        isSaving: false,
        draftId: null,
    };
    vm.runInNewContext(source.slice(start, close), context);
    await submitHandler({ preventDefault() {} });
    return { resetCalls, formSubmitCalls };
}

for (const failure of ['student-validation', 'native-validation', 'pending-upload', 'failed-upload']) {
    test(`failed ${failure} closes the incoming submit modal`, async () => {
        const result = await submitAfterValidationFailure(failure);
        assert.equal(result.resetCalls, 1);
        assert.equal(result.formSubmitCalls, 0);
    });
}

async function saveDraftWithUnnamedStudent(extraInput) {
    const calls = [];
    const alerts = [];
    const button = { innerHTML: '', disabled: false };
    const indicator = { classList: { add() {} }, style: {} };
    const name = { type: 'text', value: '' };
    const inputs = [name, { type: 'hidden', value: '0' }, ...(extraInput ? [extraInput] : [])];
    const card = {
        querySelector: () => name,
        querySelectorAll: () => inputs,
    };
    const document = {
        getElementById: id => ({ btnSaveDraft: button, jumlah_siswa: { value: '1' }, autoSaveIndicator: indicator })[id] || null,
        querySelector: selector => selector.startsWith('.siswa-card') ? card : null,
        addEventListener() {},
    };
    const window = { MUTASI_CONFIG: { draftId: 'MM-TEST-1' } };
    const fetch = async url => {
        calls.push(url);
        return { ok: true, json: async () => ({ success: true }) };
    };
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/mutasi-masuk.js'), 'utf8'), {
        window, document, fetch, alert: message => alerts.push(message),
        console: { log() {}, error() {}, warn() {} }, setTimeout: () => 1, clearTimeout() {},
    });
    await window.saveDraftAndRedirect();
    return { calls, alerts, button, indicator };
}

test('empty student slot permits saving the incoming draft header', async () => {
    const result = await saveDraftWithUnnamedStudent();
    assert.equal(result.calls.length, 1);
    assert.match(result.indicator.textContent, /Draft tersimpan/);
    assert.deepEqual(result.alerts, []);
});

for (const input of [
    { type: 'text', value: '0012345678' },
    { type: 'checkbox', checked: true },
    { type: 'file', files: [{}] },
]) {
    test(`draft does not discard an unnamed student's ${input.type} data`, async () => {
        const result = await saveDraftWithUnnamedStudent(input);
        assert.equal(result.calls.length, 0);
        assert.match(result.alerts[0], /Isi nama murid #1/);
        assert.equal(result.button.disabled, false);
    });
}

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

function regionForm(fetch) {
    const nodes = new Map();
    const select = () => ({
        options: [], value: '', disabled: false, listeners: {},
        get selectedIndex() { return this.options.findIndex(option => option.value === this.value); },
        set innerHTML(value) { this.options = [{ value: '', dataset: {} }]; this.value = ''; },
        appendChild(option) { this.options.push(option); },
        addEventListener(event, callback) { this.listeners[event] = callback; },
        async change(value) { this.value = value; await this.listeners.change.call(this); },
    });
    for (let i = 0; i < 3; i++) {
        for (const prefix of ['provinsi', 'kabkota']) {
            nodes.set(`${prefix}_asal_select_${i}`, select());
            nodes.set(`${prefix}_asal_${i}`, { value: '' });
        }
        nodes.set(`luar_negeri_${i}`, { checked: false });
    }
    const document = {
        getElementById: id => nodes.get(id) || null,
        querySelector: () => null, addEventListener() {},
        createElement: () => ({ value: '', dataset: {} }),
    };
    const CONFIG = { routes: { apiProvinces: '/provinces', apiRegencies: '/regencies/:provinceId' } };
    const window = { MUTASI_CONFIG: CONFIG };
    const context = vm.createContext({ window, document, fetch, CONFIG, console, setTimeout, clearTimeout });
    vm.runInContext(regionSource, context);
    vm.runInContext(regionSource.slice(regionSource.indexOf('    const regionRequests = new Map();'),
        regionSource.indexOf('    function resetSekolahAsal(')), context);
    for (let i = 0; i < 3; i++) context.setupSiswaEvents(i);
    return { nodes, window };
}

const regionResponse = data => ({ ok: true, json: async () => ({ success: true, data }) });
const provinceData = [{ id: '32', name: 'Jawa Barat' }, { id: '33', name: 'Jawa Tengah' }];
const settle = () => new Promise(resolve => setImmediate(resolve));

test('students independently select different provinces and cities while sharing reference lists', async () => {
    const calls = [];
    const { nodes, window } = regionForm(async url => {
        calls.push(url);
        return regionResponse(url === '/provinces' ? provinceData :
            url.endsWith('/32') ? [{ name: 'Kota Bogor' }, { name: 'Kota Bandung' }] : [{ name: 'Kota Semarang' }]);
    });
    await settle();
    await Promise.all([
        nodes.get('provinsi_asal_select_0').change('Jawa Barat'),
        nodes.get('provinsi_asal_select_1').change('Jawa Tengah'),
        nodes.get('provinsi_asal_select_2').change('Jawa Barat'),
    ]);
    await nodes.get('kabkota_asal_select_0').change('Kota Bogor');
    await nodes.get('kabkota_asal_select_1').change('Kota Semarang');
    await nodes.get('kabkota_asal_select_2').change('Kota Bandung');
    assert.equal(nodes.get('kabkota_asal_0').value, 'Kota Bogor');
    assert.equal(nodes.get('kabkota_asal_1').value, 'Kota Semarang');
    assert.equal(nodes.get('kabkota_asal_2').value, 'Kota Bandung');
    assert.equal(nodes.get('provinsi_asal_1').value, 'Jawa Tengah');
    assert.deepEqual(calls.sort(), ['/provinces', '/regencies/32', '/regencies/33']);
    nodes.get('luar_negeri_0').checked = true;
    window.syncForeignOrigin(0);
    assert.equal(nodes.get('kabkota_asal_0').value, 'Luar Negeri');
    assert.equal(nodes.get('kabkota_asal_1').value, 'Kota Semarang');
    assert.equal(nodes.get('kabkota_asal_2').value, 'Kota Bandung');
});

test('rapid province changes do not apply outdated responses or duplicate cities', async () => {
    const pending = new Map();
    const { nodes } = regionForm(url => url === '/provinces' ? Promise.resolve(regionResponse(provinceData)) :
        new Promise(resolve => pending.set(url, resolve)));
    await settle();
    const province = nodes.get('provinsi_asal_select_0');
    const first = province.change('Jawa Barat');
    const second = province.change('Jawa Tengah');
    const third = province.change('Jawa Barat');
    pending.get('/regencies/32')(regionResponse([{ name: 'Kota Bogor' }]));
    await Promise.all([first, third]);
    pending.get('/regencies/33')(regionResponse([{ name: 'Kota Semarang' }]));
    await second;
    const cities = nodes.get('kabkota_asal_select_0').options.map(option => option.value);
    assert.deepEqual(cities, ['', 'Kota Bogor']);
    assert.equal(nodes.get('provinsi_asal_0').value, 'Jawa Barat');
});
