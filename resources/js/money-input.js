export function parseMoneyInput(input) {
    const match = input.trim().match(/^(-)?(?:Rp\s*)?(\d+|\d{1,3}(?:\.\d{3})+)(?:,(\d+))?\s*(rb|jt)?$/i);
    if (!match) throw new Error('invalid');
    const scale = { rb: 3, jt: 6 }[match[4]?.toLowerCase()] ?? 0;
    const fraction = match[3] ?? '';
    if (/[1-9]/.test(fraction.slice(scale))) throw new Error('invalid');
    const digits = match[2].replaceAll('.', '') + fraction.slice(0, scale).padEnd(scale, '0');
    const value = BigInt((match[1] ?? '') + digits);
    if (value > BigInt(Number.MAX_SAFE_INTEGER) || value < BigInt(Number.MIN_SAFE_INTEGER)) {
        throw new Error('range');
    }
    return Number(value);
}

export function moneyInput(messages) {
    return {
        value: null,
        display: '',
        error: '',
        lastValue: null,
        init() {
            this.$watch('value', value => {
                if (value === this.lastValue) return;
                this.applyValue(value);
            });
            this.$nextTick(() => this.applyValue(this.value));
        },
        applyValue(value) {
            if (value !== null && !Number.isSafeInteger(value)) {
                this.error = messages.range;
                this.display = String(value);
                this.lastValue = null;
                this.value = null;
            } else {
                this.display = value === null ? '' : String(value).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                this.error = '';
                this.lastValue = value;
            }
            this.$refs.input.setCustomValidity(this.error);
        },
        update(event) {
            const input = event.target;
            const raw = input.value;
            const before = raw.slice(0, input.selectionStart).replace(/\D/g, '').length;
            try {
                const editingDigits = (event.inputType === 'insertText' && /^\d$/.test(event.data ?? ''))
                    || event.inputType?.startsWith('delete');
                const candidate = editingDigits ? raw.replaceAll('.', '') : raw;
                const value = raw.trim() === '' ? null : parseMoneyInput(candidate);
                this.lastValue = value;
                this.value = value;
                this.display = value === null ? '' : String(value).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                this.error = '';
                input.value = this.display;
                let cursor = this.display.startsWith('-') ? 1 : 0;
                let digits = 0;
                while (cursor < this.display.length && digits < before) {
                    if (/\d/.test(this.display[cursor])) digits++;
                    cursor++;
                }
                input.setSelectionRange(cursor, cursor);
            } catch (error) {
                if (!(error instanceof Error) || !['invalid', 'range'].includes(error.message)) throw error;
                this.lastValue = null;
                this.value = null;
                this.display = raw;
                this.error = messages[error.message];
            }
            input.setCustomValidity(this.error);
        },
    };
}
