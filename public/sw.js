// Family Hub - Vanilla Service Worker (ADR-009)
const STATIC_CACHE = 'fh-static-v1';
const DYNAMIC_CACHE = 'fh-dynamic-v1';

const STATIC_ASSETS = [
    '/offline.html',
    '/manifest.json',
    '/icons/icon-192.svg',
    '/icons/icon-512.svg'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        }).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
                    .map((key) => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// Cache purge message listener for auth/household context changes (CORE-13 AC)
self.addEventListener('message', (event) => {
    if (event.data && (event.data.type === 'CLEAR_CACHE' || event.data.type === 'AUTH_CHANGED')) {
        event.waitUntil(
            caches.delete(DYNAMIC_CACHE).then(() => {
                if (event.ports && event.ports[0]) {
                    event.ports[0].postMessage({ success: true });
                }
            })
        );
    }
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Do not cache non-GET requests or Livewire AJAX updates
    if (request.method !== 'GET' || request.url.includes('/livewire/')) {
        return;
    }

    // HTML Navigation requests: Network-first, fallback to cache, fallback to offline.html
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    // Only cache valid OK responses
                    if (networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(DYNAMIC_CACHE).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(() => {
                    return caches.match(request).then((cachedResponse) => {
                        return cachedResponse || caches.match('/offline.html');
                    });
                })
        );
        return;
    }

    // Static assets (CSS, JS, images, fonts): Cache-first with network fallback
    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(request).then((networkResponse) => {
                if (networkResponse.status === 200 && (
                    request.url.includes('/build/') ||
                    request.url.includes('/icons/') ||
                    request.destination === 'style' ||
                    request.destination === 'script' ||
                    request.destination === 'image' ||
                    request.destination === 'font'
                )) {
                    const responseClone = networkResponse.clone();
                    caches.open(STATIC_CACHE).then((cache) => {
                        cache.put(request, responseClone);
                    });
                }
                return networkResponse;
            });
        })
    );
});

// Web Push event listener
self.addEventListener('push', (event) => {
    let payload = { title: 'Family Hub', body: 'Pemberitahuan baru', data: { url: '/' } };

    if (event.data) {
        try {
            payload = event.data.json();
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    const options = {
        body: payload.body || '',
        icon: '/icons/icon-192.svg',
        badge: '/icons/icon-192.svg',
        data: payload.data || { url: '/' },
        actions: payload.actions || []
    };

    event.waitUntil(
        self.registration.showNotification(payload.title || 'Family Hub', options)
    );
});

// Notification click event listener
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const targetUrl = event.notification.data && event.notification.data.url
        ? event.notification.data.url
        : '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
