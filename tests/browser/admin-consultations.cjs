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
        // Exercise Turbo entry from another admin page.
        await page.locator('[data-mobile-navigation]').getByRole('link', { name: 'Consultations' }).click();
        await page.waitForURL('**/admin/consultations');
        await page.goto(`${base}/admin/consultations?month=2026-10&day=2026-10-16`);
        const row = page.locator('[data-booking-open]').first();
        assert.ok(await row.count(), 'Use the isolated demo database with booking records');
        assert.equal(await page.locator('.booking-records-table thead th').count(), 2);
        for (const theme of ['light', 'dark']) {
            if ((await page.locator('html').getAttribute('data-theme')) !== theme) await page.locator('[data-theme-toggle]').click();
            for (const width of [320, 390, 768, 1024, 1440]) {
                await page.setViewportSize({ width, height: 844 });
                assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `Booking list overflows at ${width}px`);
                assert.equal(await page.locator('[data-monitor-date]').count(), 31);
                assert.equal(await page.locator('.admin-monitor-selected').getAttribute('data-monitor-date'), '2026-10-16');
                await row.locator('td').last().click();
                const dialog = page.locator('[data-booking-dialog][open]');
                await dialog.waitFor();
                const size = await dialog.boundingBox();
                assert.ok(size.x >= 0 && size.x + size.width <= width);
                assert.ok(size.y >= 0 && size.y + size.height <= 844);
                assert.ok(await dialog.evaluate(element => element.scrollWidth <= element.clientWidth + 1));
                await dialog.getByText('Consultation reason', { exact: true }).waitFor();
                await dialog.getByText('Student', { exact: true }).waitFor();
                await page.keyboard.press('Escape');
                assert.equal(await page.locator('[data-booking-dialog][open]').count(), 0);
                assert.ok(await row.locator('button').evaluate(element => element === document.activeElement));
            }
        }
        await page.setViewportSize({ width: 390, height: 440 });
        await row.locator('button').focus();
        await page.keyboard.press('Enter');
        await page.locator('[data-booking-dialog][open]').waitFor();
        await page.locator('[data-booking-dialog][open]').getByRole('button', { name: 'Close', exact: true }).click();
        await row.locator('button').focus();
        await page.keyboard.press('Space');
        await page.locator('[data-booking-dialog][open]').waitFor();
        await page.mouse.click(2, 2);
        assert.equal(await page.locator('[data-booking-dialog][open]').count(), 0);
        await page.waitForFunction(() => !document.documentElement.classList.contains('booking-detail-open'));
        await page.setViewportSize({ width: 390, height: 844 });
        await page.locator('[data-monitor-date="2026-10-17"]').click();
        await page.waitForURL('**/admin/consultations?month=2026-10&day=2026-10-17');
        assert.equal(await page.locator('.admin-monitor-selected').getAttribute('data-monitor-date'), '2026-10-17');
        await page.getByRole('link', { name: 'Next month', exact: true }).click();
        await page.waitForURL('**/admin/consultations?month=2026-11');
        assert.equal(await page.locator('[data-monitor-date]').count(), 30);
        await page.getByRole('link', { name: 'Today', exact: true }).click();
        await page.waitForURL(url => url.searchParams.get('day') !== null);
        assert.deepEqual(errors, []);
        console.log('PASS consultation calendar: date selection, previous/next month and Today navigation; two-column daily records and modal details in both themes at 320–1440px');
    } finally { await browser.close(); }
}
run().catch(error => { console.error(error); process.exitCode = 1; });
