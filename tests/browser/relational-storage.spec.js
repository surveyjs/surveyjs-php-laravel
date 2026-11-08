import { expect, test } from '@playwright/test';

for (const version of ['v1', 'v2']) {
    test(`${version} maps renamed questions to stable keys and confirms only the returned record`, async ({ page }) => {
        let submitted;
        await page.route('**/api/claims', route => {
            submitted = route.request().postDataJSON();
            return route.fulfill({ status: 201, json: { id: 901 } });
        });
        await page.route('**/demo/stored/relational-storage*', route => route.fulfill({ json: {
            claims: [{ response_id: 3, customer_email: 'old@example.com', amount: 999 }, ...(submitted ? [{ response_id: 901, ...submitted.data }] : [])],
            responses: [{ id: 3, definition_version: 'v1' }, ...(submitted ? [{ id: 901, definition_version: version, data: submitted.data }] : [])],
        } }));
        await page.goto(`/examples/relational-storage?version=${version}`);
        const mapping = page.locator('#mapping-rows');
        await expect(mapping).toContainText(version === 'v1' ? 'q_email' : 'contact_email');
        await expect(mapping).toContainText(version === 'v1' ? 'q_amount' : 'claimed_total');
        await expect(mapping).toContainText('claims.customer_email');
        await page.locator('#form input[type="email"]').fill('visitor@example.com');
        await page.locator('#form input[type="number"]').fill('120.5');
        await page.getByRole('button', { name: 'Submit claim', exact: true }).click();
        await expect(page.getByText('Response ID: 901', { exact: true })).toBeVisible();
        await expect(page.locator('[data-show="mapping-status"]')).toContainText('visitor@example.com');
        await expect(page.locator('[data-show="mapping-status"]')).not.toContainText('old@example.com');
        expect(submitted).toEqual({ definitionVersion: version, data: { customer_email: 'visitor@example.com', amount: 120.5 } });
    });
}

test('a failed claim save does not confirm an older record and can be retried', async ({ page }) => {
    let fail = true;
    await page.route('**/api/claims', route => fail
        ? route.fulfill({ status: 503, json: { error: 'Unavailable' } })
        : route.fulfill({ status: 201, json: { id: 902 } }));
    await page.route('**/demo/stored/relational-storage*', route => route.fulfill({ json: {
        claims: [{ response_id: 3, customer_email: 'old@example.com', amount: 999 }],
        responses: [{ id: 3, definition_version: 'v1' }],
    } }));
    await page.goto('/examples/relational-storage');
    await page.locator('#form input[type="email"]').fill('visitor@example.com');
    await page.getByRole('button', { name: 'Submit claim', exact: true }).click();
    await expect(page.getByRole('heading', { name: "Your claim hasn't been saved." })).toBeVisible();
    await page.waitForTimeout(400);
    await expect(page.locator('#notes > li[data-kind="ok"]')).toHaveCount(0);
    await expect(page.locator('[data-show="mapping-status"]')).toBeEmpty();
    fail = false;
    await page.getByRole('button', { name: 'Try again', exact: true }).click();
    await expect(page.getByText('Response ID: 902', { exact: true })).toBeVisible();
    await expect(page.locator('[data-show="mapping-status"]')).toBeEmpty();
});
