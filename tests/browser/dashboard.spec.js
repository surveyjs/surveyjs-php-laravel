import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const definition = JSON.parse(readFileSync(new URL('../../shared/definitions/dashboard.json', import.meta.url), 'utf8'));
const response = { satisfaction: 4, recommend: 9, channel: 'Search', features: ['Forms'], improve: 'More examples' };
test.beforeEach(async ({ page }) => {
    await page.route('**/api/forms/feedback', route => route.fulfill({ json: { definition } }));
});

test('date presets show actual counts and an empty date range has no charts', async ({ page }) => {
    const requests = [];
    await page.route('**/api/responses?*', route => {
        const query = new URL(route.request().url()).searchParams;
        requests.push(query);
        return route.fulfill({ json: query.get('from') === '2999-01-01' ? [] : [response, response] });
    });
    await page.goto('/examples/dashboard');
    await expect(page.locator('[data-show="dashboard-status"]')).toHaveText('2 responses loaded');
    await expect(page.locator('#dashboard')).toBeVisible();
    await page.locator('#period').selectOption('7');
    await expect.poll(() => requests.length).toBeGreaterThanOrEqual(2);
    expect(requests[1].get('to')).toMatch(/^\d{4}-\d{2}-\d{2}$/);
    await page.locator('#from').fill('2999-01-01');
    await page.locator('#to').fill('2999-01-31');
    await page.locator('#to').blur();
    await expect(page.locator('[data-show="dashboard-status"]')).toHaveText('No responses');
    await expect(page.locator('#dashboard')).toBeHidden();
});

test('a failed lookup offers retry without an unhandled error', async ({ page }) => {
    let fail = true;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('**/api/responses?*', route => fail
        ? route.fulfill({ status: 503, json: { error: 'Unavailable' } })
        : route.fulfill({ json: [response] }));
    await page.goto('/examples/dashboard');
    await expect(page.locator('[data-show="dashboard-status"]')).toHaveText("Couldn't load the responses. Please try again.");
    fail = false;
    await page.getByRole('button', { name: 'Try again', exact: true }).click();
    await expect(page.locator('[data-show="dashboard-status"]')).toHaveText('1 response loaded');
    await expect(page.locator('#dashboard canvas').first()).toBeVisible();
    expect(errors).toEqual([]);
});

test('a slower previous refresh cannot overwrite a newer selection', async ({ page }) => {
    const pending = Promise.withResolvers();
    let requests = 0;
    await page.route('**/api/responses?*', async route => {
        requests++;
        if (requests === 1) { await pending.promise; await route.fulfill({ json: [response] }).catch(() => {}); }
        else await route.fulfill({ json: [response, response, response] });
    });
    await page.goto('/examples/dashboard');
    await expect.poll(() => requests).toBe(1);
    await page.locator('#period').selectOption('90');
    await expect(page.locator('[data-show="dashboard-status"]')).toHaveText('3 responses loaded');
    pending.resolve();
    await page.waitForTimeout(200);
    await expect(page.locator('[data-show="dashboard-status"]')).toHaveText('3 responses loaded');
});
