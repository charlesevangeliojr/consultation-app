// Optional browser smoke test. Requires Playwright and a running local app with
// demo data. Set PLAYWRIGHT_MODULE to an external installation if needed.
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.APP_TEST_URL || 'http://127.0.0.1:8091';
const widths = [320, 375, 390, 640, 768, 1024, 1280, 1440, 1920, 2560];

async function run() {
    const browser = await chromium.launch({
        headless: true,
        ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}),
    });
    try {
        for (const role of ['teacher', 'student', 'admin']) {
            const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
            const page = await context.newPage();
            await page.goto(`${base}/${role === 'admin' ? 'admin' : 'portal'}/login`);
            await page.locator('input[name="email"]').fill(`demo.${role}${role === 'admin' ? '' : '1'}@example.com`);
            await page.locator('input[name="password"]').fill('DemoConsult!2026');
            const password = page.locator('input[name="password"]');
            const eye = page.locator('[data-login-password-toggle]');
            assert.equal(await password.getAttribute('type'), 'password', 'Login password must start hidden');
            await eye.click();
            assert.equal(await password.getAttribute('type'), 'text', 'Eye button must reveal the password');
            assert.equal(await eye.getAttribute('aria-label'), 'Hide password');
            assert.equal(await password.inputValue(), 'DemoConsult!2026', 'Toggling must preserve the password');
            assert.ok(new URL(page.url()).pathname.endsWith('/login'), 'Eye button must not submit the form');
            await eye.focus();
            await page.keyboard.press('Space');
            assert.equal(await password.getAttribute('type'), 'password', 'Eye button must support the keyboard');
            await Promise.all([
                page.waitForURL(url => !url.pathname.endsWith('/login')),
                page.getByRole('button', { name: 'Sign in', exact: true }).click(),
            ]);
            const navigation = page.locator('[data-mobile-navigation]');
            await navigation.waitFor({ state: 'attached' });
            // Wait for the CDN utility stylesheet to be applied, not just loaded.
            await page.waitForFunction(() => getComputedStyle(document.body).backgroundColor === 'rgb(2, 6, 23)');
            if (role === 'teacher') {
                const scheduledDate = await page.locator('[data-calendar-date]').evaluateAll(links => links.find(link => /, [1-9]\d* availability windows/.test(link.getAttribute('aria-label')))?.href);
                if (scheduledDate) await page.goto(scheduledDate);
                if (process.env.CALENDAR_SCREENSHOT_DIR) {
                    require('node:fs').mkdirSync(process.env.CALENDAR_SCREENSHOT_DIR, { recursive: true });
                    for (const width of [390, 1440]) {
                        await page.setViewportSize({ width, height: 844 });
                        await page.locator('#teacher-month-calendar').screenshot({ path: require('node:path').join(process.env.CALENDAR_SCREENSHOT_DIR, `teacher-calendar-${width}.png`) });
                    }
                    await page.setViewportSize({ width: 390, height: 844 });
                }
                // Stress long unbroken user content without changing the database.
                await page.evaluate(() => {
                    const name = document.querySelector('header span.text-slate-400');
                    if (name) name.textContent = 'LongTeacherName'.repeat(18);
                    document.querySelectorAll('.teacher-portal article p').forEach((paragraph, i) => {
                        if (i % 3 === 0) paragraph.textContent = 'LongConsultationContent'.repeat(20);
                    });
                });
            }
            for (const width of widths) {
                await page.setViewportSize({ width, height: 844 });
                await page.evaluate(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))));
                const metrics = await page.evaluate(() => {
                    const nav = document.querySelector('[data-mobile-navigation]');
                    const rect = nav.getBoundingClientRect();
                    return {
                        viewport: window.innerWidth,
                        pageWidth: document.documentElement.scrollWidth,
                        navVisible: getComputedStyle(nav).display !== 'none',
                        navBottom: rect.bottom,
                        navTop: rect.top,
                        bodyPadding: parseFloat(getComputedStyle(document.body).paddingBottom),
                        targets: [...nav.querySelectorAll('a')].map(link => ({ width: link.getBoundingClientRect().width, height: link.getBoundingClientRect().height })),
                        columns: document.querySelector('#teacher-month-calendar .grid-cols-7')
                            ? getComputedStyle(document.querySelector('#teacher-month-calendar .grid-cols-7')).gridTemplateColumns.split(' ').length : null,
                        calendar: (() => {
                            const calendar = document.getElementById('teacher-month-calendar');
                            if (!calendar) return null;
                            const day = calendar.querySelector('.calendar-day');
                            const grid = calendar.querySelector('.calendar-month-grid');
                            const agenda = calendar.querySelector('.calendar-agenda');
                            const pane = calendar.querySelector('.calendar-month-pane');
                            const style = getComputedStyle(calendar);
                            const containerWidth = calendar.clientWidth - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight);
                            return {
                                containerWidth,
                                dayHeight: day.getBoundingClientRect().height,
                                gridHeight: grid.getBoundingClientRect().height,
                                dotDisplay: getComputedStyle(day.querySelector('.calendar-event-dots')).display,
                                previewDisplay: getComputedStyle(day.querySelector('.calendar-desktop-events')).display,
                                selectedBorder: getComputedStyle(calendar.querySelector('[data-selected="true"]')).borderTopColor,
                                agendaTop: agenda.getBoundingClientRect().top,
                                agendaLeft: agenda.getBoundingClientRect().left,
                                paneBottom: pane.getBoundingClientRect().bottom,
                                paneRight: pane.getBoundingClientRect().right,
                                sidebarVisible: getComputedStyle(document.querySelector('.teacher-sidebar')).display !== 'none',
                                sidebarRight: document.querySelector('.teacher-sidebar').getBoundingClientRect().right,
                                workspaceLeft: document.querySelector('#teacher-workspace').getBoundingClientRect().left,
                                workspaceWidth: document.querySelector('#teacher-workspace').getBoundingClientRect().width,
                                calendarRight: calendar.getBoundingClientRect().right,
                            };
                        })(),
                    };
                });
                assert.ok(metrics.pageWidth <= width + 1, `${role} ${width}px: horizontal page overflow (${metrics.pageWidth}px)`);
                assert.equal(metrics.navVisible, width < 1024, `${role} ${width}px: navigation breakpoint`);
                if (width < 1024) {
                    assert.ok(Math.abs(metrics.navBottom - 844) <= 1, `${role}: navigation must stay at the bottom`);
                    assert.ok(metrics.bodyPadding >= 844 - metrics.navTop, `${role}: content needs bottom clearance`);
                    for (const target of metrics.targets) assert.ok(target.width >= 44 && target.height >= 44, `${role}: touch target too small`);
                }
                if (role === 'teacher') {
                    assert.equal(metrics.columns, 7, 'Calendar must retain seven columns');
                    assert.ok(metrics.calendar.dayHeight >= 44, 'Calendar dates need comfortable vertical touch targets');
                    assert.equal(metrics.calendar.selectedBorder, 'rgb(251, 113, 133)', 'Selected date must remain highlighted');
                    assert.equal(metrics.calendar.sidebarVisible, width >= 1024, 'Teacher sidebar must be desktop-only');
                    if (width >= 1024) {
                        assert.ok(Math.abs(metrics.calendar.workspaceLeft - metrics.calendar.sidebarRight) <= 1, 'Teacher content must start beside the sidebar');
                        assert.ok(Math.abs(metrics.calendar.workspaceWidth - (width - metrics.calendar.sidebarRight)) <= 1, 'Desktop workspace must fill the remaining width, without a maximum-width cap');
                        assert.ok(width - metrics.calendar.calendarRight <= 32, 'Calendar should extend to the desktop right gutter');
                    }
                    if (metrics.calendar.containerWidth < 680) {
                        assert.ok(metrics.calendar.gridHeight < 380, 'Mobile month picker should be compact, not desktop-sized');
                        assert.notEqual(metrics.calendar.dotDisplay, 'none', 'Mobile month picker needs event dots');
                        assert.equal(metrics.calendar.previewDisplay, 'none', 'Hide crowded event text on mobile');
                    } else {
                        assert.notEqual(metrics.calendar.previewDisplay, 'none', 'Roomy calendars show event previews');
                    }
                    if (metrics.calendar.containerWidth >= 1000) assert.ok(metrics.calendar.agendaLeft >= metrics.calendar.paneRight, 'Desktop agenda should be beside the month');
                    else assert.ok(metrics.calendar.agendaTop >= metrics.calendar.paneBottom, 'Mobile agenda should be below the month');
                }
                console.log(`PASS ${role}: ${width}px, no horizontal overflow, correct navigation visibility`);
            }
            await page.setViewportSize({ width: 390, height: 844 });
            if (role === 'teacher') {
                await navigation.getByText('Settings', { exact: true }).click();
                await page.waitForFunction(() => document.querySelector('[data-navigation-section="teacher-settings"]').getAttribute('aria-current') === 'location');
                await navigation.getByText('Requests', { exact: true }).click();
                await page.waitForFunction(() => document.querySelector('[data-navigation-section="teacher-requests"]').getAttribute('aria-current') === 'location');
                await page.setViewportSize({ width: 1920, height: 1080 });
                await page.goto(`${base}/consultations/teacher?month=2026-11#teacher-month-calendar`);
                assert.ok(await page.getByRole('heading', { name: 'November 2026', exact: true }).isVisible());
                const sidebar = page.getByRole('navigation', { name: 'Teacher desktop navigation' });
                await sidebar.getByText('Consultation settings', { exact: true }).click();
                await page.waitForFunction(() => document.querySelector('[data-section-navigation] [data-navigation-section="teacher-settings"]').getAttribute('aria-current') === 'location');
                assert.equal(new URL(page.url()).searchParams.get('month'), '2026-11', 'Sidebar navigation must preserve the selected month');
                await sidebar.getByText('My calendar', { exact: true }).click();
                await page.waitForFunction(() => document.querySelector('[data-section-navigation] [data-navigation-section="teacher-month-calendar"]').getAttribute('aria-current') === 'location');
                console.log('PASS teacher desktop: full-width sidebar workspace and November calendar navigation');
            } else if (role === 'student') {
                await navigation.getByText('Calendar', { exact: true }).click();
                await page.waitForURL('**/consultations/browse');
                await page.locator('#student-teacher-calendar').waitFor();
                for (const width of widths) {
                    await page.setViewportSize({ width, height: 844 });
                    const calendar = await page.locator('#student-teacher-calendar').evaluate(element => ({
                        pageWidth: document.documentElement.scrollWidth,
                        columns: getComputedStyle(element.querySelector('.calendar-month-grid')).gridTemplateColumns.split(' ').length,
                        gridHeight: element.querySelector('.calendar-month-grid').getBoundingClientRect().height,
                    }));
                    assert.ok(calendar.pageWidth <= width + 1, `Student booking calendar overflows at ${width}px`);
                    assert.equal(calendar.columns, 7);
                    assert.ok(calendar.gridHeight < 380);
                    console.log(`PASS student booking calendar: ${width}px, compact month and readable session list`);
                }
                await page.setViewportSize({ width: 390, height: 844 });
                const availableDate = page.locator('#student-teacher-calendar a[data-calendar-date]').first();
                if (await availableDate.count()) {
                    await availableDate.click();
                    await page.waitForFunction(() => location.hash.startsWith('#availability-'));
                }
                await page.locator('#student-teacher-calendar [aria-label="Next month"]').click();
                await page.waitForURL(url => url.searchParams.has('month') && url.hash === '#student-teacher-calendar');
                await page.locator('[data-mobile-navigation]').getByText('My profile', { exact: true }).click();
                await page.waitForURL('**/consultations/student/profile');
            } else {
                await navigation.getByText(role === 'admin' ? 'School' : 'My profile', { exact: true }).click();
                await page.waitForURL(role === 'admin' ? '**/admin/school' : '**/consultations/student/profile');
                await page.waitForFunction(() => document.documentElement.scrollWidth <= window.innerWidth + 1);
                assert.ok(await page.locator('[data-mobile-navigation] a[aria-current="page"]').count(), `${role}: active destination missing`);
            }
            await context.close();
        }
    } finally {
        await browser.close();
    }
}

run().catch(error => { console.error(error); process.exitCode = 1; });
