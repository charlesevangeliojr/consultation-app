// Run against a local app using an existing administrator account.
// APP_TEST_URL, ADMIN_EMAIL, ADMIN_PASSWORD, PLAYWRIGHT_MODULE and CHROME_PATH are optional.
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.APP_TEST_URL || 'http://127.0.0.1:8092';

async function run() {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
    const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    try {
        await page.goto(`${base}/admin/login`);
        await page.locator('[name="email"]').fill(process.env.ADMIN_EMAIL || 'admin@admin.com');
        await page.locator('[name="password"]').fill(process.env.ADMIN_PASSWORD || '*');
        await Promise.all([page.waitForURL(url => !url.pathname.endsWith('/login')), page.getByRole('button', { name: 'Sign in', exact: true }).click()]);
        await page.waitForFunction(() => getComputedStyle(document.body).backgroundColor === 'rgb(2, 6, 23)');

        for (const path of ['/admin', '/admin/consultations', '/admin/accounts', '/admin/roles', '/admin/school', '/admin/settings', '/admin/profile']) {
            await page.goto(`${base}${path}`);
            for (const width of [320, 375, 390, 640, 768, 1024, 1440]) {
                await page.setViewportSize({ width, height: 844 });
                const size = await page.evaluate(() => ({ width: document.documentElement.scrollWidth, viewport: innerWidth }));
                assert.ok(size.width <= size.viewport + 1, `${path} overflows at ${width}px: ${JSON.stringify(size)}`);
                const trigger = page.getByRole('button', { name: 'Open admin menu' });
                const sidebar = page.locator('.admin-sidebar');
                if (width >= 1024) {
                    assert.ok(await sidebar.isVisible());
                    assert.equal(await trigger.isVisible(), false);
                    const box = await sidebar.boundingBox();
                    assert.equal(box.x, 0);
                    assert.equal(box.height, 844);
                    continue;
                }
                assert.equal(await sidebar.isVisible(), false);
                assert.ok(await trigger.isVisible());
                await trigger.click();
                const dialog = page.getByRole('dialog', { name: 'Admin menu', exact: true });
                await dialog.waitFor();
                const bounds = await dialog.boundingBox();
                assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width);
                assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 845);
                assert.equal(await trigger.getAttribute('aria-expanded'), 'true');
                assert.equal(await dialog.getByRole('navigation', { name: 'Admin menu navigation', exact: true }).getByRole('link').count(), 6);
                const targets = await dialog.locator('a.admin-navigation-link, button').evaluateAll(elements => elements.map(element => element.getBoundingClientRect().height));
                assert.ok(targets.every(height => height >= 44), 'Menu touch targets must be at least 44px tall');
                await page.keyboard.press('Escape');
                await dialog.waitFor({ state: 'hidden' });
                assert.equal(await trigger.getAttribute('aria-expanded'), 'false');
                assert.ok(await trigger.evaluate(element => element === document.activeElement), 'Escape must return focus to menu trigger');
                const dock = page.getByRole('navigation', { name: 'Admin mobile navigation', exact: true });
                const dockBounds = await dock.boundingBox();
                assert.ok(dockBounds.x >= 0 && dockBounds.x + dockBounds.width <= width);
                assert.equal(await dock.locator('a[aria-current]').count(), path === '/admin/profile' ? 0 : 1);
            }
            console.log(`PASS ${path}: 320–1440px, layout fit, navigation, touch targets and Escape focus`);
        }

        await page.setViewportSize({ width: 390, height: 844 });
        const trigger = page.getByRole('button', { name: 'Open admin menu' });
        await trigger.click();
        await page.getByRole('navigation', { name: 'Admin menu navigation', exact: true }).getByRole('link', { name: 'Roles & permissions', exact: true }).click();
        await page.waitForURL('**/admin/roles');
        assert.equal(await page.locator('[data-admin-menu]').evaluate(dialog => dialog.open), false);
        await trigger.click();
        await page.mouse.click(380, 200);
        await page.waitForFunction(() => !document.querySelector('[data-admin-menu]').open);
        await trigger.click();
        await page.setViewportSize({ width: 1280, height: 844 });
        await page.waitForFunction(() => !document.querySelector('[data-admin-menu]').open);
        assert.equal(await page.locator('html').evaluate(element => element.classList.contains('admin-menu-open')), false);

        await page.setViewportSize({ width: 667, height: 375 });
        await trigger.click();
        const navigation = page.getByRole('navigation', { name: 'Admin menu navigation', exact: true });
        await navigation.getByRole('link', { name: 'School settings', exact: true }).scrollIntoViewIfNeeded();
        const last = await navigation.getByRole('link', { name: 'School settings', exact: true }).boundingBox();
        assert.ok(last.y >= 0 && last.y + last.height <= 375, 'Short landscape menu must scroll to all links');
        await page.keyboard.press('Escape');
        console.log('PASS drawer navigation with Turbo, backdrop dismissal, desktop resize, and short landscape scrolling');
        assert.deepEqual(errors, [], 'No browser JavaScript errors');
    } finally {
        await browser.close();
    }
}

run().catch(error => { console.error(error); process.exitCode = 1; });
