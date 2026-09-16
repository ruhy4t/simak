const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');

function setup() {
    const timers = new Map();
    const listeners = {};
    const children = [];
    const stored = new Map();
    let writes = 0;
    let nextId = 0;
    let failStorage = false;
    const form = {
        dataset: { autosave: 'test' }, isConnected: true,
        addEventListener: (name, fn) => { listeners[name] = fn; },
        appendChild: child => children.push(child),
    };
    const context = {
        window: {}, console: { warn() {} }, Date, File: class {},
        FormData: class { entries() { return [['name', 'Murid']]; } },
        localStorage: {
            getItem: key => stored.get(key) ?? null,
            setItem: (key, value) => { if (failStorage) throw Error('Full'); writes++; stored.set(key, value); },
            removeItem: key => stored.delete(key),
        },
        setTimeout: (fn, ms) => { timers.set(++nextId, { fn, ms }); return nextId; },
        clearTimeout: id => timers.delete(id),
        document: {
            readyState: 'complete', querySelectorAll: () => [form], addEventListener() {},
            createElement: () => ({ style: {}, setAttribute() {}, appendChild() {} }),
        },
    };
    vm.runInNewContext(readFileSync('resources/js/autosave.js', 'utf8'), context);
    const runTimer = ms => {
        const entry = [...timers].find(([, timer]) => timer.ms === ms);
        assert.ok(entry, `Timer ${ms} exists`);
        timers.delete(entry[0]); entry[1].fn();
    };
    return { form, listeners, children, timers, stored, runTimer, writes: () => writes, fail: value => { failStorage = value; } };
}

test('autosave skips unchanged forms and reuses its status indicator', () => {
    const env = setup();
    env.runTimer(30000);
    assert.equal(env.writes(), 0);
    for (let i = 0; i < 3; i++) {
        env.listeners.input(); env.runTimer(2000);
    }
    assert.equal(env.writes(), 3);
    assert.equal(env.children.length, 1);
    env.runTimer(30000);
    assert.equal(env.writes(), 3);
});

test('autosave retries failed storage and reports the actual result', () => {
    const env = setup();
    env.fail(true); env.listeners.input(); env.runTimer(2000);
    assert.equal(env.writes(), 0);
    assert.match(env.children[0].textContent, /belum tersimpan/);
    env.fail(false); env.runTimer(30000);
    assert.equal(env.writes(), 1);
    assert.match(env.children[0].innerHTML, /Draft tersimpan/);
});

test('autosave stops background work for detached forms', () => {
    const env = setup();
    env.listeners.input(); env.form.isConnected = false;
    env.runTimer(2000); env.runTimer(30000);
    assert.equal(env.writes(), 0);
    assert.equal(env.timers.size, 0);
});

test('submission cancels delayed saves so cleared drafts are not recreated', () => {
    const env = setup();
    env.listeners.input(); env.runTimer(2000);
    env.listeners.input(); env.listeners.submit();
    assert.equal(env.stored.size, 0);
    assert.equal([...env.timers.values()].some(timer => [2000, 30000].includes(timer.ms)), false);
});
