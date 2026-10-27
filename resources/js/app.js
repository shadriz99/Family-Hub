import './bootstrap';
import { moneyInput } from './money-input';

// Dark mode management (system preference + localStorage override)
function initTheme() {
    const stored = typeof localStorage !== 'undefined' ? localStorage.getItem('theme') : null;
    if (stored === 'dark' || (!stored && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.setAttribute('data-theme', 'light');
    }
}

window.toggleDarkMode = function () {
    const isDark = document.documentElement.classList.contains('dark');
    if (isDark) {
        document.documentElement.classList.remove('dark');
        document.documentElement.setAttribute('data-theme', 'light');
        if (typeof localStorage !== 'undefined') localStorage.setItem('theme', 'light');
    } else {
        document.documentElement.classList.add('dark');
        document.documentElement.setAttribute('data-theme', 'dark');
        if (typeof localStorage !== 'undefined') localStorage.setItem('theme', 'dark');
    }
};

initTheme();

document.addEventListener('alpine:init', () => {
    window.Alpine.data('moneyInput', moneyInput);

    window.Alpine.store('theme', {
        dark: document.documentElement.classList.contains('dark'),
        toggle() {
            window.toggleDarkMode();
            this.dark = document.documentElement.classList.contains('dark');
        }
    });

    window.Alpine.store('network', {
        isOffline: typeof navigator !== 'undefined' ? !navigator.onLine : false,
        init() {
            if (typeof window !== 'undefined') {
                window.addEventListener('online', () => { this.isOffline = false; });
                window.addEventListener('offline', () => { this.isOffline = true; });
            }
        }
    });
});
