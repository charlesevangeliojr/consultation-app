const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function setup() {
    const card = { dataset: { unseenRejection: '12' } };
    const badges = [2, 2].map(count => ({ dataset: { navigationCount: String(count) }, setAttribute() {} }));
    const list = { isConnected: true, dataset: { seenUrl: '/seen', consultationsUrl: 'http://localhost/consultations/student/consultations' }, querySelectorAll: () => [card] };
    const calls = [];
    let callback;
    const document = {
        hidden: false, readyState: 'complete', addEventListener() {}, removeEventListener() {},
        querySelector: selector => selector === '[data-student-consultations]' ? list : { content: 'test-csrf' },
        querySelectorAll: () => badges.map(badge => ({ href: list.dataset.consultationsUrl, querySelector: () => badge })),
    };
    class Observer {
        constructor(fn) { callback = fn; }
        observe() {}
        unobserve() {}
        disconnect() {}
    }
    const context = { window: { IntersectionObserver: Observer }, document, IntersectionObserver: Observer, AbortController,
        fetch: async (url, options) => { calls.push({ url, options }); return { ok: true, json: async () => ({ count: 1 }) }; },
    };
    vm.runInNewContext(fs.readFileSync('public/js/student-consultations.js', 'utf8'), context);
    return { calls, document, card, badges, intersect: ratio => callback([{ target: card, isIntersecting: ratio > 0, intersectionRatio: ratio }]) };
}

test('rejections are marked seen only when visible, with both navigation badges updated once', async () => {
    const state = setup();
    assert.equal(state.calls.length, 0);
    state.intersect(0.25);
    assert.equal(state.calls.length, 0);
    state.intersect(1);
    state.intersect(1);
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(state.calls.length, 1);
    assert.equal(state.calls[0].options.method, 'POST');
    assert.equal(state.calls[0].options.headers['X-CSRF-TOKEN'], 'test-csrf');
    assert.deepEqual(JSON.parse(state.calls[0].options.body), { ids: [12] });
    assert.equal(state.card.dataset.unseenRejection, undefined);
    assert.ok(state.badges.every(badge => badge.dataset.navigationCount === '1' && !badge.hidden));
});

test('background tabs do not mark rejection cards seen', () => {
    const state = setup();
    state.document.hidden = true;
    state.intersect(1);
    assert.equal(state.calls.length, 0);
});
