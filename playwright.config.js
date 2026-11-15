// Browser tests for the step pages: what PHPUnit and the contract test can't see, because they never run
// shared/client/host.js or shared/ui/page-tabs.js. Chromium only.
//
//   shared/browser-tests/   the specs every platform runs (HTTP, the page DOM and the shared seed only)
//   tests/browser/          specs only this app can run (none at the moment)
//
//   npm run test:browser                              migrates and seeds database/browser-tests.sqlite, then
//                                                     composer start on APP_PORT (default 8000)
//   BASE_URL=http://127.0.0.1:9000 npm run test:browser   against an app that is already running
//
// Outside CI, an app that already answers on APP_PORT is used as it is, with its own database.
import { resolve } from 'node:path';
import { defineConfig, devices } from '@playwright/test';

const port = process.env.APP_PORT || '8000';
const baseURL = process.env.BASE_URL ?? `http://127.0.0.1:${port}`;
const database = resolve('database/browser-tests.sqlite');

export default defineConfig({
    testDir: '.',
    testMatch: ['shared/browser-tests/**/*.spec.js', 'tests/browser/**/*.spec.js'],
    workers: 1,   // PHP's built-in server answers one request at a time on Windows
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['github']] : 'list',
    use: {
        baseURL,
        trace: 'retain-on-failure',
    },
    projects: [{
        name: 'chromium',
        use: {
            ...devices['Desktop Chrome'],
            // Full Chromium in its new headless mode, not the headless shell: the shell has no PDF viewer
            // and turns a navigation to a PDF into a download, so server-pdf's tab never shows the blob: URL.
            channel: 'chromium',
            viewport: { width: 1440, height: 900 },
            // Under composer dev, a page the test fulfils itself loads its assets from Vite on [::1]:5173;
            // Chrome's Local Network Access checks would block that. Built assets (CI) don't need it.
            launchOptions: { args: ['--disable-features=LocalNetworkAccessChecks'] },
        },
    }],
    webServer: process.env.BASE_URL ? undefined : {
        // A fresh, seeded database every run: the specs expect the seed, not what earlier runs stored
        command: `php -r "touch(getenv('DB_DATABASE'));" && php artisan migrate:fresh --seed --force && composer start`,
        env: { DB_DATABASE: database, APP_PORT: port },
        url: `${baseURL}/up`,
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
    },
});
