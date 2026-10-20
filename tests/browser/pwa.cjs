// Public-page checks only; no production records or private pages are cached.
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.APP_TEST_URL || 'http://127.0.0.1:8096';
const android = 'Mozilla/5.0 (Linux; Android 14; Demo Phone) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36';

async function run() {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
    try {
        const mobile = await browser.newContext({ userAgent: android, isMobile: true, hasTouch: true, viewport: { width: 390, height: 844 } });
        const page = await mobile.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`${base}/portal/login`);
        const banner = page.locator('[data-pwa-install]');
        await banner.waitFor();
        for (const width of [320, 390, 768, 1024]) {
            await page.setViewportSize({ width, height: 844 });
            const bounds = await banner.boundingBox();
            assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1);
            assert.ok(Math.abs(bounds.x + bounds.width / 2 - width / 2) <= 2, 'Install popup must be centered horizontally');
            assert.ok(Math.abs(bounds.y + bounds.height / 2 - 844 / 2) <= 2, 'Install popup must be centered vertically');
            assert.equal(await banner.evaluate(dialog => dialog.matches(':modal')), true, 'Install popup must block the background');
            assert.match(await banner.evaluate(dialog => getComputedStyle(dialog, '::backdrop').backgroundColor), /0\.75\)/, 'Install popup must have a dark backdrop');
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
        }
        await page.setViewportSize({ width: 390, height: 844 });
        await banner.getByText('Right on your home screen', { exact: true }).waitFor();
        assert.ok(await banner.getByRole('button', { name: 'Not now', exact: true }).isVisible());
        for (const theme of ['dark', 'light']) {
            await page.emulateMedia({ colorScheme: theme });
            await page.waitForFunction(value => document.documentElement.dataset.theme === value, theme);
            if (process.env.PWA_SCREENSHOT_DIR) await page.screenshot({ path: `${process.env.PWA_SCREENSHOT_DIR}/install-${theme}.png` });
        }
        assert.equal(await page.locator('[data-pwa-launch]').isVisible(), false, 'Normal browser visits must not show the PWA splash');
        await banner.getByRole('button', { name: 'Install app', exact: true }).click();
        const dialog = page.locator('[data-pwa-instructions]');
        await dialog.waitFor();
        await dialog.getByText('Open this site in Chrome or Edge.', { exact: false }).waitFor();
        assert.equal(await dialog.locator('[data-pwa-install-steps] li').count(), 3);
        assert.equal(await banner.isVisible(), false, 'Instructions must not stack on the install modal');
        await page.keyboard.press('Escape');
        // A rejected native prompt must show a usable fallback rather than silently fail.
        await page.evaluate(() => {
            const event = new Event('beforeinstallprompt', { cancelable: true });
            event.prompt = async () => { throw new Error('Installation unavailable'); };
            event.userChoice = Promise.resolve({ outcome: 'dismissed' });
            dispatchEvent(event);
        });
        await banner.getByRole('button', { name: 'Install app', exact: true }).click();
        await dialog.waitFor();
        assert.equal(await banner.isVisible(), false);
        await dialog.getByRole('button', { name: 'Got it', exact: true }).click();
        await page.evaluate(() => {
            const event = new Event('beforeinstallprompt', { cancelable: true });
            event.prompt = async () => {};
            event.userChoice = Promise.resolve({ outcome: 'dismissed' });
            dispatchEvent(event);
        });
        await banner.getByRole('button', { name: 'Install app', exact: true }).click();
        await page.waitForFunction(() => document.querySelector('[data-pwa-install]').hidden);
        await page.evaluate(() => localStorage.removeItem('consultation-pwa-dismissed-until'));
        await page.reload();
        await banner.waitFor();
        // Simulate the browser-only install event to test the custom trigger.
        await page.evaluate(() => {
            const event = new Event('beforeinstallprompt', { cancelable: true });
            event.prompt = async () => { window.promptCalled = true; };
            event.userChoice = Promise.resolve({ outcome: 'accepted' });
            dispatchEvent(event);
        });
        await banner.getByRole('button', { name: 'Install app', exact: true }).click();
        await page.waitForFunction(() => window.promptCalled && document.querySelector('[data-pwa-install]').hidden);
        await page.evaluate(async () => { await navigator.serviceWorker.ready; });
        await page.reload();
        await page.evaluate(async () => { await navigator.serviceWorker.ready; });
        // Cache must contain only the static offline page, never login or accounts.
        const cacheURLs = await page.evaluate(async () => {
            const keys = (await caches.keys()).filter(key => key.startsWith('consultation-pwa-'));
            return (await Promise.all(keys.map(async key => (await (await caches.open(key)).keys()).map(request => new URL(request.url).pathname)))).flat();
        });
        assert.deepEqual(cacheURLs, ['/pwa/offline.html']);
        await mobile.setOffline(true);
        await page.goto(`${base}/consultations/dashboard`);
        await page.getByRole('heading', { name: 'You’re offline' }).waitFor();
        await page.getByText('Private information is not stored for offline access.', { exact: false }).waitFor();
        await mobile.setOffline(false);
        console.log('PASS Android/tablet: responsive prompt, install-event trigger, manual guidance, network-only privacy and offline fallback');

        const ios = await browser.newContext({ userAgent: 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1', hasTouch: true, viewport: { width: 768, height: 1024 } });
        const ipad = await ios.newPage();
        await ipad.goto(`${base}/portal/login`);
        await ipad.getByRole('button', { name: 'Install app', exact: true }).click();
        await ipad.locator('[data-pwa-instructions]').getByText('In Safari, tap Share,', { exact: false }).waitFor();
        assert.equal(await ipad.locator('[data-pwa-install-steps] li').count(), 4);
        await ipad.locator('[data-pwa-install-steps]').getByText('Keep Open as Web App enabled', { exact: false }).waitFor();
        await ipad.getByRole('button', { name: 'Got it' }).click();
        await ipad.getByRole('button', { name: 'Dismiss install suggestion' }).click();
        await ipad.reload();
        assert.equal(await ipad.locator('[data-pwa-install]').isVisible(), false);
        await ipad.evaluate(() => localStorage.removeItem('consultation-pwa-dismissed-until'));
        await ipad.reload();
        await ipad.locator('[data-pwa-install]').waitFor();
        await ipad.mouse.click(2, 2);
        assert.equal(await ipad.locator('[data-pwa-install]').isVisible(), false, 'Clicking the backdrop must dismiss');
        await ipad.evaluate(() => localStorage.removeItem('consultation-pwa-dismissed-until'));
        await ipad.reload();
        await ipad.locator('[data-pwa-install]').waitFor();
        await ipad.keyboard.press('Escape');
        assert.equal(await ipad.locator('[data-pwa-install]').isVisible(), false, 'Escape must dismiss');
        assert.equal(await ipad.evaluate(() => document.documentElement.classList.contains('pwa-install-open')), false);
        console.log('PASS iOS: Share / Add to Home Screen guidance, centered modal, dark backdrop, button/backdrop/Escape dismissal and scroll unlock');

        const standalone = await browser.newContext({ userAgent: android, hasTouch: true });
        await standalone.addInitScript(() => {
            const native = window.matchMedia.bind(window);
            window.matchMedia = query => query === '(display-mode: standalone)' ? { matches: true, addEventListener() {} } : native(query);
            document.addEventListener('DOMContentLoaded', () => {
                window.splashWasVisible = getComputedStyle(document.querySelector('[data-pwa-launch]')).display !== 'none';
            });
        });
        const app = await standalone.newPage();
        await app.goto(`${base}/portal/login`);
        await app.waitForFunction(() => document.documentElement.dataset.pwaLoading === 'false');
        assert.equal(await app.evaluate(() => window.splashWasVisible), true);
        assert.equal(await app.locator('[data-pwa-launch]').isVisible(), false);
        assert.equal(await app.locator('[data-pwa-install]').isVisible(), false);
        assert.equal(await app.evaluate(() => localStorage.getItem('consultation-pwa-installed')), 'true', 'Opening standalone mode must remember installation');
        console.log('PASS installed mode: startup splash appears then clears; no reinstall prompt');

        const desktop = await browser.newPage({ hasTouch: false });
        await desktop.goto(`${base}/portal/login`);
        assert.equal(await desktop.locator('[data-pwa-install]').isVisible(), false);
        assert.deepEqual(errors, []);
        console.log('PASS desktop: no mobile install suggestion; no JavaScript errors');
        const separateApps = await browser.newContext({ userAgent: android, isMobile: true, hasTouch: true, viewport: { width: 390, height: 844 } });
        const adminPage = await separateApps.newPage();
        await adminPage.goto(`${base}/portal/login`);
        await adminPage.getByRole('button', { name: 'Not now', exact: true }).click();
        await adminPage.goto(`${base}/admin/login`);
        await adminPage.locator('[data-pwa-install]').waitFor();
        assert.match(await adminPage.locator('#pwa-install-title').textContent(), / Admin$/);
        assert.equal(await adminPage.locator('link[rel="manifest"]').getAttribute('href'), '/admin/manifest.webmanifest');
        await adminPage.getByRole('button', { name: 'Not now', exact: true }).click();
        assert.ok(await adminPage.evaluate(() => localStorage.getItem('consultation-pwa-admin-dismissed-until')));
        await adminPage.reload();
        assert.equal(await adminPage.locator('[data-pwa-install]').isVisible(), false);
        await separateApps.close();
        console.log('PASS admin: separate manifest, Admin install name, and independent dismissal preferences');
        const retry = await browser.newContext({ userAgent: android, isMobile: true, hasTouch: true, viewport: { width: 390, height: 844 } });
        await retry.addInitScript(() => localStorage.setItem('consultation-pwa-dismissed-until', String(Date.now() + 86400000)));
        const retryPage = await retry.newPage();
        await retryPage.goto(base);
        assert.equal(await retryPage.locator('[data-pwa-install]').isVisible(), false);
        await retryPage.evaluate(() => {
            const event = new Event('beforeinstallprompt', { cancelable: true });
            event.prompt = async () => { window.retryPromptCalled = true; };
            event.userChoice = Promise.resolve({ outcome: 'accepted' });
            dispatchEvent(event);
        });
        await retryPage.getByRole('button', { name: 'Install app', exact: true }).click();
        await retryPage.waitForFunction(() => window.retryPromptCalled);
        await retry.close();
        console.log('PASS install resilience: failed-prompt guidance, cancellation handling, numbered iOS steps, and landing-page retry after dismissal');
        const protectedHost = await browser.newContext();
        await protectedHost.addCookies([{ name: 'pwa-host-check', value: 'allowed', url: base }]);
        const protectedPage = await protectedHost.newPage();
        const cookieChecks = [];
        await protectedPage.route('**/manifest.webmanifest', async route => {
            const headers = await route.request().allHeaders();
            const allowed = (headers.cookie || '').includes('pwa-host-check=allowed');
            cookieChecks.push(allowed);
            if (allowed) await route.continue();
            else await route.fulfill({ contentType: 'text/html', body: '<html>Browser security check required</html>' });
        });
        await protectedPage.goto(`${base}/portal/login`);
        const protocol = await protectedPage.context().newCDPSession(protectedPage);
        const checkedManifest = await protocol.send('Page.getAppManifest');
        assert.equal(await protectedPage.locator('link[rel="manifest"]').getAttribute('crossorigin'), 'use-credentials');
        assert.ok(cookieChecks.length > 0 && cookieChecks.every(Boolean), 'Manifest requests must include same-origin hosting-security cookies');
        assert.deepEqual(checkedManifest.errors, []);
        assert.ok(JSON.parse(checkedManifest.data).name);
        await protectedHost.close();
        console.log('PASS shared-hosting install: real browser manifest requests include security cookies and parse without errors');
        const detected = await browser.newContext({ userAgent: android, hasTouch: true, viewport: { width: 390, height: 844 } });
        await detected.addInitScript(() => {
            navigator.getInstalledRelatedApps = async () => [{ platform: 'webapp', url: `${location.origin}/manifest.webmanifest`, id: `${location.origin}/` }];
        });
        const detectedPage = await detected.newPage();
        await detectedPage.goto(`${base}/portal/login`);
        await detectedPage.waitForFunction(() => localStorage.getItem('consultation-pwa-installed') === 'true');
        assert.equal(await detectedPage.locator('[data-pwa-install]').isVisible(), false);
        await detectedPage.goto(`${base}/admin/login`);
        await detectedPage.locator('[data-pwa-install]').waitFor();
        assert.equal(await detectedPage.evaluate(() => localStorage.getItem('consultation-pwa-admin-installed')), null, 'Portal installation must not hide the Admin install suggestion');
        await detected.close();
        const saved = await browser.newContext({ userAgent: android, hasTouch: true });
        const savedPage = await saved.newPage();
        await savedPage.goto(`${base}/portal/login`);
        await savedPage.locator('[data-pwa-install]').waitFor();
        await savedPage.evaluate(() => dispatchEvent(new Event('appinstalled')));
        await savedPage.reload();
        assert.equal(await savedPage.locator('[data-pwa-install]').isVisible(), false, 'Confirmed installations stay hidden after reload');
        await saved.close();
        const iosConfirmed = await browser.newContext({ userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1', hasTouch: true });
        const iosPage = await iosConfirmed.newPage();
        await iosPage.goto(`${base}/portal/login`);
        await iosPage.getByRole('button', { name: 'Install app', exact: true }).click();
        await iosPage.getByRole('button', { name: 'I’ve added the app', exact: true }).click();
        await iosPage.reload();
        assert.equal(await iosPage.locator('[data-pwa-install]').isVisible(), false);
        await iosConfirmed.close();
        console.log('PASS install detection: related-app detection, standalone persistence, appinstalled reload persistence, separate Admin state, and iOS confirmation');
        await mobile.close();
        await ios.close();
        await standalone.close();
    } finally { await browser.close(); }
}
run().catch(error => { console.error(error); process.exitCode = 1; });
