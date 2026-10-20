// Uses fictional accounts in an isolated test database; does not seed any records.
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.APP_TEST_URL || 'http://127.0.0.1:8096';

async function run() {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
    try {
        for (const role of ['teacher', 'student']) {
            const context = await browser.newContext({ viewport: { width: 390, height: 844 }, hasTouch: true });
            await context.addInitScript(() => {
                if (location.pathname.endsWith('/login')) localStorage.setItem('consultation-pwa-dismissed-until', String(Date.now() + 86400000));
            });
            const page = await context.newPage();
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.goto(`${base}/portal/login`);
            await page.locator('[name="login"]').fill(role === 'teacher' ? 'demo.teacher1@example.com' : '990000000001');
            await page.locator('[name="password"]').fill('DemoConsult!2026');
            await page.evaluate(() => localStorage.removeItem('consultation-pwa-dismissed-until'));
            await page.getByRole('button', { name: 'Sign in', exact: true }).click();
            await page.waitForURL(url => !url.pathname.endsWith('/login'));
            const tour = page.locator('[data-portal-tour]');
            await tour.waitFor({ state: 'visible' });
            assert.equal(await page.locator('[data-pwa-install]').isVisible(), false, 'First-login tour must take priority over install modal');
            assert.equal(await tour.evaluate(element => element.matches(':modal')), true);
            const count = role === 'teacher' ? 5 : 4;
            for (let step = 1; step <= count; step++) {
                await tour.locator('[data-portal-tour-progress]').getByText(`Step ${step} of ${count}`, { exact: true }).waitFor();
                if (step === 1) assert.equal(await tour.getByRole('button', { name: 'Back', exact: true }).isDisabled(), true);
                if (step === count) {
                    await Promise.all([
                        page.waitForResponse(response => response.url().endsWith('/tour/complete') && response.status() === 204),
                        tour.getByRole('button', { name: 'Finish tour', exact: true }).click(),
                    ]);
                } else await tour.getByRole('button', { name: 'Next', exact: true }).click();
            }
            assert.equal(await tour.isVisible(), false);
            await page.locator('[data-pwa-install]').waitFor();
            await page.getByRole('button', { name: 'Not now', exact: true }).click();
            await page.reload();
            await page.waitForTimeout(700);
            assert.equal(await tour.isVisible(), false, 'Finished tour must not auto-reopen');
            await page.getByRole('button', { name: 'Start navigation tour' }).click();
            await tour.getByRole('button', { name: 'Next', exact: true }).click();
            await tour.getByRole('button', { name: 'Back', exact: true }).click();
            for (const theme of ['light', 'dark']) {
                await page.emulateMedia({ colorScheme: theme });
                for (const width of [320, 390, 768, 1440]) {
                    await page.setViewportSize({ width, height: 844 });
                    const bounds = await tour.boundingBox();
                    assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1);
                    assert.ok(await tour.evaluate(element => element.scrollWidth <= element.clientWidth + 1), `${role} tour overflows at ${width}px`);
                }
            }
            await page.keyboard.press('Escape');
            assert.equal(await tour.isVisible(), false);
            assert.equal(await page.evaluate(() => document.documentElement.classList.contains('portal-tour-open')), false);
            await page.getByRole('button', { name: 'Start navigation tour' }).click();
            await tour.locator('[data-portal-tour-page]').click();
            await page.waitForURL(url => role === 'teacher' ? url.pathname.endsWith('/teacher/profile') : url.pathname.endsWith('/dashboard'));
            assert.equal(await tour.isVisible(), false);
            assert.deepEqual(errors, []);
            console.log(`PASS ${role}: automatic onboarding, all ${count} steps, Next/Back/Finish, replay, Escape, page links, persistence, and 320–1440px light/dark layout`);
            await context.close();
        }
    } finally { await browser.close(); }
}
run().catch(error => { console.error(error); process.exitCode = 1; });
