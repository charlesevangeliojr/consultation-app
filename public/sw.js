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
