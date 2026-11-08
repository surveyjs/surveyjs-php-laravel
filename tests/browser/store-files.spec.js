import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const image = { name: 'receipt.png', mimeType: 'image/png', buffer: readFileSync(new URL('../../shared/samples/work-order-scan.png', import.meta.url)) };

async function uploadReceipt(page) {
    const chooser = page.waitForEvent('filechooser');
    await page.locator('#form label[aria-label="Select File"]').click();
    await (await chooser).setFiles(image);
    await expect(page.locator('[data-show="file-status"]')).toHaveText('Files uploaded. Submit the expense report to save their references.');
}

test('a failed response save can be retried without uploading the files again', async ({ page }) => {
    let uploads = 0;
    const submissions = [];
    await page.route('**/api/files', route => { uploads++; return route.fulfill({ json: ['receipt-id'] }); });
    await page.route('**/api/files/receipt-id', route => route.fulfill({ contentType: 'image/png', body: image.buffer }));
    await page.route('**/api/responses', route => {
        submissions.push(route.request().postDataJSON());
        return submissions.length === 1
            ? route.fulfill({ status: 503, json: { error: 'Unavailable' } })
            : route.fulfill({ status: 201, json: { id: 901 } });
    });
    await page.goto('/examples/store-files');
    await page.getByRole('textbox', { name: /Purpose of the expense/ }).fill('Travel to a customer');
    await uploadReceipt(page);
    await expect(page.locator('[data-show="sizes"]')).toBeVisible();
    await expect(page.locator('[data-show="sizes"]')).toContainText('with file IDs');
    await page.getByRole('button', { name: 'Submit expense report', exact: true }).click();
    await expect(page.getByRole('heading', { name: "Your expense report hasn't been saved." })).toBeVisible();
    await page.getByRole('button', { name: 'Try again', exact: true }).click();
    await expect(page.getByText('Response ID: 901', { exact: true })).toBeVisible();
    expect(submissions).toHaveLength(2);
    expect(submissions[1]).toEqual(submissions[0]);
    expect(submissions[1].data.receipt[0].content).toBe('receipt-id');
    expect(uploads).toBe(1);
    await page.getByRole('button', { name: 'Start over', exact: true }).click();
    await expect(page.getByRole('textbox', { name: /Purpose of the expense/ })).toBeEmpty();
    await expect(page.locator('[data-show="sizes"]')).toHaveText('Upload a file to compare response sizes.');
});

test('saving waits for a valid response ID and disables reset while pending', async ({ page }) => {
    const pending = Promise.withResolvers();
    await page.route('**/api/files', route => route.fulfill({ json: ['receipt-id'] }));
    await page.route('**/api/files/receipt-id', route => route.fulfill({ contentType: 'image/png', body: image.buffer }));
    await page.route('**/api/responses', async route => { await pending.promise; return route.fulfill({ status: 201, json: {} }); });
    await page.goto('/examples/store-files');
    await page.getByRole('textbox', { name: /Purpose of the expense/ }).fill('Travel');
    await uploadReceipt(page);
    await page.getByRole('button', { name: 'Submit expense report', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Start over', exact: true })).toBeDisabled();
    pending.resolve();
    await expect(page.getByRole('button', { name: 'Try again', exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Start over', exact: true })).toBeEnabled();
});
