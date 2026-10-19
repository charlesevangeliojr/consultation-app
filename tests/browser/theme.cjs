// Use only an isolated database with the fictional demo accounts.
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.APP_TEST_URL || 'http://127.0.0.1:8096';

async function run() {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
    try {
        const context = await browser.newContext({ colorScheme: 'light', viewport: { width: 390, height: 844 } });
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        for (const path of ['/', '/portal/login', '/admin/login', '/register', '/register?role=student', '/register?role=teacher']) {
            await page.goto(`${base}${path}`);
            await page.waitForFunction(() => document.documentElement.dataset.theme === 'light');
            assert.equal(await page.locator('[data-theme-toggle]').count(), 1);
            for (const width of [320, 390, 768, 1024, 1440]) {
                await page.setViewportSize({ width, height: 844 });
                const toggle = await page.locator('[data-theme-toggle]').boundingBox();
                assert.ok(toggle.y >= 0 && toggle.y + toggle.height <= 100, `${path}: theme control must be at the top`);
                assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${path}: navbar overflows at ${width}px`);
            }
            assert.equal(await page.locator('body').evaluate(element => getComputedStyle(element).backgroundColor), 'rgb(248, 250, 252)');
            await page.getByRole('button', { name: 'Switch to dark mode' }).click();
            assert.equal(await page.locator('html').getAttribute('data-theme'), 'dark');
            await page.reload();
            assert.equal(await page.locator('html').getAttribute('data-theme'), 'dark');
            await page.getByRole('button', { name: 'Switch to light mode' }).click();
        }
        console.log('PASS public themes: landing, both logins and registration; OS preference, toggle and reload persistence');

        for (const [role, email, login, paths] of [
            ['admin', 'demo.admin@example.com', '/admin/login', ['/admin', '/admin/accounts', '/admin/accounts/create', '/admin/roles', '/admin/school', '/admin/settings', '/admin/profile', '/admin/consultations']],
            ['teacher', 'demo.teacher1@example.com', '/portal/login', ['/consultations/teacher', '/consultations/teacher/requests', '/consultations/teacher/availability', '/consultations/teacher/settings', '/consultations/teacher/profile']],
            ['student', '990000000001', '/portal/login', ['/consultations/dashboard', '/consultations/browse', '/consultations/student/profile']],
        ]) {
            await page.goto(`${base}${login}`);
            await page.locator(role === 'admin' ? '[name="email"]' : '[name="login"]').fill(email);
            await page.locator('[name="password"]').fill('DemoConsult!2026');
            await page.getByRole('button', { name: 'Sign in', exact: true }).click();
            await page.waitForURL(url => !url.pathname.endsWith('/login'));
            for (const path of paths) {
                await page.goto(`${base}${path}`);
                for (const theme of ['light', 'dark']) {
                    for (const width of [320, 390, 768, 1440]) {
                        await page.setViewportSize({ width, height: 844 });
                        const toggle = page.locator('header.mobile-topbar [data-theme-toggle]');
                        assert.equal(await page.locator('[data-theme-toggle]').count(), 1, 'Theme switch must appear once, in the navbar');
                        if ((await page.locator('html').getAttribute('data-theme')) !== theme) await toggle.click();
                        assert.equal(await page.locator('html').getAttribute('data-theme'), theme);
                        assert.ok(await toggle.isVisible());
                        const toggleBox = await toggle.boundingBox();
                        assert.ok(toggleBox.height >= 44);
                        assert.ok(toggleBox.y >= 0 && toggleBox.y + toggleBox.height <= 100, 'Theme switch must be in the top navbar');
                        const metrics = await page.evaluate(() => ({
                            background: getComputedStyle(document.body).backgroundColor,
                            color: getComputedStyle(document.body).color,
                            overflow: document.documentElement.scrollWidth > innerWidth + 1,
                            inputs: [...document.querySelectorAll('input:not([type="hidden"]), select, textarea')].filter(element => element.getClientRects().length).map(element => getComputedStyle(element).colorScheme),
                        }));
                        assert.equal(metrics.overflow, false, `${role} ${path} ${theme} ${width}px overflows`);
                        assert.equal(metrics.background, theme === 'light' ? 'rgb(248, 250, 252)' : 'rgb(2, 6, 23)');
                        if (theme === 'light') {
                            assert.equal(metrics.color, 'rgb(15, 23, 42)');
                            for (const scheme of metrics.inputs) assert.equal(scheme, 'light');
                        }
                        if (process.env.THEME_SCREENSHOT_DIR && path === paths[0] && (width === 390 || width === 1440)) {
                            await page.screenshot({ path: `${process.env.THEME_SCREENSHOT_DIR}/${role}-${theme}-${width}.png`, fullPage: true });
                        }
                    }
                }
                await page.reload();
                assert.equal(await page.locator('html').getAttribute('data-theme'), 'dark');
            }
            // Use Turbo links to verify the document theme survives body swaps.
            await page.setViewportSize({ width: 390, height: 844 });
            const mobile = page.locator('[data-mobile-account-menu]');
            await page.getByRole('button', { name: 'Switch to light mode' }).click();
            await page.locator('[data-mobile-navigation] a').first().click();
            await page.waitForFunction(() => !document.documentElement.hasAttribute('aria-busy'));
            assert.equal(await page.locator('html').getAttribute('data-theme'), 'light');
            await mobile.locator('summary').click();
            await mobile.getByRole('button', { name: 'Sign out', exact: true }).click();
            await page.waitForURL('**/login');
            assert.equal(await page.locator('html').getAttribute('data-theme'), 'light');
            console.log(`PASS ${role}: single top-navbar theme switch, both themes, every workspace page, controls and no overflow at 320–1440px; Turbo and logout persistence`);
        }
        // Browser storage synchronization between tabs.
        const other = await context.newPage();
        await other.goto(`${base}/portal/login`);
        await page.getByRole('button', { name: 'Switch to dark mode' }).click();
        await other.waitForFunction(() => document.documentElement.dataset.theme === 'dark');
        assert.deepEqual(errors, []);
        console.log('PASS cross-tab synchronization; no JavaScript errors');
        if (process.env.THEME_SCREENSHOT_DIR) {
            await page.screenshot({ path: `${process.env.THEME_SCREENSHOT_DIR}/dark-login.png`, fullPage: true });
            await page.getByRole('button', { name: 'Switch to light mode' }).click();
            await page.screenshot({ path: `${process.env.THEME_SCREENSHOT_DIR}/light-login.png`, fullPage: true });
        }
        await context.close();
    } finally { await browser.close(); }
}
run().catch(error => { console.error(error); process.exitCode = 1; });
