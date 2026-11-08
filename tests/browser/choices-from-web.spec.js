import { expect, test } from '@playwright/test';

test('changing region clears the office before its choices arrive and review shows API values', async ({ page }) => {
    const pending = Promise.withResolvers();
    let requestedAmericas = false;
    const authHeaders = [];
    const posts = [];
    await page.route('**/api/offices?*', async route => {
        authHeaders.push(route.request().headers().authorization);
        const americas = new URL(route.request().url()).searchParams.get('region') === 'americas';
        if (americas) { requestedAmericas = true; await pending.promise; }
        return route.fulfill({ json: americas ? [{ id: 'aus', name: 'Austin' }] : [{ id: 'ber', name: 'Berlin' }] });
    });
    await page.route('**/api/countries', route => route.fulfill({ json: [{ code: 'DE', name: 'Germany' }] }));
    page.on('request', request => { if (request.method() === 'POST') posts.push(request.url()); });
    await page.goto('/examples/choices-from-web');
    const office = page.getByRole('combobox', { name: /^Office/ });
    await office.press('ArrowDown');
    await page.getByRole('option', { name: 'Berlin', exact: true }).click();
    await expect(office).toHaveValue('Berlin');
    await page.getByRole('radio', { name: 'Americas', exact: true }).press('Space');
    await expect.poll(() => requestedAmericas).toBe(true);
    await expect(office).toHaveValue('');
    pending.resolve();
    await office.press('ArrowDown');
    await page.getByRole('option', { name: 'Austin', exact: true }).click();
    await page.getByRole('combobox', { name: /^Your country/ }).press('ArrowDown');
    await page.getByRole('option', { name: 'Germany', exact: true }).click();
    await page.getByRole('button', { name: 'Review selection', exact: true }).click();
    await expect(page.locator('#choice-summary')).toBeVisible();
    await expect(page.locator('[data-show="selected-office"]')).toHaveText('Austin');
    await expect(page.locator('[data-show="selected-country"]')).toHaveText('Germany');
    expect(JSON.parse(await page.locator('[data-show="selected-values"]').textContent())).toEqual({ region: 'americas', office: 'aus', country: 'DE' });
    expect(authHeaders).toEqual(['Bearer demo-token', 'Bearer demo-token']);
    expect(posts).toEqual([]);
    await page.getByRole('button', { name: 'Start over', exact: true }).click();
    await expect(page.locator('#choice-summary')).toBeHidden();
    await expect(page.getByRole('radio', { name: 'Europe', exact: true })).toBeChecked();
    await expect(page.getByRole('combobox', { name: /^Office/ })).toHaveValue('');
});
