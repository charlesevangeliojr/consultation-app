(() => {
    if (window.consultationThemeInitialized) return;
    window.consultationThemeInitialized = true;
    const key = 'consultation-theme';
    const system = window.matchMedia('(prefers-color-scheme: dark)');
    let preference = null;
    try {
        const saved = localStorage.getItem(key);
        if (saved === 'light' || saved === 'dark') preference = saved;
    } catch (_) { /* Themes still work when browser storage is blocked. */ }
    const current = () => preference || (system.matches ? 'dark' : 'light');
    function apply(root = document.documentElement) {
        const theme = current();
        root.dataset.theme = theme;
        root.style.colorScheme = theme;
    }
    function refresh() {
        apply();
        const dark = current() === 'dark';
        document.querySelectorAll('[data-theme-toggle]').forEach(button => {
            const label = `Switch to ${dark ? 'light' : 'dark'} mode`;
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            button.setAttribute('aria-pressed', String(dark));
            button.querySelector('[data-theme-label]').textContent = dark ? 'Light mode' : 'Dark mode';
        });
        document.querySelectorAll('meta[name="theme-color"]').forEach(meta => {
            meta.content = dark ? '#020617' : '#f8fafc';
        });
    }
    apply();
    document.addEventListener('click', event => {
        if (!event.target.closest('[data-theme-toggle]')) return;
        preference = current() === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem(key, preference); } catch (_) { /* Optional persistence. */ }
        refresh();
    });
    window.addEventListener('storage', event => {
        if (event.key !== key && event.key !== null) return;
        preference = event.newValue === 'dark' || event.newValue === 'light' ? event.newValue : null;
        refresh();
    });
    system.addEventListener('change', () => { if (!preference) refresh(); });
    document.addEventListener('turbo:before-render', event => apply(event.detail.newBody.ownerDocument.documentElement));
    document.addEventListener('turbo:load', refresh);
    document.addEventListener('DOMContentLoaded', refresh);
    if (document.readyState !== 'loading') refresh();
})();
