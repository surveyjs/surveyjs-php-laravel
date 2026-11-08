import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const sample = JSON.parse(readFileSync(new URL('../../shared/definitions/creator-load-save.json', import.meta.url), 'utf8'));

test('reports blocked errors, accepted warnings, and the raw submitted schema', async ({ page }) => {
    let saved = sample;
    await page.route('**/api/forms/support-lint', route => {
        if (route.request().method() === 'GET') return route.fulfill({ json: { definition: saved } });
        const definition = route.request().postDataJSON();
        const elements = definition.elements ?? definition.pages?.[0]?.elements ?? [];
        if (elements[0]?.visibleIf) return route.fulfill({ status: 422, json: { errors: [{ ruleId: 'reference/unknown', path: 'elements[0].visibleIf', message: 'Unknown question: nosuch' }], warnings: [] } });
        saved = definition;
        return elements[0]?.someproperty
            ? route.fulfill({ json: { warnings: [{ ruleId: 'property/unknown', path: 'elements[0].someproperty', message: 'Unknown property: someproperty' }] } })
            : route.fulfill({ status: 204 });
    });
    await page.goto('/examples/lint-definition');
    await page.getByRole('button', { name: 'An unknown variable in an expression', exact: true }).click();
    await expect(page.locator('[data-show="lint-status"]')).toContainText('Schema not saved');
    await expect(page.locator('#lint-findings')).toContainText('Error');
    await expect(page.locator('#lint-findings')).toContainText('elements[0].visibleIf');
    expect(saved).toEqual(sample);
    await page.locator('summary').filter({ hasText: /^Submitted schema$/ }).click();
    await expect(page.locator('[data-show="lint-payload"]')).toContainText('{nosuch} = 1');
    await page.getByRole('button', { name: 'An unknown property', exact: true }).click();
    await expect(page.locator('[data-show="lint-status"]')).toContainText('Schema saved with 1 warning');
    await expect(page.locator('#lint-findings')).toContainText('property/unknown');
    expect(saved.elements[0].someproperty).toBe(1);
    await page.getByRole('button', { name: 'A clean save', exact: true }).click();
    await expect(page.locator('[data-show="lint-status"]')).toContainText('Schema saved.');
    await expect(page.locator('#lint-report')).toBeHidden();
    expect((saved.elements ?? saved.pages[0].elements)[0]).not.toHaveProperty('someproperty');
});

test('load failures are retryable and samples still work with an empty editor', async ({ page }) => {
    let failLoad = true, sent;
    await page.route('**/api/forms/support-lint', route => {
        if (route.request().method() === 'GET') return failLoad
            ? route.fulfill({ status: 503, json: { error: 'Load unavailable' } })
            : route.fulfill({ json: { definition: { title: 'Empty editor', elements: [] } } });
        sent = route.request().postDataJSON();
        return route.fulfill({ status: 422, json: { errors: [{ message: 'Unknown question reference' }] } });
    });
    await page.goto('/examples/lint-definition');
    await expect(page.locator('#creator')).toBeHidden();
    failLoad = false;
    await page.getByRole('button', { name: 'Retry loading', exact: true }).click();
    await page.getByRole('button', { name: 'An unknown variable in an expression', exact: true }).click();
    await expect(page.locator('#lint-findings')).toContainText('Unknown question reference');
    expect(sent.elements[0].visibleIf).toBe('{nosuch} = 1');
});

test('a pending check blocks duplicate actions and network failure keeps edits retryable', async ({ page }) => {
    const pending = Promise.withResolvers();
    let writes = 0, fail = true;
    await page.route('**/api/forms/support-lint', async route => {
        if (route.request().method() === 'GET') return route.fulfill({ json: { definition: sample } });
        writes++;
        await pending.promise;
        return fail ? route.abort('connectionfailed') : route.fulfill({ status: 204 });
    });
    await page.goto('/examples/lint-definition');
    const clean = page.getByRole('button', { name: 'A clean save', exact: true });
    await clean.click();
    await expect(clean).toBeDisabled();
    await expect(page.locator('#creator')).toHaveAttribute('inert', '');
    pending.resolve();
    await expect(page.locator('[data-show="lint-status"]')).toContainText("Couldn't save the form schema");
    fail = false;
    await clean.click();
    await expect(page.locator('[data-show="lint-status"]')).toContainText('Schema saved.');
    expect(writes).toBe(2);
});
