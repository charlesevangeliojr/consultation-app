(() => {
    if (window.accountActivityInitialized) return;
    window.accountActivityInitialized = true;
    let pending = false;
    let lastPing = 0;

    async function ping() {
        const url = document.querySelector('meta[name="account-activity-url"]')?.content;
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!url || !token || document.hidden || pending || Date.now() - lastPing < 60000) return;
        pending = true;
        lastPing = Date.now();
        try {
            await fetch(url, {
                method: 'POST', credentials: 'same-origin', redirect: 'error',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            });
        } catch {
            // Activity will be recorded on the next successful request.
        } finally {
            pending = false;
        }
    }

    setInterval(ping, 60000);
    document.addEventListener('visibilitychange', ping);
    document.addEventListener('turbo:load', ping);
    ping();
})();
