// Read-only diagnostics against public PWA resources; never prints cookies or account data.
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const base = process.env.APP_TEST_URL || 'http://localhost:8000';

async function run() {
    const browser = await chromium.launch({ headless: true, ...(process.env.CHROME_PATH ? { executablePath: process.env.CHROME_PATH } : {}) });
    try {
        const page = await browser.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
        const errors = [];
        const simulateCredentialsFix = process.env.PWA_CHECK_CREDENTIALS === '1';
        page.on('pageerror', error => errors.push(error.message));
        page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
        await page.goto(base, { waitUntil: 'domcontentloaded', timeout: 60000 });
        await page.waitForFunction(() => document.querySelector('link[rel="manifest"]'), null, { timeout: 30000 });
        if (simulateCredentialsFix) {
            // Change only this browser's DOM; preserve the real HTTPS connection.
            await page.evaluate(() => {
                const manifest = document.querySelector('link[rel="manifest"]');
                manifest.setAttribute('crossorigin', 'use-credentials');
                const url = new URL(manifest.href);
                url.searchParams.set('_pwa_check', 'credentials');
                manifest.href = url.href;
            });
        }
        const result = await page.evaluate(async () => {
            const manifest = document.querySelector('link[rel="manifest"]');
            const inspect = async (path, credentials = 'same-origin') => {
                try {
                    const response = await fetch(path, { credentials, cache: 'no-store' });
                    const text = await response.text();
                    const type = response.headers.get('content-type');
                    return { path, credentials, status: response.status, type, challenge: text.includes('slowAES') || text.includes('/aes.js'), preview: type?.startsWith('image/') ? '(image bytes)' : text.slice(0, 110) };
                } catch (error) { return { path, credentials, error: error.message }; }
            };
            let worker;
            try {
                const registration = await navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' });
                await Promise.race([navigator.serviceWorker.ready, new Promise(resolve => setTimeout(resolve, 5000))]);
                worker = { scope: registration.scope, active: !!registration.active };
            } catch (error) { worker = { error: error.message }; }
            return {
                url: location.href, title: document.title, secure: isSecureContext,
                manifest: { href: manifest.getAttribute('href'), crossorigin: manifest.getAttribute('crossorigin') },
                resources: await Promise.all([
                    inspect(manifest.href, 'omit'), inspect(manifest.href), inspect('/sw.js'), inspect('/pwa/icon-192.png'),
                    inspect('/js/pwa.js'),
                ]), worker,
            };
        });
        // Browser installability diagnostics are available through Chromium DevTools.
        const session = await page.context().newCDPSession(page);
        await session.send('Page.enable');
        const manifestCheck = await session.send('Page.getAppManifest').then(result => ({ url: result.url, errors: result.errors, parsed: !!result.data })).catch(error => ({ error: error.message }));
        const installability = await session.send('Page.getInstallabilityErrors').catch(error => ({ error: error.message }));
        console.log(JSON.stringify({ simulatedManifestCredentialsFix: simulateCredentialsFix, ...result, manifestCheck, installability, errors }, null, 2));
    } finally { await browser.close(); }
}
run().catch(error => { console.error(error); process.exitCode = 1; });
