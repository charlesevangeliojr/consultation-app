const assert = require('node:assert/strict');
const { test } = require('node:test');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../public/js/theme.js'), 'utf8');

function setup({ saved = null, dark = false, blocked = false } = {}) {
    const events = {};
    const windowEvents = {};
    const root = { dataset: {}, style: {} };
    const attributes = {};
    const label = {};
    const meta = {};
    const button = { setAttribute: (key, value) => { attributes[key] = value; }, querySelector: () => label };
    const system = { matches: dark, addEventListener: (_, handler) => { system.change = handler; } };
    const stored = new Map(saved ? [['consultation-theme', saved]] : []);
    const context = {
        document: { documentElement: root, readyState: 'complete', addEventListener: (key, handler) => { events[key] = handler; }, querySelectorAll: selector => selector.includes('meta') ? [meta] : [button] },
        window: { matchMedia: () => system, addEventListener: (key, handler) => { windowEvents[key] = handler; } },
        localStorage: { getItem: key => { if (blocked) throw Error('blocked'); return stored.get(key); }, setItem: (key, value) => { if (blocked) throw Error('blocked'); stored.set(key, value); } },
    };
    vm.runInNewContext(source, context);
    return { root, attributes, label, meta, stored, system, events, windowEvents, context, click: () => events.click({ target: { closest: () => button } }) };
}

test('first visit follows OS theme and sets native color scheme and accessible label', () => {
    const light = setup();
    assert.equal(light.root.dataset.theme, 'light');
    assert.equal(light.root.style.colorScheme, 'light');
    assert.equal(light.attributes['aria-label'], 'Switch to dark mode');
    assert.equal(light.meta.content, '#f8fafc');
    const dark = setup({ dark: true });
    assert.equal(dark.root.dataset.theme, 'dark');
    assert.equal(dark.attributes['aria-pressed'], 'true');
});
test('explicit preference persists, overrides OS, and updates all labels', () => {
    const state = setup({ saved: 'dark' });
    state.click();
    assert.equal(state.root.dataset.theme, 'light');
    assert.equal(state.stored.get('consultation-theme'), 'light');
    assert.equal(state.label.textContent, 'Dark mode');
    state.system.matches = true;
    state.system.change();
    assert.equal(state.root.dataset.theme, 'light');
});
test('OS changes are respected until the user selects a theme', () => {
    const state = setup({ saved: 'invalid' });
    state.system.matches = true;
    state.system.change();
    assert.equal(state.root.dataset.theme, 'dark');
});
test('blocked storage does not prevent toggling', () => {
    const state = setup({ blocked: true });
    state.click();
    assert.equal(state.root.dataset.theme, 'dark');
});
test('cross-tab changes and Turbo navigation retain the active theme', () => {
    const state = setup();
    state.windowEvents.storage({ key: 'consultation-theme', newValue: 'dark' });
    state.events['turbo:load']();
    assert.equal(state.root.dataset.theme, 'dark');
    state.windowEvents.storage({ key: 'consultation-theme', newValue: null });
    assert.equal(state.root.dataset.theme, 'light');
    // Loading the same script from another page does not attach more handlers.
    const originalClick = state.events.click;
    vm.runInNewContext(source, state.context);
    assert.equal(state.events.click, originalClick);
});
