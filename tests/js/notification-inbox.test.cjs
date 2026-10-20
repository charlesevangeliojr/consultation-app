const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function setup(hidden = false) {
    const calls = [];
    const messages = {};
    const events = {};
    const badge = { dataset: { notificationCount: '3' }, hidden: false };
    const bell = { isConnected: true, dataset: { notificationSummaryUrl: '/notifications/summary' }, querySelector: () => badge, setAttribute() {} };
    const status = { textContent: 'Unread', classList: { replace() {} } };
    const card = { dataset: { notificationRecord: '9' }, classList: { replace() {} }, querySelector: () => status };
    const banner = { hidden: true };
    const inbox = { isConnected: true, dataset: { notificationThrough: '9', notificationSeenUrl: '/notifications/read-all' }, querySelectorAll: () => [card], querySelector: () => banner };
    const document = { hidden, readyState: 'complete',
        querySelector: selector => selector === '[data-notification-bell]' ? bell : selector === '[data-notification-inbox]' ? inbox : selector === '[data-notification-new]' ? banner : { content: 'test-csrf' },
        addEventListener: (event, fn) => { events[event] = fn; },
    };
    vm.runInNewContext(fs.readFileSync('public/js/notification-inbox.js', 'utf8'), {
        window: { addEventListener() {} }, document,
        navigator: { serviceWorker: { addEventListener: (event, fn) => { messages[event] = fn; } } },
        fetch: async (url, options) => { calls.push({ url, options }); return { ok: true, json: async () => ({ unread: options.method === 'POST' ? 0 : 1 }) }; },
    });
    return { calls, badge, status, banner, messages, events, document };
}

test('opening inbox acknowledges its snapshot and removes the navbar number without deleting history', async () => {
    const state = setup();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(state.calls[0].url, '/notifications/read-all');
    assert.equal(state.calls[0].options.method, 'POST');
    assert.equal(state.calls[0].options.headers['X-CSRF-TOKEN'], 'test-csrf');
    assert.deepEqual(JSON.parse(state.calls[0].options.body), { through: 9 });
    assert.equal(state.badge.hidden, true);
    assert.equal(state.status.textContent, 'Read');
    state.messages.message({ data: { type: 'account-notification' } });
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(state.calls.length, 2);
    assert.equal(state.calls[1].url, '/notifications/summary', 'New arrivals are counted, not silently marked read');
    assert.equal(state.badge.hidden, false);
    assert.equal(state.badge.dataset.notificationCount, '1');
    assert.equal(state.banner.hidden, false);
});

test('a background tab does not mark notifications read until it is opened', async () => {
    const state = setup(true);
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(state.calls.length, 0);
    state.document.hidden = false;
    state.events.visibilitychange();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(state.calls[0].options.method, 'POST');
    assert.equal(state.badge.hidden, true);
});
