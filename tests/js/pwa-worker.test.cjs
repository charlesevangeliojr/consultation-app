const assert = require('node:assert/strict');
const { test } = require('node:test');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../public/sw.js'), 'utf8');

function setup(offline = false) {
    const handlers = {};
    const cached = [];
    const removed = [];
    const requested = [];
    const fallback = { offline: true };
    vm.runInNewContext(source, {
        URL,
        self: { location: { origin: 'https://school.example' }, addEventListener: (name, handler) => { handlers[name] = handler; }, skipWaiting: async () => {}, clients: { claim: async () => {} } },
        caches: {
            open: async () => ({ add: async url => { cached.push(url); } }),
            keys: async () => ['unrelated-cache', 'consultation-pwa-offline-v0', 'consultation-pwa-offline-v1'],
            delete: async key => { removed.push(key); },
            match: async url => { assert.equal(url, '/pwa/offline.html'); return fallback; },
        },
        fetch: async request => { requested.push(request.url); if (offline) throw Error('offline'); return { online: true }; },
    });
    return { handlers, cached, removed, requested, fallback };
}

test('worker pre-caches only the public offline page and cleans only its own old caches', async () => {
    const state = setup();
    let work;
    state.handlers.install({ waitUntil: promise => { work = promise; } });
    await work;
    assert.deepEqual(state.cached, ['/pwa/offline.html']);
    state.handlers.activate({ waitUntil: promise => { work = promise; } });
    await work;
    assert.deepEqual(state.removed, ['consultation-pwa-offline-v0']);
});

test('worker does not intercept submissions, image requests, or external URLs', () => {
    const { handlers } = setup();
    for (const request of [
        { method: 'POST', mode: 'navigate', url: 'https://school.example/consultations/teachers/1/request-time' },
        { method: 'GET', mode: 'cors', url: 'https://school.example/storage/private-photo.png' },
        { method: 'GET', mode: 'navigate', url: 'https://external.example/' },
    ]) handlers.fetch({ request, respondWith: () => assert.fail('Sensitive or unrelated request was intercepted') });
});

test('navigation stays network-only online and returns a generic offline page on failure', async () => {
    for (const offline of [false, true]) {
        const state = setup(offline);
        let response;
        state.handlers.fetch({ request: { method: 'GET', mode: 'navigate', url: 'https://school.example/consultations/dashboard' }, respondWith: promise => { response = promise; } });
        assert.deepEqual(await response, offline ? { offline: true } : { online: true });
        assert.deepEqual(state.cached, []);
    }
});
