const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { test } = require('node:test');

const source = fs.readFileSync(path.join(__dirname, '../../resources/views/auth/register.blade.php'), 'utf8');
const start = source.indexOf('            function updateValidity');
const end = source.indexOf('            fields.forEach(field => {', start);
assert.ok(start >= 0 && end > start, 'Registration validation functions must exist');
const logic = source.slice(start, end);

function field(name, value, options = {}) {
    return {
        name, value, required: false, maxLength: -1, message: '', reported: false, ...options,
        setCustomValidity(message) { this.message = message; },
        checkValidity() {
            return !this.message && (!this.pattern || !this.value || new RegExp(this.pattern).test(this.value));
        },
        reportValidity() { this.reported = true; return this.checkValidity(); },
    };
}

function validation(inputs, serverErrors = {}, initialValues = new Map()) {
    const sections = inputs.map((step, i) => ({
        dataset: { formStep: String(i + 1) }, querySelectorAll() { return step; },
    }));
    const password = inputs.flat().find(input => input.name === 'password');
    const make = new Function('sections', 'serverErrors', 'serverErrorValues', 'document', `
        let current = 3;
        function render() {}
        ${logic}
        return { validateStep, updateValidity, current: () => current };
    `);
    return make(sections, serverErrors, initialValues, { getElementById: () => password });
}

test('unchanged server errors block Continue until the field is edited', () => {
    const email = field('email', 'used@example.com', { required: true });
    const api = validation([[email]], { email: ['Email is already taken.'] }, new Map([['email', email.value]]));
    assert.equal(api.validateStep(1), false);
    assert.equal(api.current(), 1);
    assert.equal(email.message, 'Email is already taken.');
    email.value = 'new@example.com';
    assert.equal(api.validateStep(1), true);
});

test('optional inputs are validated and blank required inputs are blocked', () => {
    const middleName = field('middle_name', 'x'.repeat(101), { maxLength: 100 });
    const firstName = field('first_name', 'Sam', { required: true });
    const api = validation([[middleName, firstName]]);
    assert.equal(api.validateStep(1), false);
    assert.equal(middleName.reported, true);
    middleName.value = '';
    assert.equal(api.validateStep(1), true);
    firstName.value = '   ';
    assert.equal(api.validateStep(1), false);
});

test('invalid contact and LRN patterns block the student details step', () => {
    const contact = field('contact_number', '123', { required: true, pattern: '^9[0-9]{9}$' });
    const lrn = field('lrn', '123', { required: true, pattern: '^[0-9]{12}$' });
    const api = validation([[], [contact, lrn]]);
    assert.equal(api.validateStep(2), false);
    contact.value = '9171234567';
    assert.equal(api.validateStep(2), false);
    lrn.value = '123456789012';
    assert.equal(api.validateStep(2), true);
});

test('short and mismatched passwords block final registration', () => {
    const password = field('password', 'short', { required: true });
    const confirmation = field('password_confirmation', 'different', { required: true });
    const api = validation([[], [], [password, confirmation]]);
    assert.equal(api.validateStep(3), false);
    assert.equal(password.message, 'Password must be at least 8 characters.');
    password.value = 'password123';
    assert.equal(api.validateStep(3), false);
    assert.equal(confirmation.message, 'Passwords must match.');
    confirmation.value = password.value;
    assert.equal(api.validateStep(3), true);
});

test('grade and section cannot be omitted from student details', () => {
    const grade = field('grade_level_id', '', { required: true });
    const section = field('section_id', '', { required: true });
    const api = validation([[], [grade, section]]);
    assert.equal(api.validateStep(2), false);
    grade.value = '1';
    assert.equal(api.validateStep(2), false);
    section.value = '2';
    assert.equal(api.validateStep(2), true);
});
