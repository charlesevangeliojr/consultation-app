(() => {
    if (window.portalTourInitialized) return;
    window.portalTourInitialized = true;
    const remembered = new Set();
    let index = 0;
    let timer;
    let returnFocus;
    const dialog = () => document.querySelector('[data-portal-tour]');
    const steps = modal => JSON.parse(modal.querySelector('[data-portal-tour-steps]').textContent);
    function render(modal) {
        const items = steps(modal);
        const step = items[index];
        modal.querySelector('[data-portal-tour-progress]').textContent = `Step ${index + 1} of ${items.length}`;
        modal.querySelector('[data-portal-tour-title]').textContent = step.title;
        modal.querySelector('[data-portal-tour-text]').textContent = step.text;
        modal.querySelector('[data-portal-tour-location]').textContent = step.navigation;
        const link = modal.querySelector('[data-portal-tour-page]');
        link.href = step.url;
        link.textContent = `${step.action} →`;
        modal.querySelector('[data-portal-tour-back]').disabled = index === 0;
        modal.querySelector('[data-portal-tour-next]').textContent = index === items.length - 1 ? 'Finish tour' : 'Next';
    }
    function finish(modal) {
        remembered.add(modal.dataset.tourKey);
        try { localStorage.setItem(modal.dataset.tourKey, 'seen'); } catch (_) { /* Remember for this visit. */ }
        modal.dataset.tourRequired = 'false';
        fetch(modal.dataset.tourCompleteUrl, {
            method: 'POST', credentials: 'same-origin', keepalive: true,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
        }).catch(() => { /* A failed acknowledgement will be retried on a future visit. */ });
        modal.close();
        document.documentElement.classList.remove('portal-tour-open');
        if (returnFocus?.isConnected) returnFocus.focus();
        document.dispatchEvent(new Event('portal-tour:finished'));
    }
    function open(modal) {
        if (!modal || modal.open || document.querySelector('dialog[open]')) return;
        returnFocus = document.activeElement;
        index = 0;
        render(modal);
        modal.showModal();
        document.documentElement.classList.add('portal-tour-open');
    }
    function suggest() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const modal = dialog();
            if (!modal || remembered.has(modal.dataset.tourKey) || document.hidden || document.querySelector('dialog[open]')) return;
            if (document.activeElement?.matches('input, textarea, select')) return;
            if (modal.dataset.tourRequired !== 'true') return;
            open(modal);
        }, 500);
    }
    document.addEventListener('click', event => {
        const modal = dialog();
        if (!modal) return;
        if (event.target.closest('[data-portal-tour-start]')) open(modal);
        if (!modal.open) return;
        if (event.target.closest('[data-portal-tour-skip], [data-portal-tour-page]')) finish(modal);
        if (event.target.closest('[data-portal-tour-next]')) {
            if (index === steps(modal).length - 1) finish(modal);
            else { index++; render(modal); }
        }
        if (event.target.closest('[data-portal-tour-back]') && index > 0) { index--; render(modal); }
        if (event.target === modal) {
            const bounds = modal.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) finish(modal);
        }
    });
    document.addEventListener('cancel', event => {
        if (!event.target.matches('[data-portal-tour]')) return;
        event.preventDefault();
        finish(event.target);
    }, true);
    // Wait for the install suggestion (or another modal) to close first.
    document.addEventListener('close', suggest, true);
    document.addEventListener('DOMContentLoaded', suggest);
    document.addEventListener('turbo:load', suggest);
    document.addEventListener('turbo:before-cache', () => {
        clearTimeout(timer);
        if (dialog()?.open) dialog().close();
        clearTimeout(timer);
        document.documentElement.classList.remove('portal-tour-open');
    });
    if (document.readyState !== 'loading') suggest();
})();
