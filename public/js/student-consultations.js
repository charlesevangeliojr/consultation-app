(() => {
    if (window.studentConsultationsInitialized) return;
    window.studentConsultationsInitialized = true;
    let cleanup = () => {};

    function init() {
        cleanup();
        const list = document.querySelector('[data-student-consultations]');
        if (!list || !('IntersectionObserver' in window)) return;
        const pending = new Set();
        const controller = new AbortController();
        const observer = new IntersectionObserver(entries => {
            if (document.hidden) return;
            for (const entry of entries) {
                if (!entry.isIntersecting || entry.intersectionRatio < 0.5) continue;
                const card = entry.target;
                const id = card.dataset.unseenRejection;
                if (!id || pending.has(id)) continue;
                pending.add(id);
                observer.unobserve(card);
                fetch(list.dataset.seenUrl, {
                    method: 'POST', credentials: 'same-origin', signal: controller.signal,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ ids: [Number(id)] }),
                }).then(response => {
                    if (!response.ok) throw new Error('Could not mark consultation as seen');
                    return response.json();
                }).then(result => {
                    if (!list.isConnected) return;
                    delete card.dataset.unseenRejection;
                    // Rejections only decrease this count, including concurrent receipts.
                    document.querySelectorAll('a').forEach(link => {
                        if (link.href !== list.dataset.consultationsUrl) return;
                        const badge = link.querySelector('[data-navigation-count]');
                        if (!badge) return;
                        const count = Math.min(Number(badge.dataset.navigationCount), result.count);
                        badge.dataset.navigationCount = String(count);
                        badge.textContent = String(count);
                        badge.hidden = count === 0;
                        const label = `${count} unfinished consultations or unseen rejections`;
                        badge.setAttribute('aria-label', label);
                        badge.title = label;
                    });
                }).catch(() => { /* Leave unread on failure; retry on the next visit. */ });
            }
        }, { threshold: 0.5 });
        const observe = () => list.querySelectorAll('[data-unseen-rejection]').forEach(card => {
            if (!pending.has(card.dataset.unseenRejection)) {
                observer.unobserve(card);
                observer.observe(card);
            }
        });
        document.addEventListener('visibilitychange', observe);
        observe();
        cleanup = () => {
            observer.disconnect();
            controller.abort();
            document.removeEventListener('visibilitychange', observe);
        };
    }

    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('turbo:load', init);
    document.addEventListener('turbo:before-cache', () => cleanup());
    if (document.readyState !== 'loading') init();
})();
