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
    const notifications = [];
    const opened = [];
    const windows = [];
    vm.runInNewContext(source, {
        URL,
        self: { location: { origin: 'https://school.example' }, addEventListener: (name, handler) => { handlers[name] = handler; }, skipWaiting: async () => {},
            registration: { showNotification: async (title, options) => { notifications.push({ title, options }); } },
            clients: { claim: async () => {}, matchAll: async () => windows, openWindow: async url => { opened.push(url); } } },
        caches: {
            open: async () => ({ add: async url => { cached.push(url); } }),
            keys: async () => ['unrelated-cache', 'consultation-pwa-offline-v0', 'consultation-pwa-offline-v1'],
            delete: async key => { removed.push(key); },
            match: async url => { assert.equal(url, '/pwa/offline.html'); return fallback; },
        },
        fetch: async request => { requested.push(request.url); if (offline) throw Error('offline'); return { online: true }; },
    });
    return { handlers, cached, removed, requested, fallback, notifications, opened, windows };
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

test('background push displays a notification without fetching or caching private data', async () => {
    const state = setup();
    let work;
    state.handlers.push({ data: { json: () => ({ title: 'School', body: 'Your request was updated.', url: '/consultations/student/consultations', icon: '/pwa/icon-192.png' }) }, waitUntil: promise => { work = promise; } });
    await work;
    assert.equal(state.notifications[0].title, 'School');
    assert.equal(state.notifications[0].options.body, 'Your request was updated.');
    assert.equal(state.notifications[0].options.data.url, '/consultations/student/consultations');
    assert.deepEqual(state.cached, []);
    assert.deepEqual(state.requested, []);
});

test('malformed pushes and external notification targets fall back to a same-origin account page', async () => {
    const state = setup();
    let work;
    state.handlers.push({ data: { json: () => { throw Error('invalid JSON'); } }, waitUntil: promise => { work = promise; } });
    await work;
    assert.equal(state.notifications[0].title, 'Consultation update');
    state.handlers.notificationclick({ notification: { close() {}, data: { url: 'https://evil.example/steal' } }, waitUntil: promise => { work = promise; } });
    await work;
    assert.deepEqual(state.opened, ['https://school.example/consultations']);
});

test('notification clicks reuse an existing same-origin window', async () => {
    const state = setup();
    const navigated = [];
    let focused = false;
    state.windows.push({ url: 'https://school.example/consultations/dashboard', navigate: async url => { navigated.push(url); }, focus: async () => { focused = true; } });
    let work;
    state.handlers.notificationclick({ notification: { close() {}, data: { url: '/consultations/teacher/requests' } }, waitUntil: promise => { work = promise; } });
    await work;
    assert.deepEqual(navigated, ['https://school.example/consultations/teacher/requests']);
    assert.equal(focused, true);
    assert.deepEqual(state.opened, []);
});

test('push refreshes open inbox badges with a generic message only', async () => {
    const state = setup();
    const messages = [];
    state.windows.push({ postMessage: message => messages.push(message) });
    let work;
    state.handlers.push({ data: { json: () => ({ title: 'School', body: 'A consultation was updated.' }) }, waitUntil: promise => { work = promise; } });
    await work;
    assert.equal(messages.length, 1);
    assert.equal(messages[0].type, 'account-notification');
    assert.deepEqual(Object.keys(messages[0]), ['type']);
});
