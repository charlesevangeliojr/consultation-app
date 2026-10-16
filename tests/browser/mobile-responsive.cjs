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
            if (role === 'teacher') {
                await page.route('https://psgc.gitlab.io/api/**', route => {
                    const url = new URL(route.request().url());
                    let locations = [];
                    if (url.pathname.endsWith('/regions/')) locations = [{ name: 'National Capital Region (NCR)', code: '130000000' }];
                    if (url.pathname.endsWith('/cities-municipalities/')) locations = [{ name: 'Quezon City', code: '137404000', isCity: true }];
                    if (url.pathname.endsWith('/barangays/')) locations = [{ name: 'Diliman', code: '137404001' }];
                    return route.fulfill({ json: locations });
                });
            }
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
            const mobileAccount = page.locator('[data-mobile-account-menu]');
            const mobileAccountTrigger = mobileAccount.locator('summary');
            assert.ok(await mobileAccountTrigger.isVisible(), `${role}: mobile top bar needs an avatar account trigger`);
            assert.equal((await mobileAccountTrigger.innerText()).trim().length <= 2, true, 'Closed trigger must show only an avatar or initial, not account name/logout');
            assert.equal(await mobileAccount.getByRole('button', { name: 'Sign out', exact: true }).isVisible(), false);
            await mobileAccountTrigger.focus();
            await page.keyboard.press('Enter');
            assert.ok(await mobileAccount.locator('.mobile-account-identity strong').isVisible(), `${role}: account menu must show name`);
            assert.ok(await mobileAccount.getByRole('button', { name: 'Sign out', exact: true }).isVisible());
            for (const width of [320, 390, 768]) {
                await page.setViewportSize({ width, height: 844 });
                const bounds = await mobileAccount.locator('.mobile-account-panel').boundingBox();
                assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1, `${role}: account dropdown must fit screen`);
            }
            await page.keyboard.press('Escape');
            assert.equal(await mobileAccount.getAttribute('open'), null);
            assert.equal(await mobileAccountTrigger.evaluate(element => element === document.activeElement), true);
            await mobileAccountTrigger.click();
            await page.locator('main').click({ position: { x: 5, y: 5 } });
            assert.equal(await mobileAccount.getAttribute('open'), null, 'Outside click should dismiss mobile account menu');
            await page.setViewportSize({ width: 390, height: 844 });
            console.log(`PASS ${role} mobile topbar: avatar-only trigger, name/logout dropdown, keyboard, Escape, outside click and responsive fit`);
            await page.evaluate(() => window.scrollTo(0, 400));
            const fixedHeader = await page.locator('.mobile-topbar').evaluate(header => ({
                position: getComputedStyle(header).position,
                top: header.getBoundingClientRect().top,
                height: header.getBoundingClientRect().height,
                clearance: parseFloat(getComputedStyle(document.body).paddingTop),
            }));
            assert.equal(fixedHeader.position, 'fixed', `${role}: mobile top bar must stay fixed`);
            assert.equal(fixedHeader.top, 0, `${role}: mobile top bar must remain at the viewport top while scrolling`);
            assert.ok(fixedHeader.clearance >= fixedHeader.height, `${role}: fixed top bar must not cover the start of the page`);
            await mobileAccountTrigger.click();
            assert.ok(await mobileAccount.getByRole('button', { name: 'Sign out', exact: true }).isVisible(), `${role}: account dropdown must work while scrolled`);
            await page.keyboard.press('Escape');
            await page.evaluate(() => window.scrollTo(0, 0));
            if (role === 'teacher') {
                await page.goto(`${base}/consultations/teacher/profile`);
                await page.getByRole('heading', { name: 'Your profile', exact: true }).waitFor();
                const offerings = page.locator('#consultation_offerings');
                const originalOfferings = await offerings.inputValue();
                await offerings.fill('Study planning and exam preparation.');
                assert.equal(await page.locator('[data-profile-save-state]').textContent(), 'You have unsaved changes');
                assert.equal(await page.locator('[data-offerings-count]').textContent(), '36 / 2,000');
                await offerings.fill(originalOfferings);
                assert.equal(await page.locator('.profile-save-bar').getAttribute('data-dirty'), 'false', 'Reverting edits should clear the unsaved indicator');
                const fullName = page.locator('#name');
                const originalName = await fullName.inputValue();
                const initiallyComplete = await page.locator('[data-profile-progress]').getAttribute('aria-valuenow') === '100';
                if (initiallyComplete) assert.equal(await page.locator('.profile-progress-card').isVisible(), false, 'Completed profiles must hide checklist');
                await fullName.fill('');
                assert.ok(await page.locator('.profile-progress-card').isVisible(), 'Checklist must reappear when a required detail is missing');
                await page.locator('.profile-checklist-disclosure summary').click();
                assert.ok(await page.locator('.profile-checklist').isVisible(), 'Profile checklist must expand');
                await page.locator('.profile-checklist-disclosure summary').click();
                await fullName.fill(originalName);
                if (initiallyComplete) assert.equal(await page.locator('.profile-progress-card').isVisible(), false, 'Restoring completion must hide checklist again');
                await page.locator('[data-profile-section-link="consultation-offerings"]').click();
                await page.waitForFunction(() => document.querySelector('[data-profile-section-link="consultation-offerings"]').getAttribute('aria-current') === 'location');
                await page.locator('[data-profile-section-link="personal-information"]').click();
                await page.evaluate(() => window.scrollTo(0, 0));
                if (await page.getByText('Setup required', { exact: true }).count()) {
                    await page.goto(`${base}/consultations/teacher`);
                    await page.waitForURL('**/consultations/teacher/profile');
                    assert.ok(await page.getByText('Setup required', { exact: true }).isVisible(), 'Incomplete profile must block the calendar');
                }
                for (const width of widths) {
                    await page.setViewportSize({ width, height: 844 });
                    const design = await page.locator('.teacher-profile-page').evaluate(element => ({
                        width: element.clientWidth,
                        pageWidth: document.documentElement.scrollWidth,
                        columns: getComputedStyle(element.querySelector('.profile-workspace')).gridTemplateColumns.split(' ').length,
                        countryWidth: element.querySelector('#phone_country').getBoundingClientRect().width,
                        numberWidth: element.querySelector('#phone_number').getBoundingClientRect().width,
                    }));
                    assert.ok(design.pageWidth <= width + 1, `Teacher onboarding overflows at ${width}px`);
                    assert.equal(design.columns, design.width >= 860 ? 2 : 1, 'Profile layout must adapt to available container width');
                    assert.ok(design.countryWidth <= 124, `Country dropdown should use a compact fixed width at ${width}px: ${JSON.stringify(design)}`);
                    assert.ok(design.numberWidth > design.countryWidth, 'Phone number should have more space than its country dropdown');
                }
                await page.locator('#phone_country').selectOption('PH');
                await page.locator('#phone_number').fill('123');
                assert.equal(await page.locator('#phone_number').evaluate(element => element.validity.patternMismatch), true, 'Short phone numbers must fail browser validation');
                await page.locator('#phone_country').selectOption('US');
                await page.locator('#phone_number').fill('2025550123');
                assert.equal(await page.locator('#phone_number').evaluate(element => element.validity.valid), true, 'Country dropdown must apply national-number rules');
                await page.locator('#phone_country').selectOption('PH');
                await page.locator('#phone_number').fill('9123456789');
                await page.locator('#birthday').fill('1990-05-10');
                await page.getByLabel('Sex', { exact: false }).selectOption('Female');
                await page.waitForFunction(() => [...document.querySelector('#region-options').options].some(option => option.value === 'National Capital Region (NCR)'));
                await page.locator('#region').fill('National Capital Region (NCR)');
                await page.waitForFunction(() => [...document.querySelector('#city-options').options].some(option => option.value === 'Quezon City'));
                await page.locator('#city').fill('Quezon City');
                await page.waitForFunction(() => [...document.querySelector('#barangay-options').options].some(option => option.value === 'Diliman'));
                await page.locator('#barangay').fill('Diliman');
                await page.locator('#house_number').fill('12 Fictional Street');
                await page.locator('#postal_code').fill('1100');
                assert.equal(await page.locator('[name="subject_ids[]"]').count(), 0, 'Profile should go directly to the offerings description without a subject picker');
                await page.locator('#consultation_offerings').fill('Algebra coaching, science project feedback, and study planning.');
                await page.locator('#profile_photo').setInputFiles({ name: 'too-large.png', mimeType: 'image/png', buffer: Buffer.alloc(2 * 1024 * 1024 + 1) });
                assert.ok(await page.getByText('Your picture must be 2 MB or smaller.', { exact: true }).isVisible());
                const portrait = await page.evaluate(() => {
                    const canvas = document.createElement('canvas');
                    canvas.width = 320; canvas.height = 160;
                    const context = canvas.getContext('2d');
                    context.fillStyle = '#be123c'; context.fillRect(0, 0, 320, 160);
                    context.fillStyle = '#0369a1'; context.fillRect(160, 0, 160, 160);
                    context.font = 'bold 64px sans-serif'; context.fillStyle = '#ffffff'; context.fillText('MS', 28, 104);
                    return canvas.toDataURL('image/png').split(',')[1];
                });
                const photoUpload = { name: 'demo-teacher.png', mimeType: 'image/png', buffer: Buffer.from(portrait, 'base64') };
                const originalPhoto = await page.locator('[data-profile-photo-preview]').getAttribute('src');
                const cropDialog = page.getByRole('dialog', { name: 'Crop profile picture', exact: true });
                await page.locator('#profile_photo').setInputFiles(photoUpload);
                await cropDialog.waitFor();
                await page.keyboard.press('Escape');
                await cropDialog.waitFor({ state: 'hidden' });
                await page.waitForFunction(() => document.querySelector('#profile_photo').files.length === 0);
                assert.equal(await page.locator('[data-profile-photo-preview]').getAttribute('src'), originalPhoto, 'Cancelling must preserve the saved photo');
                await page.locator('#profile_photo').setInputFiles(photoUpload);
                await cropDialog.waitFor();
                await page.waitForFunction(() => !document.querySelector('[data-crop-apply]').disabled);
                for (const width of [320, 390, 768, 1440]) {
                    await page.setViewportSize({ width, height: 844 });
                    const bounds = await cropDialog.boundingBox();
                    assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1, 'Crop dialog must fit mobile and desktop widths');
                    assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 845, 'Crop dialog must fit the viewport height');
                }
                const cropCanvas = cropDialog.locator('[data-crop-canvas]');
                const centered = await cropCanvas.evaluate(canvas => canvas.toDataURL());
                await cropDialog.locator('[data-crop-zoom]').evaluate(input => { input.value = '2'; input.dispatchEvent(new Event('input', { bubbles: true })); });
                await cropCanvas.focus();
                await page.keyboard.press('ArrowRight');
                assert.notEqual(await cropCanvas.evaluate(canvas => canvas.toDataURL()), centered, 'Zoom and keyboard reposition must change the crop');
                const canvasBounds = await cropCanvas.boundingBox();
                await page.mouse.move(canvasBounds.x + canvasBounds.width / 2, canvasBounds.y + canvasBounds.height / 2);
                await page.mouse.down();
                await page.mouse.move(canvasBounds.x + canvasBounds.width / 2 + 30, canvasBounds.y + canvasBounds.height / 2 + 20);
                await page.mouse.up();
                await cropDialog.getByRole('button', { name: 'Reset', exact: true }).click();
                assert.equal(await cropDialog.locator('[data-crop-zoom]').inputValue(), '1');
                assert.equal(await cropCanvas.evaluate(canvas => canvas.toDataURL()), centered, 'Reset should restore the centered crop');
                await cropDialog.getByRole('button', { name: 'Use photo', exact: true }).click();
                await cropDialog.waitFor({ state: 'hidden' });
                assert.equal(await page.locator('#profile_photo').evaluate(input => input.files[0].type), 'image/jpeg', 'Upload must contain the cropped JPEG, not the original');
                assert.ok((await page.locator('[data-profile-photo-preview]').getAttribute('src')).startsWith('blob:'), 'New photo needs an instant preview');
                await page.waitForFunction(() => document.querySelector('[data-profile-progress]').getAttribute('aria-valuenow') === '100');
                await page.getByRole('button', { name: /^(Complete profile|Save changes)/ }).click();
                await page.getByText('Profile complete', { exact: true }).waitFor();
                await page.waitForFunction(() => {
                    const picture = document.querySelector('[data-profile-photo-preview]');
                    return picture && !picture.src.startsWith('blob:') && picture.complete && picture.naturalWidth > 0;
                });
                assert.equal(await page.locator('[data-profile-photo-preview]').evaluate(image => image.naturalWidth), 512, 'Saved avatar must be the 512px cropped image');
                assert.equal(await page.locator('[data-profile-photo-preview]').evaluate(image => image.naturalHeight), 512);
                console.log('PASS photo cropper: responsive dialog, cancel, zoom, keyboard/drag reposition, reset and cropped-file persistence');
                assert.equal(await page.locator('#phone_country').inputValue(), 'PH');
                assert.equal(await page.locator('#phone_number').inputValue(), '9123456789');
                assert.equal(await page.locator('#gender').inputValue(), 'Female');
                assert.equal(await page.locator('#city').inputValue(), 'Quezon City');
                assert.equal(await page.locator('#barangay').inputValue(), 'Diliman');
                const offlineLocations = route => route.abort();
                await page.route('https://psgc.gitlab.io/api/**', offlineLocations);
                await page.reload();
                await page.getByText('Address suggestions are temporarily unavailable.', { exact: false }).waitFor();
                await page.locator('#region').fill('Manual region');
                assert.equal(await page.locator('#city').inputValue(), '', 'Changing a parent address must clear stale child values');
                assert.equal(await page.locator('#barangay').inputValue(), '', 'Changing region must clear the old barangay');
                assert.equal(await page.locator('#city').isEnabled(), true, 'Manual address entry must remain available offline');
                await page.locator('#city').fill('Manual city');
                await page.locator('#barangay').fill('Manual barangay');
                await page.waitForFunction(() => document.querySelector('[data-profile-progress]').getAttribute('aria-valuenow') === '100');
                await page.unroute('https://psgc.gitlab.io/api/**', offlineLocations);
                await page.reload();
                await page.getByText('Profile complete', { exact: true }).waitFor();
                console.log('PASS teacher details: country formats, sex selection, cascading address suggestions, offline manual entry and editable offerings');
                if (process.env.CALENDAR_SCREENSHOT_DIR) {
                    require('node:fs').mkdirSync(process.env.CALENDAR_SCREENSHOT_DIR, { recursive: true });
                    for (const width of [390, 1440]) {
                        await page.setViewportSize({ width, height: 1000 });
                        await page.screenshot({ path: require('node:path').join(process.env.CALENDAR_SCREENSHOT_DIR, `teacher-profile-${width}.png`), fullPage: true });
                    }
                }
                await page.goto(`${base}/consultations/teacher`);
                await page.setViewportSize({ width: 390, height: 844 });
                console.log('PASS teacher setup: required details, photo validation/preview, saved photo and workspace unlock');
                const scheduledDate = await page.locator('[data-calendar-date]').evaluateAll(links => links.find(link => /, [1-9]\d* availability windows/.test(link.getAttribute('aria-label')))?.href);
                if (scheduledDate) await page.goto(scheduledDate);
                const scheduleModal = page.getByRole('dialog', { name: /.+/ });
                const calendarURL = page.url();
                const dateLink = page.locator('#teacher-month-calendar a[data-calendar-date]').first();
                await dateLink.click();
                await scheduleModal.waitFor();
                await scheduleModal.getByRole('heading', { name: 'Availability windows', exact: true }).waitFor();
                assert.equal(page.url(), calendarURL, 'Mobile date selection must not navigate away from the calendar');
                for (const width of [320, 390, 768]) {
                    await page.setViewportSize({ width, height: 844 });
                    const bounds = await scheduleModal.boundingBox();
                    assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= width + 1, 'Schedule modal must fit mobile viewport');
                    assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 845, 'Schedule modal must fit viewport height');
                }
                await page.keyboard.press('Escape');
                await scheduleModal.waitFor({ state: 'hidden' });
                assert.equal(await dateLink.evaluate(element => document.activeElement === element), true, 'Closing schedule must restore focus to selected date');
                await page.setViewportSize({ width: 390, height: 844 });
                await page.route('**/consultations/teacher?*day=*', route => route.abort());
                await dateLink.click();
                await scheduleModal.getByRole('alert').waitFor();
                assert.ok(await scheduleModal.getByRole('link', { name: 'Open full calendar' }).isVisible(), 'Failed loads need a working full calendar fallback');
                await scheduleModal.getByRole('button', { name: 'Close schedule' }).click();
                await page.unroute('**/consultations/teacher?*day=*');
                console.log('PASS teacher mobile schedule modal: date selection, responsive sizing, Escape, focus restoration and load failure fallback');
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
                        dockWidth: nav.querySelector('.mobile-navigation-dock').getBoundingClientRect().width,
                        labelsFit: [...nav.querySelectorAll('.mobile-navigation-label')].every(label => label.scrollWidth <= label.clientWidth + 1),
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
                    assert.ok(metrics.dockWidth <= 560, `${role}: mobile dock must stay compact on tablets`);
                    assert.ok(metrics.labelsFit, `${role}: navigation labels must fit without clipping`);
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
                await page.waitForURL('**/consultations/teacher/settings');
                assert.ok(await page.getByRole('button', { name: 'Save preferences' }).isVisible());
                await navigation.getByText('Requests', { exact: true }).click();
                await page.waitForURL('**/consultations/teacher/requests');
                await page.getByRole('navigation', { name: 'Filter requests by status' }).getByRole('link', { name: /^Pending\s+\d+$/ }).click();
                await page.waitForURL(url => url.searchParams.get('status') === 'pending');
                await page.setViewportSize({ width: 1920, height: 1080 });
                await page.goto(`${base}/consultations/teacher?month=2026-11#teacher-month-calendar`);
                assert.ok(await page.getByRole('heading', { name: 'November 2026', exact: true }).isVisible());
                const sidebar = page.getByRole('navigation', { name: 'Teacher desktop navigation', includeHidden: true });
                assert.equal(await sidebar.locator('a').count(), 4, 'Main desktop navigation should contain four workspace pages');
                assert.equal(await sidebar.getByText('My profile', { exact: true }).count(), 0, 'Profile belongs in the bottom account menu');
                const accountMenu = page.locator('[data-teacher-account-menu]');
                const accountTrigger = accountMenu.locator('summary');
                await accountTrigger.focus();
                await page.keyboard.press('Enter');
                assert.ok(await accountMenu.getAttribute('open') !== null, 'Keyboard must open account menu');
                const menuBounds = await accountMenu.locator('.teacher-account-options').boundingBox();
                const triggerBounds = await accountTrigger.boundingBox();
                assert.ok(menuBounds.y >= 0 && menuBounds.y + menuBounds.height <= triggerBounds.y, 'Account dropdown should open above its bottom trigger');
                await page.keyboard.press('Escape');
                assert.equal(await accountMenu.getAttribute('open'), null, 'Escape must close account menu');
                assert.ok(await accountTrigger.evaluate(element => element === document.activeElement), 'Escape returns focus to the account trigger');
                await accountTrigger.click();
                await page.getByRole('heading', { name: 'Consultation calendar', exact: true }).click();
                assert.equal(await accountMenu.getAttribute('open'), null, 'Outside click must close account menu');
                await sidebar.getByText('My calendar', { exact: true }).click();
                assert.equal((await sidebar.locator('a[aria-current="page"]').innerText()).trim(), 'My calendar');
                assert.equal(new URL(page.url()).searchParams.get('month'), '2026-11', 'Sidebar navigation must preserve the selected month');
                console.log('PASS teacher desktop: full-width sidebar workspace and November calendar navigation');
                for (const [desktopLabel, mobileLabel, path, heading] of [
                    ['Consultation requests', 'Requests', 'requests', 'Consultation requests'],
                    ['Availability', 'Availability', 'availability', 'Availability'],
                    ['Consultation settings', 'Settings', 'settings', 'Consultation settings'],
                    ['My profile', 'My profile', 'profile', 'Your profile'],
                ]) {
                    await page.setViewportSize({ width: 1920, height: 1080 });
                    if (path === 'profile') {
                        await accountTrigger.click();
                        await accountMenu.getByRole('link', { name: 'My profile', exact: true }).click();
                    } else {
                        await sidebar.getByText(desktopLabel, { exact: true }).click();
                    }
                    await page.waitForURL(`**/consultations/teacher/${path}`);
                    await page.getByRole('heading', { name: heading, exact: true, level: 1 }).waitFor();
                    assert.equal(await page.locator('#teacher-month-calendar').count(), 0, 'Other workspace pages must not render the calendar');
                    for (const width of widths) {
                        await page.setViewportSize({ width, height: 844 });
                        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), `Teacher ${path} overflows at ${width}px`);
                        assert.equal((await navigation.locator('a[aria-current="page"]').innerText()).trim(), mobileLabel);
                        if (path === 'profile') {
                            assert.equal(await sidebar.locator('a[aria-current="page"]').count(), 0);
                            assert.equal((await accountMenu.locator('a[aria-current="page"]').textContent()).trim(), 'My profile');
                        } else {
                            assert.equal((await sidebar.locator('a[aria-current="page"]').innerText()).trim(), desktopLabel);
                        }
                    }
                    await page.setViewportSize({ width: 390, height: 844 });
                    await navigation.getByText('Calendar', { exact: true }).click();
                    await page.waitForURL(url => url.pathname === '/consultations/teacher');
                    await navigation.getByText(mobileLabel, { exact: true }).click();
                    await page.waitForURL(`**/consultations/teacher/${path}`);
                    await page.getByRole('heading', { name: heading, exact: true, level: 1 }).waitFor();
                    await page.waitForFunction(() => !document.documentElement.hasAttribute('data-turbo-preview') && !document.documentElement.hasAttribute('aria-busy'));
                    console.log(`PASS teacher ${path}: separate responsive page and desktop/mobile active navigation`);
                }
                await page.setViewportSize({ width: 1440, height: 600 });
                await accountTrigger.click();
                await accountMenu.getByRole('button', { name: 'Sign out', exact: true }).click();
                await page.waitForURL('**/portal/login');
                await page.goto(`${base}/consultations/teacher/profile`);
                await page.waitForURL('**/portal/login');
                console.log('PASS teacher account dropdown: keyboard, Escape, outside click, profile navigation and sign out');
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
                    const studentURL = page.url();
                    const date = await availableDate.getAttribute('data-calendar-date');
                    const sessionCount = await page.locator(`[data-schedule-date="${date}"]`).count();
                    await availableDate.click();
                    const modal = page.getByRole('dialog');
                    await modal.waitFor();
                    assert.equal(await modal.locator('[data-schedule-date]').count(), sessionCount, 'Modal must show every availability window for the selected date');
                    assert.equal(page.url(), studentURL, 'Student mobile date selection must stay on calendar');
                    const reason = modal.getByRole('textbox', { name: 'Consultation reason' }).first();
                    if (await reason.count()) await reason.fill('Keep this reason when closing the schedule.');
                    await modal.getByRole('button', { name: 'Close schedule' }).click();
                    assert.equal(await page.locator(`[data-schedule-date="${date}"]`).count(), sessionCount, 'Closing must restore all schedule cards');
                    await availableDate.click();
                    if (await reason.count()) assert.equal(await reason.inputValue(), 'Keep this reason when closing the schedule.');
                    await page.setViewportSize({ width: 1280, height: 844 });
                    await modal.waitFor({ state: 'hidden' });
                    assert.equal(await page.locator('html').evaluate(element => element.classList.contains('calendar-schedule-open')), false, 'Desktop resize must unlock page scrolling');
                    await availableDate.click();
                    await page.waitForFunction(() => location.hash.startsWith('#availability-'));
                    await page.setViewportSize({ width: 390, height: 844 });
                    console.log('PASS student mobile schedule modal: all sessions, booking form preservation, close/resize cleanup and desktop fallback');
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
            if (role !== 'teacher') {
                await page.locator('[data-mobile-account-menu] summary').click();
                await page.locator('[data-mobile-account-menu]').getByRole('button', { name: 'Sign out', exact: true }).click();
                await page.waitForURL(`**/${role === 'admin' ? 'admin' : 'portal'}/login`);
                console.log(`PASS ${role}: mobile account sign out`);
            }
            await context.close();
        }
    } finally {
        await browser.close();
    }
}

run().catch(error => { console.error(error); process.exitCode = 1; });
