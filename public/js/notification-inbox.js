(() => {
    if (window.notificationInboxInitialized) return;
    window.notificationInboxInitialized = true;
    let pending = false;
    const acknowledged = new WeakSet();
    function paint(bell, unread, showNew = true) {
        if (!bell.isConnected || !Number.isInteger(unread) || unread < 0) return;
        const badge = bell.querySelector('[data-notification-count]');
        const previous = Number(badge.dataset.notificationCount);
        badge.dataset.notificationCount = String(unread);
        badge.textContent = unread > 99 ? '99+' : String(unread);
        badge.hidden = unread === 0;
        bell.setAttribute('aria-label', unread ? `Notifications, ${unread} unread` : 'Notifications');
        if (showNew && unread > previous) {
            const banner = document.querySelector('[data-notification-new]');
            if (banner) banner.hidden = false;
        }
    }
    async function refresh() {
        const bell = document.querySelector('[data-notification-bell]');
        if (!bell || document.hidden || pending) return;
        pending = true;
        try {
            const inbox = document.querySelector('[data-notification-inbox]');
            const through = Number(inbox?.dataset.notificationThrough || 0);
            if (inbox && through > 0 && !acknowledged.has(inbox)) {
                const response = await fetch(inbox.dataset.notificationSeenUrl, {
                    method: 'POST', credentials: 'same-origin', cache: 'no-store',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ through }),
                });
                if (!response.ok) return;
                const result = await response.json();
                if (!inbox.isConnected || !bell.isConnected) return;
                acknowledged.add(inbox);
                paint(bell, result.unread, false);
                if (result.unread > 0) {
                    const banner = inbox.querySelector('[data-notification-new]');
                    if (banner) banner.hidden = false;
                }
                inbox.querySelectorAll('[data-notification-record]').forEach(card => {
                    if (Number(card.dataset.notificationRecord) > through) return;
                    card.classList.replace('border-rose-700', 'border-slate-800');
                    const status = card.querySelector('[data-notification-status]');
                    if (status) { status.textContent = 'Read'; status.classList.replace('text-rose-300', 'text-slate-400'); }
                });
                return;
            }
            const response = await fetch(bell.dataset.notificationSummaryUrl, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const result = await response.json();
            if (!bell.isConnected || !Number.isInteger(result.unread) || result.unread < 0) return;
            paint(bell, result.unread);
        } catch (_) { /* Keep server-rendered counts if the connection is unavailable. */ }
        finally {
            pending = false;
            if (bell !== document.querySelector('[data-notification-bell]')) refresh();
        }
    }
    document.addEventListener('DOMContentLoaded', refresh);
    document.addEventListener('visibilitychange', refresh);
    window.addEventListener('pageshow', refresh);
    document.addEventListener('turbo:load', refresh);
    if ('serviceWorker' in navigator) navigator.serviceWorker.addEventListener('message', event => {
        if (event.data?.type === 'account-notification') refresh();
    });
    if (document.readyState !== 'loading') refresh();
})();
