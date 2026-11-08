import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const pdf = Buffer.from('%PDF-1.7\nDemo document\n%%EOF');
const firstRow = page => page.locator('[data-claim-row]').first();

test('generation stays pending and Download PDF saves a named file with the returned bytes', async ({ page }) => {
    const pending = Promise.withResolvers();
    await page.route('**/api/claims/*/pdf', async route => {
        await pending.promise;
        return route.fulfill({ contentType: 'application/pdf', body: pdf });
    });
    await page.goto('/examples/server-pdf');
    const row = firstRow(page);
    await row.getByRole('button', { name: 'Download PDF', exact: true }).click();
    await expect(row.locator('[data-pdf-status]')).toHaveText('Generating the PDF...');
    await expect(row.getByRole('button', { name: 'Open PDF', exact: true })).toBeDisabled();
    const downloaded = page.waitForEvent('download');
    pending.resolve();
    const file = await downloaded;
    expect(file.suggestedFilename()).toMatch(/^claim-\d+\.pdf$/);
    expect(readFileSync(await file.path())).toEqual(pdf);
    await expect(row.locator('[data-pdf-status]')).toHaveText('PDF ready. Download started.');
    await expect(row.getByRole('button', { name: 'Open PDF', exact: true })).toBeEnabled();
});

test('blocked popups provide working links to the generated PDF', async ({ page }) => {
    await page.addInitScript(() => { window.open = () => null; });
    await page.route('**/api/claims/*/pdf', route => route.fulfill({ contentType: 'application/pdf', body: pdf }));
    await page.goto('/examples/server-pdf');
    const row = firstRow(page);
    await row.getByRole('button', { name: 'Open PDF', exact: true }).click();
    await expect(row.locator('[data-pdf-status]')).toHaveText('PDF ready. Use the links below to open or download it.');
    await expect(row.getByRole('link', { name: 'Open generated PDF', exact: true })).toHaveAttribute('href', /^blob:/);
    const downloaded = page.waitForEvent('download');
    await row.getByRole('link', { name: 'Download generated PDF', exact: true }).click();
    expect(readFileSync(await (await downloaded).path())).toEqual(pdf);
});

for (const failure of ['service error', 'network error', 'wrong content type']) {
    test(`${failure} closes the placeholder tab and allows retry`, async ({ page }) => {
        let fail = true;
        await page.route('**/api/claims/*/pdf', route => {
            if (!fail) return route.fulfill({ contentType: 'application/pdf', body: pdf });
            if (failure === 'network error') return route.abort('connectionfailed');
            return failure === 'service error'
                ? route.fulfill({ status: 503, json: { error: 'PDF service unavailable' } })
                : route.fulfill({ contentType: 'text/html', body: 'Service unavailable' });
        });
        await page.goto('/examples/server-pdf');
        const row = firstRow(page);
        const opened = page.waitForEvent('popup');
        await row.getByRole('button', { name: 'Open PDF', exact: true }).click();
        const popup = await opened;
        await expect.poll(() => popup.isClosed()).toBe(true);
        await expect(row.locator('[data-pdf-status]')).toContainText(failure === 'service error' ? 'PDF service unavailable' : failure === 'network error' ? "Couldn't generate" : "didn't return a PDF");
        fail = false;
        const downloaded = page.waitForEvent('download');
        await row.getByRole('button', { name: 'Download PDF', exact: true }).click();
        await downloaded;
        await expect(row.locator('[data-pdf-status]')).toHaveText('PDF ready. Download started.');
    });
}
