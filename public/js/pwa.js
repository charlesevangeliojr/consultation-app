(() => {
    if (window.consultationPwaInitialized) return;
    window.consultationPwaInitialized = true;
    const standaloneMedia = matchMedia('(display-mode: standalone)');
    const isStandalone = () => standaloneMedia.matches || navigator.standalone === true;
    const isMobile = () => /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1) || matchMedia('(pointer: coarse)').matches;
    const root = document.documentElement;
    root.dataset.pwaStandalone = String(isStandalone());
    root.dataset.pwaLoading = String(isStandalone());
    let deferredPrompt = null;
    let installed = isStandalone();
    let dismissedUntil = 0;
    const appType = () => /^\/admin(?:\/|$)/.test(location.pathname) ? 'admin' : 'portal';
    let currentApp = appType();
    const storageKey = key => `consultation-pwa-${currentApp === 'admin' ? 'admin-' : ''}${key}`;
    function readPreferences() {
        installed = isStandalone();
        dismissedUntil = 0;
        try {
            dismissedUntil = Number(localStorage.getItem(storageKey('dismissed-until'))) || 0;
            installed = installed || localStorage.getItem(storageKey('installed')) === 'true';
        } catch (_) { /* Optional preferences. */ }
    }
    function syncApp() {
        if (currentApp === appType()) return;
        currentApp = appType();
        deferredPrompt = null;
        readPreferences();
    }
    readPreferences();
    const canSuggest = () => window.isSecureContext && 'serviceWorker' in navigator && isMobile() && !installed && !isStandalone() && Date.now() >= dismissedUntil && !document.querySelector('[data-portal-tour][data-tour-required="true"]');
    function refresh() {
        syncApp();
        root.dataset.pwaStandalone = String(isStandalone());
        document.querySelectorAll('[data-pwa-install]').forEach(banner => {
            if (canSuggest()) {
                if (!banner.open && !document.querySelector('dialog[open]')) {
                    banner.hidden = false;
                    banner.showModal();
                }
            } else {
                if (banner.open) banner.close();
                banner.hidden = true;
            }
        });
        root.classList.toggle('pwa-install-open', !!document.querySelector('[data-pwa-install][open]'));
        document.querySelectorAll('[data-pwa-install-button]').forEach(button => {
            button.textContent = 'Install app';
        });
    }
    function dismissInstall() {
        dismissedUntil = Date.now() + 7 * 24 * 60 * 60 * 1000;
        try { localStorage.setItem(storageKey('dismissed-until'), String(dismissedUntil)); } catch (_) { /* Still dismissed in this visit. */ }
        refresh();
    }
    function finishLoading() {
        root.dataset.pwaLoading = 'false';
        document.querySelectorAll('[data-pwa-launch]').forEach(splash => { splash.hidden = true; });
    }
    // A failed image/CDN must never leave the installed app behind a stuck splash.
    const loadingTimeout = setTimeout(finishLoading, 8000);
    function ready() {
        refresh();
        if (document.readyState === 'complete') {
            requestAnimationFrame(() => requestAnimationFrame(() => { clearTimeout(loadingTimeout); finishLoading(); }));
        }
    }
    window.addEventListener('load', ready);
    window.addEventListener('pageshow', event => { if (event.persisted) finishLoading(); });
    document.addEventListener('DOMContentLoaded', ready);
    document.addEventListener('turbo:load', refresh);
    document.addEventListener('portal-tour:finished', refresh);
    document.addEventListener('turbo:before-cache', () => {
        finishLoading();
        document.querySelectorAll('[data-pwa-instructions][open]').forEach(dialog => dialog.close());
        document.querySelectorAll('[data-pwa-install][open]').forEach(dialog => { dialog.close(); dialog.hidden = true; });
        root.classList.remove('pwa-install-open');
    });
    window.addEventListener('beforeinstallprompt', event => {
        syncApp();
        if (!isMobile() || isStandalone()) return;
        event.preventDefault();
        // The browser offering installation again is authoritative (for example,
        // after an uninstall), even if an earlier installation was remembered.
        installed = false;
        try { localStorage.removeItem(storageKey('installed')); } catch (_) { /* Optional preference. */ }
        deferredPrompt = event;
        refresh();
    });
    window.addEventListener('appinstalled', () => {
        installed = true;
        try { localStorage.setItem(storageKey('installed'), 'true'); } catch (_) { /* Still hidden for this visit. */ }
        deferredPrompt = null;
        refresh();
    });
    standaloneMedia.addEventListener('change', () => { installed = isStandalone(); finishLoading(); refresh(); });
    document.addEventListener('cancel', event => {
        if (!event.target.matches('[data-pwa-install]')) return;
        event.preventDefault();
        dismissInstall();
    }, true);
    document.addEventListener('click', async event => {
        if (event.target.matches('[data-pwa-install]')) {
            const bounds = event.target.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dismissInstall();
        }
        if (event.target.closest('[data-pwa-dismiss]')) {
            dismissInstall();
        }
        const button = event.target.closest('[data-pwa-install-button]');
        if (button) {
            if (deferredPrompt) {
                const prompt = deferredPrompt;
                deferredPrompt = null;
                button.disabled = true;
                try {
                    await prompt.prompt();
                    const choice = await prompt.userChoice;
                    if (choice.outcome === 'accepted') installed = true;
                } catch (_) { /* Fall back to browser-specific instructions. */ }
                finally { button.disabled = false; refresh(); }
            } else {
                const dialog = document.querySelector('[data-pwa-instructions]');
                if (!dialog) return;
                const ios = /iPhone|iPad|iPod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
                dialog.querySelector('[data-pwa-instructions-text]').textContent = ios
                    ? 'In Safari, tap Share, then Add to Home Screen, then Add. If you are using another browser or an in-app browser, open this site in Safari first.'
                    : 'Open this site in Chrome or Edge. In the browser menu, choose Install app or Add to Home screen, if available. If this is an in-app browser, open the site in your regular browser first.';
                dialog.showModal();
            }
        }
        if (event.target.closest('[data-pwa-instructions-close]')) event.target.closest('dialog').close();
    });
    if (window.isSecureContext && 'serviceWorker' in navigator) {
        window.addEventListener('load', () => { navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' }).catch(() => { /* The site still works without installation. */ }); });
    }
    if (document.readyState !== 'loading') ready();
})();
