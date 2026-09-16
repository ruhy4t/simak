const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');

function setup() {
    const docListeners = new Map();
    const document = {
        addEventListener: (name, fn) => docListeners.set(name, fn),
        removeEventListener: name => docListeners.delete(name),
    };
    function element() {
        const classes = new Set(['hidden']);
        const listeners = new Map();
        return {
            isConnected: true,
            classList: { contains: x => classes.has(x), add: x => classes.add(x), remove: x => classes.delete(x) },
            focus() { document.activeElement = this; },
            addEventListener: (name, fn) => listeners.set(name, fn),
            removeEventListener: name => listeners.delete(name),
            click() { listeners.get('click')?.({ target: this }); },
        };
    }
    const elements = Object.fromEntries(['confirmModal', 'confirmTitle', 'confirmMessage', 'confirmIconContainer', 'confirmIcon', 'confirmOk', 'confirmCancel'].map(id => [id, element()]));
    document.getElementById = id => elements[id];
    const trigger = element(); trigger.focus();
    const context = { document, window: {} };
    const template = readFileSync('resources/views/components/confirm-modal.blade.php', 'utf8');
    vm.runInNewContext(template.match(/<script>([\s\S]*?)<\/script>/)[1], context);
    return {
        document, elements, trigger, show: context.window.showConfirm,
        key: key => docListeners.get('keydown')?.({ key, preventDefault() {} }),
        listeners: docListeners,
    };
}

test('confirmation focuses cancel, cycles Tab, and restores focus on Escape', async () => {
    const env = setup();
    const result = env.show({ message: 'Batalkan persetujuan?' });
    assert.equal(env.document.activeElement, env.elements.confirmCancel);
    env.key('Tab'); assert.equal(env.document.activeElement, env.elements.confirmOk);
    env.key('Tab'); assert.equal(env.document.activeElement, env.elements.confirmCancel);
    env.key('Escape'); assert.equal(await result, false);
    assert.equal(env.document.activeElement, env.trigger);
    assert.equal(env.elements.confirmModal.classList.contains('hidden'), true);
    assert.equal(env.listeners.has('keydown'), false);
});

test('a second confirmation cannot replace the active decision', async () => {
    const env = setup();
    const first = env.show({ message: 'First' });
    assert.equal(await env.show({ message: 'Second' }), false);
    assert.equal(env.elements.confirmMessage.textContent, 'First');
    env.elements.confirmOk.click();
    assert.equal(await first, true);
    assert.equal(env.document.activeElement, env.trigger);
});
