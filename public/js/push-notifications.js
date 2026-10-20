(() => {
    if (window.consultationPushInitialized) return;
    window.consultationPushInitialized = true;
    let current;
    const ios = () => /iPhone|iPad|iPod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const standalone = () => matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    const supported = () => isSecureContext && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && (!ios() || standalone());
    function preference(state, enabled) {
        try { if (enabled) localStorage.setItem(state.config.userKey, 'true'); else localStorage.removeItem(state.config.userKey); } catch (_) { /* Optional preference. */ }
    }
    function render(state, message) {
        if (current !== state || !state.node.isConnected) return;
        const panel = document.querySelector('[data-push-settings]');
        if (!panel) return;
        panel.querySelector('[data-push-status]').textContent = message;
        panel.querySelector('[data-push-enable]').disabled = state.busy || !state.config.enabled || !supported() || Notification.permission === 'denied' || state.subscribed;
        panel.querySelector('[data-push-disable]').disabled = state.busy || !state.subscription;
        panel.querySelector('[data-push-test]').disabled = state.busy || !state.subscribed || !state.config.enabled;
    }
    async function api(state, url, body, method = 'POST') {
        const response = await fetch(url, { method, credentials: 'same-origin', headers: {
            'Content-Type': 'application/json', 'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        }, body: JSON.stringify(body) });
        const result = await response.json().catch(() => null);
        if (!result) throw new Error('The server returned an invalid response. Reload this page and try again.');
        if (!response.ok) throw new Error(result.message || 'Could not update notifications. Please try again.');
        return result;
    }
    function serverKey(key) {
        const base64 = key.replace(/-/g, '+').replace(/_/g, '/');
        return Uint8Array.from(atob(base64 + '='.repeat((4 - base64.length % 4) % 4)), character => character.charCodeAt(0));
    }
    function matchingKey(subscription, key) {
        if (!subscription?.options.applicationServerKey) return true;
        const existing = new Uint8Array(subscription.options.applicationServerKey);
        const expected = serverKey(key);
        return existing.length === expected.length && !existing.some((byte, i) => byte !== expected[i]);
    }
    async function registration() {
        await navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' });
        return navigator.serviceWorker.ready;
    }
    async function init() {
        const node = document.querySelector('[data-push-config]');
        if (!node || current?.node === node) return;
        const state = current = { node, config: JSON.parse(node.textContent), subscribed: false, subscription: null, busy: false };
        if (!supported()) {
            render(state, ios() && !standalone() ? 'On iPhone/iPad, install this app on your Home Screen and open it there before enabling notifications.' : 'Web Push requires HTTPS and a supported browser with notifications enabled.');
            return;
        }
        if (!state.config.enabled) {
            render(state, state.config.setupMessage || 'Push delivery is not configured yet. Ask the school administrator to complete the server setup.');
            return;
        }
        if (Notification.permission === 'denied') {
            render(state, 'Notifications are blocked. Change this site’s notification permission in your browser or device settings.');
            return;
        }
        state.busy = true;
        render(state, 'Checking notifications on this device…');
        try {
            const worker = await registration();
            if (current !== state) return;
            state.subscription = await worker.pushManager.getSubscription();
            if (state.subscription) {
                const result = await api(state, state.config.statusUrl, { endpoint: state.subscription.endpoint });
                if (current !== state) return;
                state.subscribed = result.subscribed;
                let remembered = false;
                try { remembered = localStorage.getItem(state.config.userKey) === 'true'; } catch (_) { /* Optional preference. */ }
                // Reattach only an existing subscription to the same account that opted in.
                // Never request permission or silently subscribe a different account.
                if (!state.subscribed && remembered && Notification.permission === 'granted' && matchingKey(state.subscription, state.config.publicKey)) {
                    await api(state, state.config.subscribeUrl, state.subscription.toJSON());
                    state.subscribed = true;
                }
                if (state.subscribed) preference(state, true);
            }
            render(state, state.subscribed ? 'Notifications are enabled for this account on this device.' : 'Notifications are off for this account on this device. Press Enable to opt in.');
        } catch (_) {
            render(state, 'Could not check notification settings. Refresh the page or try enabling again.');
        } finally {
            state.busy = false;
            if (current === state) {
                const message = document.querySelector('[data-push-status]')?.textContent;
                if (message) render(state, message);
            }
        }
    }
    document.addEventListener('click', async event => {
        const action = event.target.closest('[data-push-enable], [data-push-disable], [data-push-test]');
        const state = current;
        if (!action || !state || state.busy) return;
        state.busy = true;
        try {
            if (action.matches('[data-push-enable]')) {
                if (!supported() || !state.config.enabled) return;
                // Must be called synchronously from this click for iPhone permission prompts.
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') throw new Error('Notifications were not allowed. You can enable them later in your device or browser settings.');
                render(state, 'Enabling notifications…');
                const worker = await registration();
                state.subscription = await worker.pushManager.getSubscription();
                if (state.subscription && !matchingKey(state.subscription, state.config.publicKey)) {
                    await state.subscription.unsubscribe();
                    state.subscription = null;
                }
                state.subscription ||= await worker.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: serverKey(state.config.publicKey) });
                if (current !== state) return;
                await api(state, state.config.subscribeUrl, state.subscription.toJSON());
                state.subscribed = true;
                preference(state, true);
                render(state, 'Notifications are enabled. Send a test to check delivery on this device.');
            } else if (action.matches('[data-push-disable]') && state.subscription) {
                await api(state, state.config.unsubscribeUrl, { endpoint: state.subscription.endpoint }, 'DELETE');
                await state.subscription.unsubscribe();
                state.subscription = null;
                state.subscribed = false;
                preference(state, false);
                render(state, 'Notifications are disabled on this device.');
            } else if (action.matches('[data-push-test]') && state.subscription) {
                const result = await api(state, state.config.testUrl, { endpoint: state.subscription.endpoint });
                render(state, result.message);
            }
        } catch (error) {
            render(state, error.message || 'Could not update notifications. Please try again.');
        } finally {
            state.busy = false;
            const message = document.querySelector('[data-push-status]')?.textContent;
            if (message) render(state, message);
        }
    });
    document.addEventListener('DOMContentLoaded', init);
    document.addEventListener('turbo:load', init);
    document.addEventListener('turbo:before-cache', () => { current = null; });
    if (document.readyState !== 'loading') init();
})();
