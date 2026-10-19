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
        await banner.locator('[data-pwa-install-button]').click();
        const dialog = page.locator('[data-pwa-instructions]');
        await dialog.waitFor();
        await dialog.getByText('Open this site in Chrome or Edge.', { exact: false }).waitFor();
        await page.keyboard.press('Escape');
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
        await ipad.locator('[data-pwa-install-button]').click();
        await ipad.locator('[data-pwa-instructions]').getByText('In Safari, tap Share,', { exact: false }).waitFor();
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
        console.log('PASS installed mode: startup splash appears then clears; no reinstall prompt');

        const desktop = await browser.newPage({ hasTouch: false });
        await desktop.goto(`${base}/portal/login`);
        assert.equal(await desktop.locator('[data-pwa-install]').isVisible(), false);
        assert.deepEqual(errors, []);
        console.log('PASS desktop: no mobile install suggestion; no JavaScript errors');
        await mobile.close();
        await ios.close();
        await standalone.close();
    } finally { await browser.close(); }
}
run().catch(error => { console.error(error); process.exitCode = 1; });
