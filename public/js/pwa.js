(() => {
    if (window.consultationPwaInitialized) return;
    window.consultationPwaInitialized = true;
    const standaloneMedia = matchMedia('(display-mode: standalone)');
    const isStandalone = () => standaloneMedia.matches || navigator.standalone === true;
    const isIos = () => /iPhone|iPad|iPod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isMobile = () => /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1) || matchMedia('(pointer: coarse)').matches;
    const root = document.documentElement;
    root.dataset.pwaStandalone = String(isStandalone());
    root.dataset.pwaLoading = String(isStandalone());
    let deferredPrompt = null;
    let installed = isStandalone();
    let dismissedUntil = 0;
    let caching = false;
    let checkingInstalled = false;
    let detectionVersion = 0;
    const appType = () => /^\/admin(?:\/|$)/.test(location.pathname) ? 'admin' : 'portal';
    let currentApp = appType();
    const storageKey = key => `consultation-pwa-${currentApp === 'admin' ? 'admin-' : ''}${key}`;
    function rememberInstalled() {
        installed = true;
        deferredPrompt = null;
        try { localStorage.setItem(storageKey('installed'), 'true'); } catch (_) { /* Keep it for this visit. */ }
    }
    function readPreferences() {
        installed = isStandalone();
        dismissedUntil = 0;
        try {
            dismissedUntil = Number(localStorage.getItem(storageKey('dismissed-until'))) || 0;
            installed = installed || localStorage.getItem(storageKey('installed')) === 'true';
        } catch (_) { /* Optional preferences. */ }
        if (isStandalone()) rememberInstalled();
    }
    function detectInstalled() {
        const version = ++detectionVersion;
        if (typeof navigator.getInstalledRelatedApps !== 'function' || installed) {
            checkingInstalled = false;
            return;
        }
        checkingInstalled = true;
        const app = currentApp;
        Promise.resolve().then(() => navigator.getInstalledRelatedApps()).then(apps => {
            if (version !== detectionVersion || currentApp !== app) return;
            const manifestPath = app === 'admin' ? '/admin/manifest.webmanifest' : '/manifest.webmanifest';
            const idPath = app === 'admin' ? '/admin/' : '/';
            const matches = apps.some(related => {
                if (related.platform !== 'webapp') return false;
                return [['url', manifestPath], ['id', idPath]].some(([key, path]) => {
                    if (!related[key]) return false;
                    try {
                        const url = new URL(related[key], location.origin);
                        return url.origin === location.origin && url.pathname === path;
                    } catch (_) { return false; }
                });
            });
            if (matches) rememberInstalled();
        }).catch(() => { /* Not supported or denied; use install events and saved state. */ }).finally(() => {
            if (version !== detectionVersion) return;
            checkingInstalled = false;
            refresh();
        });
    }
    function syncApp() {
        if (currentApp === appType()) return;
        currentApp = appType();
        deferredPrompt = null;
        readPreferences();
        detectInstalled();
    }
    readPreferences();
    detectInstalled();
    const canSuggest = () => !caching && !checkingInstalled && window.isSecureContext && 'serviceWorker' in navigator && isMobile() && !installed && !isStandalone() && Date.now() >= dismissedUntil && !document.querySelector('[data-portal-tour][data-tour-required="true"]');
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
            button.hidden = installed || isStandalone();
        });
        document.querySelectorAll('[data-pwa-install-status]').forEach(status => {
            status.textContent = isIos()
                ? 'iPhone / iPad: installation uses Share → Add to Home Screen in Safari.'
                : deferredPrompt
                    ? 'Ready to install. Tap Install app, then confirm in your browser.'
                    : 'If your browser does not offer a prompt, use its Install app or Add to Home screen menu.';
        });
    }
    function showInstructions() {
        const dialog = document.querySelector('[data-pwa-instructions]');
        if (!dialog) return;
        document.querySelectorAll('[data-pwa-install][open]').forEach(banner => banner.close());
        root.classList.remove('pwa-install-open');
        dialog.querySelector('[data-pwa-instructions-text]').textContent = !window.isSecureContext
            ? 'Installation requires HTTPS. Open the secure HTTPS address for this school first.'
            : isIos()
                ? 'In Safari, tap Share, then Add to Home Screen, then Add. If you are using another browser or an in-app browser, open this site in Safari first.'
                : 'Open this site in Chrome or Edge. In the browser menu, choose Install app or Add to Home screen, if available. If this is an in-app browser, open the site in your regular browser first.';
        const items = isIos()
            ? ['Open this page in Safari (not inside Facebook, Messenger, or another app).', 'Tap the Share button. If needed, open the browser menu to find Share.', 'Choose Add to Home Screen. You may need to scroll through the actions.', 'Keep Open as Web App enabled if shown, then tap Add. Open the new home-screen icon.']
            : ['Open the school’s HTTPS website in Chrome or Edge.', 'Tap Install app here when the browser offers installation, or open the browser menu.', 'Choose Install app or Add to Home screen, then confirm. Open the new app icon.'];
        const list = dialog.querySelector('[data-pwa-install-steps]');
        dialog.querySelector('[data-pwa-install-confirm]').hidden = !isIos();
        list.replaceChildren(...items.map(text => {
            const item = document.createElement('li');
            item.textContent = text;
            return item;
        }));
        if (!dialog.open) dialog.showModal();
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
    document.addEventListener('turbo:load', () => { caching = false; refresh(); });
    document.addEventListener('portal-tour:finished', refresh);
    document.addEventListener('turbo:before-cache', () => {
        caching = true;
        finishLoading();
        document.querySelectorAll('[data-pwa-instructions][open]').forEach(dialog => dialog.close());
        document.querySelectorAll('[data-pwa-install][open]').forEach(dialog => { dialog.close(); dialog.hidden = true; });
        root.classList.remove('pwa-install-open');
    });
    window.addEventListener('beforeinstallprompt', event => {
        syncApp();
        if (!isMobile() || isStandalone()) return;
        event.preventDefault();
        detectionVersion++;
        checkingInstalled = false;
        // The browser offering installation again is authoritative (for example,
        // after an uninstall), even if an earlier installation was remembered.
        installed = false;
        try { localStorage.removeItem(storageKey('installed')); } catch (_) { /* Optional preference. */ }
        deferredPrompt = event;
        refresh();
    });
    window.addEventListener('appinstalled', () => {
        rememberInstalled();
        document.querySelectorAll('[data-pwa-instructions][open]').forEach(dialog => dialog.close());
        refresh();
    });
    standaloneMedia.addEventListener('change', () => { if (isStandalone()) rememberInstalled(); finishLoading(); refresh(); });
    window.addEventListener('storage', event => {
        if (event.key === storageKey('installed') || event.key === storageKey('dismissed-until') || event.key === null) {
            readPreferences();
            refresh();
        }
    });
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
            if (installed || isStandalone()) return;
            if (deferredPrompt) {
                const prompt = deferredPrompt;
                deferredPrompt = null;
                button.disabled = true;
                document.querySelectorAll('[data-pwa-install][open]').forEach(banner => banner.close());
                root.classList.remove('pwa-install-open');
                try {
                    // Invoke directly from this click: browsers require a user gesture.
                    await prompt.prompt();
                    const choice = await prompt.userChoice;
                    if (choice.outcome === 'accepted') {
                        installed = true;
                        // appinstalled confirms success. Until then avoid nagging across navigation.
                        dismissedUntil = Date.now() + 7 * 24 * 60 * 60 * 1000;
                        try { localStorage.setItem(storageKey('dismissed-until'), String(dismissedUntil)); } catch (_) { /* Optional preference. */ }
                    }
                    else dismissInstall();
                } catch (_) { showInstructions(); }
                finally { button.disabled = false; refresh(); }
            } else {
                showInstructions();
            }
        }
        if (event.target.closest('[data-pwa-install-confirm]')) {
            rememberInstalled();
            event.target.closest('dialog').close();
            refresh();
        }
        if (event.target.closest('[data-pwa-instructions-close]')) {
            event.target.closest('dialog').close();
            refresh();
        }
    });
    document.addEventListener('close', event => {
        if (event.target.matches('[data-pwa-instructions]')) refresh();
    }, true);
    if (window.isSecureContext && 'serviceWorker' in navigator) {
        // Register even if this script is loaded after window.load (for example via navigation).
        navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' }).catch(() => { /* The site still works without installation. */ });
    }
    if (document.readyState !== 'loading') ready();
})();
