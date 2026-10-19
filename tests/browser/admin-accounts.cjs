const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.APP_TEST_URL || 'http://127.0.0.1:8096';

async function run() {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
    try {
        const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`${base}/admin/login`);
        await page.locator('[name="email"]').fill(process.env.ADMIN_EMAIL || 'demo.admin@example.com');
        await page.locator('[name="password"]').fill(process.env.ADMIN_PASSWORD || 'DemoConsult!2026');
        await page.getByRole('button', { name: 'Sign in', exact: true }).click();
        await page.waitForURL(url => url.pathname === '/admin');
        await page.locator('[data-mobile-navigation]').getByRole('link', { name: 'Accounts', exact: true }).click();
        await page.waitForURL('**/admin/accounts');
        await page.locator('#account-search').fill('demo.student1@example.com');
        await page.getByRole('button', { name: 'Search', exact: true }).click();
        await page.waitForURL('**/admin/accounts?search=demo.student1%40example.com');
        const row = page.locator('[data-record-open]').first();
        assert.equal(await page.locator('[data-account-records] thead th').count(), 2);
        assert.equal(await page.locator('[data-record-open]').count(), 1);
        for (const theme of ['light', 'dark']) {
            if ((await page.locator('html').getAttribute('data-theme')) !== theme) await page.locator('[data-theme-toggle]').click();
            for (const width of [320, 390, 768, 1024, 1440]) {
                await page.setViewportSize({ width, height: 844 });
                assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
                await row.locator('td').last().click();
                const dialog = page.locator('[data-record-dialog][open]');
                await dialog.waitFor();
                const size = await dialog.boundingBox();
                assert.ok(size.x >= 0 && size.x + size.width <= width);
                assert.ok(await dialog.evaluate(element => element.scrollWidth <= element.clientWidth + 1));
                await dialog.getByText('990000000001', { exact: true }).waitFor();
                await page.keyboard.press('Escape');
                assert.ok(await row.locator('button').evaluate(element => element === document.activeElement));
            }
        }
        await page.setViewportSize({ width: 390, height: 440 });
        await row.locator('button').focus();
        await page.keyboard.press('Enter');
        const details = page.locator('[data-record-dialog][open]');
        await details.getByRole('button', { name: 'Delete', exact: true }).click();
        const confirmation = page.locator('[data-account-delete-dialog][open]');
        await confirmation.getByRole('heading', { name: 'Delete user?', exact: true }).waitFor();
        await confirmation.getByRole('button', { name: 'Cancel', exact: true }).click();
        assert.ok(await details.isVisible());
        await details.getByRole('link', { name: 'Edit account', exact: true }).click();
        await page.waitForURL('**/admin/accounts/*/edit');
        await page.locator('[data-mobile-navigation]').getByRole('link', { name: 'Accounts', exact: true }).click();
        await page.waitForURL('**/admin/accounts');
        assert.equal(await page.locator('[data-record-dialog][open]').count(), 0);
        await page.locator('[data-mobile-navigation]').getByRole('link', { name: 'Grades & sections', exact: true }).click();
        await page.waitForURL('**/admin/school');
        await page.getByRole('heading', { name: 'Grades & sections', exact: true, level: 2 }).waitFor();
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
        assert.deepEqual(errors, []);
        console.log('PASS accounts: two-column list, search, both themes at 320–1440px, details, keyboard/focus, delete cancellation without data loss, edit navigation, modal cleanup and Grades & sections label');
    } finally { await browser.close(); }
}
run().catch(error => { console.error(error); process.exitCode = 1; });
