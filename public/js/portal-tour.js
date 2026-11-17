(() => {
    if (window.portalTourInitialized) return;
    window.portalTourInitialized = true;
    const remembered = new Set();
    let index = 0;
    let timer;
    let returnFocus;
    const nextArrow = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>';
    const detectMode = () => (window.innerWidth < 1024 ? 'mobile' : 'desktop');
    const dialog = () => document.querySelector('[data-portal-tour]');
    const steps = modal => JSON.parse(modal.querySelector('[data-portal-tour-steps]').textContent);
    function render(modal) {
        const items = steps(modal);
        const step = items[index];
        const mode = detectMode();
        modal.querySelector('[data-portal-tour-progress]').textContent = `Step ${index + 1} of ${items.length}`;
        const image = modal.querySelector('[data-portal-tour-image]');
        if (image) {
            const src = mode === 'mobile' ? (step.mobileImage || step.desktopImage) : (step.desktopImage || step.mobileImage);
            if (src) image.src = src;
            image.dataset.mode = mode;
            image.alt = step.imageAlt ? `${step.imageAlt} (${mode} view)` : `${step.title} (${mode} view)`;
        }
        modal.querySelector('[data-portal-tour-title]').textContent = step.title;
        modal.querySelector('[data-portal-tour-text]').textContent = step.text;
        modal.querySelector('[data-portal-tour-location]').textContent = step.navigation;
        modal.querySelector('[data-portal-tour-back]').disabled = index === 0;
        const nextBtn = modal.querySelector('[data-portal-tour-next]');
        const isLast = index === items.length - 1;
        if (isLast) {
            nextBtn.textContent = 'Finish';
            nextBtn.setAttribute('aria-label', 'Finish');
            nextBtn.classList.remove('portal-tour-arrow');
        } else {
            nextBtn.innerHTML = nextArrow;
            nextBtn.setAttribute('aria-label', 'Next');
            nextBtn.classList.add('portal-tour-arrow');
        }
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
        if (event.target.closest('[data-portal-tour-skip]')) finish(modal);
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
    window.addEventListener('resize', () => {
        const modal = dialog();
        if (modal?.open) render(modal);
    });
    if (document.readyState !== 'loading') suggest();
})();
