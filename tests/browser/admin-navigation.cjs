// APP_TEST_URL, ADMIN_EMAIL, ADMIN_PASSWORD, PLAYWRIGHT_MODULE and CHROME_PATH are optional.
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.APP_TEST_URL || 'http://127.0.0.1:8092';

async function run() {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
    const page = await browser.newPage({ colorScheme: 'dark', viewport: { width: 390, height: 844 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    try {
        await page.goto(`${base}/admin/login`);
        await page.locator('[name="email"]').fill(process.env.ADMIN_EMAIL || 'admin@admin.com');
        await page.locator('[name="password"]').fill(process.env.ADMIN_PASSWORD || '*');
        await page.getByRole('button', { name: 'Sign in', exact: true }).click();
        await page.waitForURL(url => !url.pathname.endsWith('/login'));
        for (const path of ['/admin', '/admin/consultations', '/admin/accounts', '/admin/accounts/create', '/admin/roles', '/admin/school', '/admin/settings', '/admin/profile']) {
            await page.goto(`${base}${path}`);
            assert.equal(await page.locator('[data-admin-menu-open], [data-admin-menu]').count(), 0, 'Mobile drawer and hamburger must be removed');
            for (const width of [320, 375, 390, 640, 768, 1024, 1440]) {
                await page.setViewportSize({ width, height: 844 });
                assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${path} overflows at ${width}px`);
                assert.equal(await page.locator('.admin-sidebar').isVisible(), width >= 1024);
                assert.equal(await page.locator('[data-mobile-navigation]').isVisible(), width < 1024);
                assert.ok(await page.locator('.admin-topbar [data-theme-toggle]').isVisible());
                if (width < 1024) {
                    assert.equal(await page.locator('[data-mobile-navigation] a').count(), 5);
                    const targets = await page.locator('[data-mobile-navigation] a').evaluateAll(links => links.map(link => link.getBoundingClientRect().height));
                    assert.ok(targets.every(height => height >= 44));
                }
            }
            console.log(`PASS ${path}: no mobile sidebar, desktop sidebar preserved, bottom navigation and topbar fit at 320–1440px`);
        }
        await page.setViewportSize({ width: 390, height: 844 });
        const navigation = page.locator('[data-mobile-navigation]');
        await navigation.getByRole('link', { name: 'Accounts', exact: true }).click();
        await page.waitForURL('**/admin/accounts');
        await page.getByRole('navigation', { name: 'People and access navigation' }).getByRole('link', { name: 'Roles & permissions' }).click();
        await page.waitForURL('**/admin/roles');
        await navigation.getByRole('link', { name: 'Grades & sections', exact: true }).click();
        await page.waitForURL('**/admin/school');
        const account = page.locator('[data-mobile-account-menu]');
        await account.locator('summary').click();
        await account.getByRole('link', { name: 'My profile', exact: true }).click();
        await page.waitForURL('**/admin/profile');
        await account.locator('summary').click();
        await account.getByRole('button', { name: 'Sign out', exact: true }).click();
        await page.waitForURL('**/admin/login');
        assert.deepEqual(errors, []);
        console.log('PASS mobile navigation: Accounts, role tabs, Grades & sections, profile and sign out remain accessible without a sidebar');
    } finally { await browser.close(); }
}
run().catch(error => { console.error(error); process.exitCode = 1; });
