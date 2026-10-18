(() => {
    function initialize() {
        const dialog = document.querySelector('[data-admin-menu]');
        const trigger = document.querySelector('[data-admin-menu-open]');
        if (!dialog || !trigger || dialog.dataset.initialized) return;
        dialog.dataset.initialized = 'true';
        const controller = new AbortController();
        const options = { signal: controller.signal };
        const desktop = window.matchMedia('(min-width: 1024px)');
        const sync = () => {
            trigger.setAttribute('aria-expanded', String(dialog.open));
            document.documentElement.classList.toggle('admin-menu-open', dialog.open);
        };
        const close = () => {
            if (dialog.open) dialog.close();
            sync();
        };
        trigger.addEventListener('click', () => {
            if (!desktop.matches && !dialog.open) dialog.showModal();
            sync();
        }, options);
        dialog.querySelector('[data-admin-menu-close]').addEventListener('click', close, options);
        dialog.addEventListener('cancel', event => { event.preventDefault(); close(); }, options);
        dialog.addEventListener('close', sync, options);
        dialog.addEventListener('click', event => {
            const bounds = dialog.getBoundingClientRect();
            if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) close();
            if (event.target.closest('a')) close();
        }, options);
        desktop.addEventListener('change', () => { if (desktop.matches) close(); }, options);
        document.addEventListener('turbo:before-cache', () => {
            close();
            controller.abort();
            delete dialog.dataset.initialized;
        }, { once: true, signal: controller.signal });
        sync();
    }
    document.addEventListener('DOMContentLoaded', initialize);
    document.addEventListener('turbo:load', initialize);
    if (document.readyState !== 'loading') initialize();
})();
