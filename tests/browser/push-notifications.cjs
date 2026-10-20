// Uses fictional isolated demo accounts. Browser subscription/API delivery are mocked;
// this does not send any real notifications or save subscriptions to the school database.
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.APP_TEST_URL || 'http://127.0.0.1:8096';
const publicKey = Buffer.concat([Buffer.from([4]), Buffer.alloc(64, 1)]).toString('base64url');

async function run() {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
    try {
        const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
        await context.addInitScript(() => {
            localStorage.setItem('consultation-pwa-dismissed-until', String(Date.now() + 86400000));
            localStorage.setItem('consultation-pwa-admin-dismissed-until', String(Date.now() + 86400000));
            window.pushPermissionCalls = 0;
            const NotificationMock = function () {};
            NotificationMock.permission = 'granted';
            NotificationMock.requestPermission = async () => { window.pushPermissionCalls++; return 'granted'; };
            window.Notification = NotificationMock;
            window.PushManager = function () {};
            let subscription = null;
            const worker = { pushManager: {
                getSubscription: async () => subscription,
                subscribe: async options => {
                    window.pushSubscribeCalls = (window.pushSubscribeCalls || 0) + 1;
                    subscription = { endpoint: 'https://fcm.googleapis.com/fcm/send/fictional-browser-test', options,
                        toJSON: () => ({ endpoint: subscription.endpoint, keys: { p256dh: 'fictional-test-key', auth: 'fictional-test-auth' } }),
                        unsubscribe: async () => { subscription = null; return true; },
                    };
                    return subscription;
                },
            } };
            navigator.serviceWorker.register = async () => worker;
            Object.defineProperty(navigator.serviceWorker, 'ready', { configurable: true, get: () => Promise.resolve(worker) });
        });
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        let subscribed = false;
        let tests = 0;
        await page.route('**/notifications/**', async route => {
            const path = new URL(route.request().url()).pathname;
            if (path.endsWith('/status')) return route.fulfill({ json: { subscribed } });
            if (path.endsWith('/subscriptions')) {
                subscribed = route.request().method() === 'POST';
                return route.fulfill({ json: { subscribed } });
            }
            if (path.endsWith('/test')) { tests++; return route.fulfill({ json: { sent: true, message: 'Test accepted by the push service.' } }); }
            return route.continue();
        });
        const configuredPage = async route => {
            const response = await route.fetch();
            const html = (await response.text()).replace(/(<script type="application\/json" data-push-config>)(.*?)(<\/script>)/s, (_, start, json, end) => {
                const config = JSON.parse(json);
                return start + JSON.stringify({ ...config, enabled: true, publicKey }) + end;
            }).replace(/data-tour-required="true"/g, 'data-tour-required="false"');
            await route.fulfill({ response, body: html });
        };
        await page.route('**/notifications/settings', configuredPage);
        await page.route('**/admin/settings', configuredPage);
        await page.goto(`${base}/portal/login`);
        await page.locator('[name="login"]').fill('990000000001');
        await page.locator('[name="password"]').fill('DemoConsult!2026');
        await page.getByRole('button', { name: 'Sign in', exact: true }).click();
        await page.waitForURL(url => !url.pathname.endsWith('/login'));
        await page.goto(`${base}/notifications/settings`);
        const enable = page.getByRole('button', { name: 'Enable notifications', exact: true });
        await enable.waitFor();
        await page.waitForFunction(() => !document.querySelector('[data-push-enable]').disabled);
        assert.equal(await page.evaluate(() => window.pushPermissionCalls), 0, 'Never auto-prompt for notification permission');
        await enable.click();
        await page.getByRole('status').getByText('Notifications are enabled. Send a test', { exact: false }).waitFor();
        assert.equal(await page.evaluate(() => window.pushPermissionCalls), 1);
        assert.equal(await page.evaluate(() => window.pushSubscribeCalls), 1);
        await page.getByRole('button', { name: 'Send test notification', exact: true }).click();
        await page.getByRole('status').getByText('Test accepted by the push service.', { exact: true }).waitFor();
        assert.equal(tests, 1);
        for (const width of [320, 390, 768, 1440]) {
            await page.setViewportSize({ width, height: 844 });
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
        }
        await page.getByRole('button', { name: 'Disable on this device', exact: true }).click();
        await page.getByRole('status').getByText('Notifications are disabled on this device.', { exact: true }).waitFor();
        assert.equal(subscribed, false);
        assert.deepEqual(errors, []);
        console.log('PASS push UI: explicit permission only, subscribe, own-device test, disable, safe mocked delivery, and 320–1440px layouts');
        await page.evaluate(() => fetch('/consultations/logout', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } }));
        await page.goto(`${base}/admin/login`);
        await page.locator('[name="email"]').fill('demo.admin@example.com');
        await page.locator('[name="password"]').fill('DemoConsult!2026');
        await page.getByRole('button', { name: 'Sign in', exact: true }).click();
        await page.waitForURL(url => url.pathname === '/admin' || url.pathname === '/admin/');
        assert.equal(await page.locator('[data-push-test]').count(), 0, 'Push controls must not appear on the dashboard');
        await page.locator('[data-notification-bell]').click();
        await page.waitForURL('**/notifications');
        assert.equal(await page.locator('[data-push-test]').count(), 0, 'Inbox lists updates; device controls live in settings');
        await page.goto(`${base}/admin/settings`);
        await page.getByRole('heading', { name: 'Device notifications', exact: true }).waitFor();
        await page.waitForFunction(() => !document.querySelector('[data-push-enable]').disabled, null, { timeout: 5000 }).catch(async () => {
            throw new Error(JSON.stringify(await page.evaluate(() => ({ path: location.pathname, status: document.querySelector('[data-push-status]')?.textContent, enabled: JSON.parse(document.querySelector('[data-push-config]').textContent).enabled }))));
        });
        await page.getByRole('button', { name: 'Enable notifications', exact: true }).click();
        await page.waitForFunction(() => !document.querySelector('[data-push-test]').disabled);
        await page.getByRole('button', { name: 'Send test notification', exact: true }).click();
        await page.locator('[data-push-status]').getByText('Test accepted by the push service.', { exact: true }).waitFor();
        assert.equal(tests, 2);
        for (const width of [320, 390, 768, 1440]) {
            await page.setViewportSize({ width, height: 844 });
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `Settings overflow at ${width}px`);
            assert.equal(await page.getByRole('navigation', { name: 'Settings sections' }).getByRole('link').count(), 3);
        }
        assert.deepEqual(errors, []);
        console.log('PASS admin: dashboard controls removed, bell opens inbox, organized settings have three sections and current-device test controls');
        await context.close();
    } finally { await browser.close(); }
}
run().catch(error => { console.error(error); process.exitCode = 1; });
