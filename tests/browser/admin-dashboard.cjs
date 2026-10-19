const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.APP_TEST_URL || 'http://127.0.0.1:8096';

async function run() {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
    try {
        const page = await browser.newPage({ viewport: { width: 390, height: 844 }, colorScheme: 'light' });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(`${base}/admin/login`);
        await page.locator('[name="email"]').fill(process.env.ADMIN_EMAIL || 'demo.admin@example.com');
        await page.locator('[name="password"]').fill(process.env.ADMIN_PASSWORD || 'DemoConsult!2026');
        await page.getByRole('button', { name: 'Sign in', exact: true }).click();
        await page.waitForURL(url => url.pathname === '/admin');
        await page.locator('#dashboard-month').fill('2026-10');
        await page.getByRole('button', { name: 'Apply', exact: true }).click();
        await page.waitForURL('**/admin?month=2026-10');
        for (const theme of ['light', 'dark']) {
            if ((await page.locator('html').getAttribute('data-theme')) !== theme) await page.locator('[data-theme-toggle]').click();
            for (const width of [320, 390, 768, 1024, 1440, 1920]) {
                await page.setViewportSize({ width, height: 900 });
                assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `${theme} dashboard overflows at ${width}px`);
                assert.equal(await page.locator('.dashboard-bar-row').count(), 6);
                assert.equal(await page.locator('.dashboard-status-list > div').count(), 5);
                assert.equal(await page.locator('.dashboard-stats > div').count(), 4);
                assert.ok(await page.locator('.dashboard-donut').isVisible());
                const dimensions = await page.locator('.dashboard-donut svg').boundingBox();
                assert.ok(dimensions.width > 0 && dimensions.height > 0 && dimensions.width <= width);
                if (process.env.DASHBOARD_SCREENSHOT_DIR && [390, 1440].includes(width)) {
                    await page.screenshot({ path: `${process.env.DASHBOARD_SCREENSHOT_DIR}/dashboard-${theme}-${width}.png`, fullPage: true });
                }
            }
        }
        await page.setViewportSize({ width: 390, height: 844 });
        await page.getByText('View monthly data', { exact: true }).click();
        assert.equal(await page.locator('.dashboard-data tbody tr').count(), 6);
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'Data table must scroll inside its card');
        await page.locator('#dashboard-month').fill('2027-01');
        await page.getByRole('button', { name: 'Apply', exact: true }).click();
        await page.waitForURL('**/admin?month=2027-01');
        await page.getByText('Six months ending Jan 2027. Includes all booking statuses.', { exact: true }).waitFor();
        assert.equal(await page.locator('.dashboard-bar-label').first().textContent(), 'Aug 2026');
        assert.deepEqual(errors, []);
        console.log('PASS dashboard charts: both themes, 320–1920px, bar/donut graphs, summary cards, accessible data table, month filter and year boundaries');
    } finally { await browser.close(); }
}
run().catch(error => { console.error(error); process.exitCode = 1; });
