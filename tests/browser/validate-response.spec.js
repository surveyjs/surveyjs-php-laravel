import { expect, test } from '@playwright/test';

test('sample answers submit once and show success only after a returned response ID', async ({ page }) => {
    const pending = Promise.withResolvers();
    let payload;
    await page.route('**/api/responses', async route => {
        payload = route.request().postDataJSON();
        await pending.promise;
        return route.fulfill({ status: 201, json: { id: 711 } });
    });
    await page.goto('/examples/validate-response');
    await page.getByRole('button', { name: 'Use sample answers', exact: true }).click();
    await page.getByRole('button', { name: 'Submit claim', exact: true }).click();
    await expect(page.getByRole('button', { name: 'Start over', exact: true })).toBeDisabled();
    await expect(page.getByRole('heading', { name: 'Your claim has been saved.', exact: true })).toBeHidden();
    pending.resolve();
    await expect(page.getByText('Response ID: 711', { exact: true })).toBeVisible();
    expect(payload.formId).toBe('claim');
    expect(payload.data.customer_email).toBe('ann@example.com');
    await page.getByRole('button', { name: 'Start over', exact: true }).click();
    await expect(page.locator('[data-show="validation-status"]')).toHaveText('No response checked');
    await expect(page.locator('[data-show="validation-payload"]')).toBeEmpty();
});

test('tampered payloads show the field errors and a generic bad request is not called a validation rejection', async ({ page }) => {
    let generic = false;
    await page.route('**/api/responses', route => generic
        ? route.fulfill({ status: 400, json: { error: 'Invalid request envelope' } })
        : route.fulfill({ status: 400, json: { errors: [{ type: 'invalidChoiceValue', path: 'incident' }] } }));
    await page.goto('/examples/validate-response');
    await page.getByRole('button', { name: 'An unknown choice', exact: true }).click();
    await expect(page.locator('[data-show="validation-status"]')).toHaveText('Response rejected. No data was saved.');
    await expect(page.locator('#validation-errors')).toContainText('What happened?: invalidChoiceValue');
    await page.getByText('Submitted payload', { exact: true }).click();
    await expect(page.locator('[data-show="validation-payload"]')).toContainText('"incident": "meteor"');
    generic = true;
    await page.getByRole('button', { name: 'An unknown choice', exact: true }).click();
    await expect(page.locator('[data-show="validation-status"]')).toHaveText('Invalid request envelope');
    await expect(page.locator('#validation-errors')).toBeEmpty();
});

test('failed validation and missing IDs keep the submission retryable', async ({ page }) => {
    let attempt = 0;
    await page.route('**/api/responses', route => {
        attempt++;
        if (attempt === 1) return route.fulfill({ status: 503, json: { error: 'Validation unavailable. Please try again.' } });
        return route.fulfill({ status: 201, json: attempt === 2 ? {} : { id: 712 } });
    });
    await page.goto('/examples/validate-response');
    await page.getByRole('button', { name: 'Use sample answers', exact: true }).click();
    await page.getByRole('button', { name: 'Submit claim', exact: true }).click();
    await expect(page.getByRole('heading', { name: "Your claim hasn't been saved.", exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Try again', exact: true }).click();
    await expect(page.locator('[data-show="validation-status"]')).toContainText("didn't confirm a saved response");
    await page.getByRole('button', { name: 'Try again', exact: true }).click();
    await expect(page.getByText('Response ID: 712', { exact: true })).toBeVisible();
    expect(attempt).toBe(3);
});
