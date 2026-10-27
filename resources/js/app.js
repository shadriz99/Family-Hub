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

// Universal Clipboard Copy with HTTP / non-secure context fallback
window.copyToClipboard = async function (text) {
    if (typeof navigator !== 'undefined' && navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (_) {
            // Fall through to legacy command
        }
    }

    try {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        textarea.style.top = '0';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        const success = document.execCommand('copy');
        document.body.removeChild(textarea);
        return Boolean(success);
    } catch (_) {
        return false;
    }
};

// PWA Service Worker Registration & Auth Cache Purge (CORE-13)
window.clearPwaCache = function () {
    if (typeof navigator !== 'undefined' && 'serviceWorker' in navigator && navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_CACHE' });
    }
};

if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').then((reg) => {
            reg.addEventListener('updatefound', () => {
                const installing = reg.installing;
                if (installing) {
                    installing.addEventListener('statechange', () => {
                        if (installing.state === 'installed' && navigator.serviceWorker.controller) {
                            window.dispatchEvent(new CustomEvent('pwa:update-available'));
                        }
                    });
                }
            });
        }).catch((err) => {
            console.warn('SW registration skipped:', err);
        });
    });

    document.addEventListener('submit', (e) => {
        if (e.target && e.target.action && e.target.action.includes('/logout')) {
            window.clearPwaCache();
        }
    });
}
