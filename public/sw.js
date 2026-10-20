/* Cache only the public offline screen. Never cache accounts, HTML pages,
   requests, photos, credentials, or consultation responses. */
const CACHE = 'consultation-pwa-offline-v1';
const OFFLINE = '/pwa/offline.html';
self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE).then(cache => cache.add(OFFLINE)).then(() => self.skipWaiting()));
});
self.addEventListener('activate', event => {
    event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key.startsWith('consultation-pwa-offline-') && key !== CACHE).map(key => caches.delete(key)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET' || event.request.mode !== 'navigate' || new URL(event.request.url).origin !== self.location.origin) return;
    event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE)));
});

// Web Push wakes this worker even when the app's tabs are closed. Payloads are
// generic account updates: never include names, LRN, reasons, or appointment times.
self.addEventListener('push', event => {
    let data = {};
    try { data = event.data?.json() || {}; } catch (_) { /* Show a safe generic update. */ }
    const allowed = ['/consultations', '/consultations/teacher/requests', '/consultations/student/consultations', '/consultations/teacher/profile', '/admin', '/admin/accounts'];
    const path = allowed.includes(data.url) ? data.url : '/consultations';
    let icon = '/pwa/icon-192.png';
    try {
        const candidate = new URL(data.icon, self.location.origin);
        if (candidate.origin === self.location.origin && candidate.pathname.startsWith('/pwa/')) icon = candidate.href;
    } catch (_) { /* Default public icon. */ }
    event.waitUntil(self.registration.showNotification(typeof data.title === 'string' ? data.title.slice(0, 80) : 'Consultation update', {
        body: typeof data.body === 'string' ? data.body.slice(0, 200) : 'Open your account to review a consultation update.',
        icon, badge: '/pwa/icon-192.png', data: { url: path }, silent: false,
    }).then(() => self.clients.matchAll({ type: 'window', includeUncontrolled: true })).then(windows => {
        windows.forEach(client => client.postMessage({ type: 'account-notification' }));
    }));
});
self.addEventListener('notificationclick', event => {
    event.notification.close();
    const allowed = ['/consultations', '/consultations/teacher/requests', '/consultations/student/consultations', '/consultations/teacher/profile', '/admin', '/admin/accounts'];
    const path = allowed.includes(event.notification.data?.url) ? event.notification.data.url : '/consultations';
    const url = new URL(path, self.location.origin).href;
    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(async windows => {
        for (const client of windows) {
            if (new URL(client.url).origin !== self.location.origin) continue;
            if ('navigate' in client) await client.navigate(url);
            return client.focus();
        }
        return self.clients.openWindow(url);
    }));
});
