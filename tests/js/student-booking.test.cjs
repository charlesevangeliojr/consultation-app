const assert = require('node:assert/strict');
const { test } = require('node:test');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const script = fs.readFileSync(path.join(__dirname, '../../public/js/student-booking.js'), 'utf8');

function setup({ start = '13:00', end = '13:30', maximum = 30, busy = [] } = {}) {
    const input = (value, min, max) => ({ value, min, max, dataset: {}, message: '', setCustomValidity(message) { this.message = message; } });
    const caption = { textContent: '', dataset: {} };
    const form = {
        elements: { starts_at: input(start, '13:00', '16:59'), ends_at: input(end, '13:01', '17:00') },
        dataset: { bookingMaximum: String(maximum), bookingBusy: JSON.stringify(busy) },
        querySelector: () => caption,
    };
    const events = {};
    const document = { readyState: 'complete', querySelectorAll: () => [form], addEventListener: (name, handler) => { events[name] = handler; } };
    vm.runInNewContext(script, { document });
    return { form, caption, update: () => events.input({ target: { closest: () => form } }) };
}

test('teacher thirty-minute cap rejects a one-hour range and updates maximum end time', () => {
    const { form, caption } = setup({ end: '14:00' });
    assert.match(form.elements.ends_at.message, /no more than 30/);
    assert.equal(form.elements.ends_at.max, '13:30');
    assert.equal(caption.dataset.invalid, 'true');
});

test('one-hour cap accepts a one-hour consultation with live duration', () => {
    const { form, caption } = setup({ maximum: 60, end: '14:00' });
    assert.equal(form.elements.ends_at.message, '');
    assert.equal(caption.textContent, '60 minute consultation · Maximum 60 minutes');
});

test('start time can change without losing the original availability end bound', () => {
    const { form, update } = setup({ maximum: 60 });
    form.elements.starts_at.value = '16:30';
    form.elements.ends_at.value = '17:00';
    update();
    assert.equal(form.elements.ends_at.max, '17:00');
    assert.equal(form.elements.ends_at.message, '');
    form.elements.starts_at.value = '14:00';
    form.elements.ends_at.value = '14:45';
    update();
    assert.equal(form.elements.ends_at.max, '15:00');
    assert.equal(form.elements.ends_at.dataset.windowEnd, '17:00');
});

test('overlapping ranges are rejected but adjacent ranges remain bookable', () => {
    const { form, update } = setup({ start: '13:15', end: '13:45', busy: [{ start: '13:30', end: '14:00' }] });
    assert.match(form.elements.ends_at.message, /overlaps/);
    form.elements.starts_at.value = '14:00';
    form.elements.ends_at.value = '14:30';
    update();
    assert.equal(form.elements.ends_at.message, '');
});

test('reversed, empty, and out-of-window times are handled without stale errors', () => {
    const { form, caption, update } = setup({ start: '14:00', end: '13:30' });
    assert.match(form.elements.ends_at.message, /after start/);
    form.elements.ends_at.value = '';
    update();
    assert.equal(form.elements.ends_at.message, '');
    assert.match(caption.textContent, /Choose a start and end/);
    form.elements.starts_at.value = '12:45';
    form.elements.ends_at.value = '13:15';
    update();
    assert.match(form.elements.ends_at.message, /published availability/);
});
