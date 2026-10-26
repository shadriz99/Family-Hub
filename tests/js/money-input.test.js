import { test } from 'node:test';
import assert from 'node:assert/strict';
import { parseMoneyInput, moneyInput } from '../../resources/js/money-input.js';

test('parses exact safe integers and abbreviations', () => {
    for (const [raw, value] of [['1.250.000', 1250000], ['1,25jt', 1250000], ['500rb', 500000],
        ['-Rp 50.000', -50000], ['0,000001jt', 1], ['0', 0],
        ['9.007.199.254.740.991', Number.MAX_SAFE_INTEGER], ['-9007199254740991', Number.MIN_SAFE_INTEGER]]) {
        assert.equal(parseMoneyInput(raw), value);
    }
});

test('rejects fractions, malformed grouping and unsafe integers', () => {
    for (const raw of ['1,5', '1.25', '1e6', '9007199254740992', '-9007199254740992', '0,0001rb', '', 'abc']) {
        assert.throws(() => parseMoneyInput(raw));
    }
});

test('invalid and blank edits clear prior values, preserving invalid text and caret', () => {
    const control = moneyInput({ invalid: 'invalid', range: 'range' });
    let validity = '';
    const input = { value: '1234567', selectionStart: 7, setSelectionRange(a) { this.cursor = a; }, setCustomValidity(message) { validity = message; } };
    control.update({ target: input });
    assert.equal(control.value, 1234567);
    assert.equal(input.value, '1.234.567');
    assert.equal(input.cursor, 9);
    input.value = '9007199254740992';
    control.update({ target: input });
    assert.equal(control.value, null);
    assert.equal(validity, 'range');
    assert.equal(control.display, '9007199254740992');
    input.value = '';
    control.update({ target: input });
    assert.equal(control.value, null);
    assert.equal(validity, '');
});

test('allows continued typing and backspace through automatic group separators', () => {
    const control = moneyInput({ invalid: 'invalid', range: 'range' });
    const input = { value: '1.2345', selectionStart: 6, setSelectionRange() {}, setCustomValidity() {} };
    control.update({ target: input, inputType: 'insertText', data: '5' });
    assert.equal(control.value, 12345);
    assert.equal(input.value, '12.345');
    input.value = '12.34';
    control.update({ target: input, inputType: 'deleteContentBackward' });
    assert.equal(control.value, 1234);
    assert.equal(input.value, '1.234');
});
