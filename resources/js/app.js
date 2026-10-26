import './bootstrap';
import { moneyInput } from './money-input';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('moneyInput', moneyInput);
});
