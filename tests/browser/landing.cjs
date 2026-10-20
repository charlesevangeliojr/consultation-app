// Public pages only. Optional APP_TEST_URL, PLAYWRIGHT_MODULE, CHROME_PATH and LANDING_SCREENSHOT_DIR.
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.APP_TEST_URL || 'http://127.0.0.1:8096';

async function run() {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
    try {
        const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
        await page.addInitScript(() => localStorage.setItem('consultation-pwa-dismissed-until', String(Date.now() + 86400000)));
        const errors = [];
        let staleScriptRequests = 0;
        // Simulate an old deployment/browser-cache URL containing menu-only JS.
        // The versioned script must bypass it and load the role-switcher handler.
        await page.route(/\/js\/landing\.js$/, route => {
            staleScriptRequests++;
            return route.fulfill({ contentType: 'application/javascript', body: 'window.consultationLandingInitialized = true;' });
        });
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(base);
        await page.getByRole('heading', { name: 'Big questions. Let’s make time.' }).waitFor();
        for (const theme of ['light', 'dark']) {
            await page.emulateMedia({ colorScheme: theme });
            await page.waitForFunction(value => document.documentElement.dataset.theme === value, theme);
            for (const width of [320, 390, 768, 1024, 1440]) {
                await page.setViewportSize({ width, height: 844 });
                assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `Landing overflows at ${width}px in ${theme}`);
                const teacherTab = page.getByRole('tab', { name: 'I’m a teacher', exact: true });
                await teacherTab.click();
                await page.getByRole('heading', { name: 'Get ready to offer support.', exact: true }).waitFor();
                assert.equal(await teacherTab.getAttribute('aria-selected'), 'true');
                assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `Teacher hero overflows at ${width}px`);
                await teacherTab.focus();
                await page.keyboard.press('ArrowLeft');
                await page.getByRole('heading', { name: 'Find the right teacher.', exact: true }).waitFor();
                if (width < 1024) {
                    assert.equal(await page.getByRole('navigation', { name: 'Main navigation', exact: true }).getByRole('link', { name: 'Register', exact: true }).count(), 0, 'Register belongs in the hero, not the navbar');
                    const button = page.getByRole('button', { name: 'Open navigation menu', exact: true });
                    await button.click();
                    await page.locator('#landing-mobile-menu').getByRole('link', { name: 'How to use', exact: true }).click();
                    assert.equal(await page.locator('#landing-mobile-menu').isVisible(), false);
                    await button.click();
                    await page.keyboard.press('Escape');
                    assert.equal(await page.locator('#landing-mobile-menu').isVisible(), false);
                }
            }
            if (process.env.LANDING_SCREENSHOT_DIR) {
                await page.setViewportSize({ width: 390, height: 844 });
                await page.goto(base);
                await page.screenshot({ path: `${process.env.LANDING_SCREENSHOT_DIR}/landing-${theme}.png`, fullPage: true });
            }
        }
        const question = page.locator('summary', { hasText: 'What do the booking statuses mean?' });
        assert.ok(await page.locator('#home').getByRole('link', { name: 'Register →', exact: true }).isVisible());
        await question.click();
        assert.equal(await question.evaluate(element => element.parentElement.open), true);
        assert.equal(await page.getByRole('link', { name: 'Register as a student', exact: false }).getAttribute('href'), `${base}/register?role=student`);
        await page.getByRole('tab', { name: 'I’m a teacher', exact: true }).click();
        assert.equal(await page.getByRole('link', { name: 'Register as a teacher', exact: false }).getAttribute('href'), `${base}/register?role=teacher`);
        const invalidAnchors = await page.locator('a[href^="#"]').evaluateAll(links => links.map(link => link.getAttribute('href')).filter(href => !document.getElementById(href.slice(1))));
        assert.deepEqual(invalidAnchors, []);
        assert.deepEqual(errors, []);
        assert.equal(staleScriptRequests, 0, 'Landing must not reuse the stale, unversioned script URL');
        assert.ok(await page.locator('script[src*="/js/landing.js?v="]').count());
        console.log('PASS landing: accurate role guides, public preview, working section links, registration CTAs, FAQ, mobile menu/Escape, and 320–1440px layout in both themes');
    } finally { await browser.close(); }
}
run().catch(error => { console.error(error); process.exitCode = 1; });
