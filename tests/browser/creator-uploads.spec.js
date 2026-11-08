import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const definition = JSON.parse(readFileSync(new URL('../../shared/definitions/creator-uploads.json', import.meta.url), 'utf8'));
const image = { name: 'logo.png', mimeType: 'image/png', buffer: readFileSync(new URL('../../shared/samples/work-order-scan.png', import.meta.url)) };
async function logo(page) {
    const chooser = page.waitForEvent('filechooser');
    await page.locator('#creator .svc-logo-image-placeholder').click();
    await (await chooser).setFiles(image);
}
async function questionImage(page, file) {
    const question = page.locator('#creator .svc-question__content--image');
    await question.click();
    const chooser = page.waitForEvent('filechooser');
    await question.locator('.svc-image-question-controls button').click();
    await (await chooser).setFiles(file);
}

test('upload confirmation stays distinct from a failed schema save and retry retains the file URL', async ({ page }) => {
    let saved = definition, failSave = true, uploads = 0;
    await page.route('**/api/forms/support-assets', route => {
        if (route.request().method() === 'GET') return route.fulfill({ json: { definition: saved } });
        if (failSave) return route.fulfill({ status: 503, json: { error: 'Save unavailable' } });
        saved = route.request().postDataJSON();
        return route.fulfill({ status: 204 });
    });
    await page.route('**/api/files', route => { uploads++; return route.fulfill({ json: ['image-1'] }); });
    await page.route('**/api/files/image-1', route => route.fulfill({ contentType: 'image/png', body: image.buffer }));
    await page.goto('/examples/creator-uploads');
    await logo(page);
    await expect(page.locator('[data-show="upload-status"]')).toContainText('Logo uploaded.');
    await expect(page.locator('[data-show="schema-status"]')).toContainText('Uploaded files are retained; retry saving their URLs.');
    failSave = false;
    await page.getByRole('button', { name: 'Try saving again', exact: true }).click();
    await expect(page.locator('[data-show="schema-status"]')).toHaveText('Form schema saved.');
    expect(saved.logo).toBe('/api/files/image-1');
    expect(uploads).toBe(1);
    await page.reload();
    await expect(page.locator('#creator img[src="/api/files/image-1"]')).toBeVisible();
    await expect(page.locator('[data-show="asset-urls"]')).toContainText('Logo: /api/files/image-1');
});

test('a question image persists as a URL and appears in the saved form preview', async ({ context, page }) => {
    let saved = definition;
    await context.route('**/api/forms/support-assets', route => {
        if (route.request().method() === 'GET') return route.fulfill({ json: { definition: saved } });
        saved = route.request().postDataJSON();
        return route.fulfill({ status: 204 });
    });
    await context.route('**/api/files', route => route.fulfill({ json: ['question-image'] }));
    await context.route('**/api/files/question-image', route => route.fulfill({ contentType: 'image/png', body: image.buffer }));
    await page.goto('/examples/creator-uploads');
    await questionImage(page, image);
    await expect(page.locator('[data-show="schema-status"]')).toHaveText('Form schema saved.');
    await expect.poll(() => JSON.stringify(saved)).toContain('/api/files/question-image');
    const opened = page.waitForEvent('popup');
    await page.getByRole('link', { name: 'Open saved form', exact: true }).click();
    const popup = await opened;
    await expect(popup.getByRole('img', { name: 'Support resources', exact: true })).toHaveAttribute('src', '/api/files/question-image');
});

test('unsupported image files are rejected before any upload or schema save', async ({ page }) => {
    const writes = [];
    await page.route('**/api/forms/support-assets', route => route.fulfill({ json: { definition } }));
    page.on('request', request => { if (['POST', 'PUT'].includes(request.method())) writes.push(request.url()); });
    await page.goto('/examples/creator-uploads');
    await questionImage(page, { name: 'notes.txt', mimeType: 'text/plain', buffer: Buffer.from('Unsupported') });
    await expect(page.locator('[data-show="upload-status"]')).toHaveText('Choose a PNG, JPEG, WebP, or GIF image up to 5 MB.');
    expect(writes).toEqual([]);
});
