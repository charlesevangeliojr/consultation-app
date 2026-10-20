// Read-only public landing-page diagnostics. Does not log cookies or account details.
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.APP_TEST_URL || 'http://localhost:8000';

async function run() {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
    try {
        const page = await browser.newPage({ viewport: { width: 390, height: 844 }, hasTouch: true });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.addInitScript(() => localStorage.setItem('consultation-pwa-dismissed-until', String(Date.now() + 86400000)));
        await page.goto(base, { waitUntil: 'domcontentloaded', timeout: 60000 });
        await page.getByRole('tab', { name: 'I’m a teacher', exact: true }).waitFor();
        const before = await page.evaluate(async () => {
            const script = [...document.scripts].find(script => script.src.includes('/js/landing.js'));
            const inspect = async url => {
                const response = await fetch(url, { cache: 'no-store' });
                const text = await response.text();
                return { url, status: response.status, type: response.headers.get('content-type'), bytes: text.length,
                    hasRoleHandler: text.includes('data-landing-role') && text.includes('selectRole'),
                    challenge: text.includes('slowAES') || text.includes('/aes.js'), preview: text.slice(0, 150) };
            };
            return {
                url: location.href, title: document.title,
                script: script?.getAttribute('src'), initialized: window.consultationLandingInitialized === true,
                openDialogs: [...document.querySelectorAll('dialog[open]')].map(dialog => dialog.className),
                scripts: await Promise.all([inspect(script?.src || '/js/landing.js'), inspect(`/js/landing.js?_check=${Date.now()}`)]),
            };
        });
        await page.getByRole('tab', { name: 'I’m a teacher', exact: true }).click({ timeout: 10000 });
        const after = await page.evaluate(() => ({
            teacherSelected: document.querySelector('#hero-teacher-tab')?.getAttribute('aria-selected'),
            teacherVisible: document.querySelector('#hero-teacher-panel')?.hidden === false,
            studentVisible: document.querySelector('#hero-student-panel')?.hidden === false,
        }));
        console.log(JSON.stringify({ before, after, errors }, null, 2));
    } finally { await browser.close(); }
}
run().catch(error => { console.error(error); process.exitCode = 1; });
