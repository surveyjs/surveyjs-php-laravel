import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const presets = JSON.parse(readFileSync(new URL('../../shared/seed/variable-presets.json', import.meta.url), 'utf8')).support;
async function selectPreset(page, name) {
    await page.evaluate(() => document.fonts.ready);
    const selector = page.getByRole('button', { name: /^Variable preset:/ });
    await selector.evaluate(element => element.scrollIntoView({ block: 'center' }));
    await selector.click();
    await page.getByRole('menuitemradio', { name, exact: true }).click();
}
async function editBasicPlan(page) {
    await page.getByRole('button', { name: 'Manage presets', exact: true }).click();
    await page.locator('.svc-variable-presets').getByRole('button', { name: 'Manage presets', exact: true }).first().click();
    await page.getByRole('combobox', { name: /^Customer plan/ }).press('ArrowDown');
    await page.getByRole('option', { name: 'Premium', exact: true }).click();
    await page.getByRole('button', { name: 'Apply', exact: true }).click();
}

test('basic is selected initially and switching presets changes variables without saving', async ({ page }) => {
    const writes = [];
    await page.route('**/api/variable-presets/support', route => {
        if (route.request().method() === 'PUT') writes.push(route.request().postDataJSON());
        return route.fulfill({ json: presets });
    });
    await page.goto('/examples/variable-presets');
    await expect(page.locator('[data-show="active-preset"]')).toHaveText('basic');
    await expect(page.getByRole('switch', { name: /Call me back within one hour/ })).toBeHidden();
    await selectPreset(page, 'premium');
    await expect(page.locator('[data-show="preset-variables"]')).toContainText('"customerTier": "premium"');
    await expect(page.getByRole('switch', { name: /Call me back within one hour/ })).toBeVisible();
    await selectPreset(page, 'basic');
    await expect(page.getByRole('switch', { name: /Call me back within one hour/ })).toBeHidden();
    expect(writes).toEqual([]);
});

test('edited presets retry after failure, save as a list, and restore the edited variables on reload', async ({ page }) => {
    let saved = structuredClone(presets), fail = true;
    await page.route('**/api/variable-presets/support', route => {
        if (route.request().method() === 'GET') return route.fulfill({ json: saved });
        if (fail) return route.fulfill({ status: 503, json: { error: 'Save unavailable' } });
        saved = route.request().postDataJSON();
        return route.fulfill({ status: 204 });
    });
    await page.goto('/examples/variable-presets');
    await editBasicPlan(page);
    await expect(page.locator('[data-show="preset-status"]')).toContainText('Your preset edits remain in this editor.');
    fail = false;
    await page.getByRole('button', { name: 'Try saving again', exact: true }).click();
    await expect(page.locator('[data-show="preset-status"]')).toHaveText('Presets saved.');
    expect(Array.isArray(saved)).toBe(true);
    expect(saved[0].variables.customerTier).toBe('premium');
    await page.reload();
    await expect(page.locator('[data-show="active-preset"]')).toHaveText('basic');
    await expect(page.getByRole('switch', { name: /Call me back within one hour/ })).toBeVisible();
});

test('viewer permission failures are visible and keep the edited scenario in the editor', async ({ context, page }) => {
    await context.addCookies([{ name: 'demo_user', value: 'bob', url: 'http://127.0.0.1:8000' }]);
    await page.route('**/api/variable-presets/support', route => route.request().method() === 'GET'
        ? route.fulfill({ json: presets })
        : route.fulfill({ status: 403, json: { error: "You don't have editor rights" } }));
    await page.goto('/examples/variable-presets');
    await editBasicPlan(page);
    await expect(page.locator('[data-show="preset-status"]')).toContainText("You don't have editor rights");
    await expect(page.locator('[data-show="preset-variables"]')).toContainText('premium');
});

test('legacy stored configurations load while an empty list is a normal initial state', async ({ page }) => {
    let legacy = true;
    await page.route('**/api/variable-presets/support', route => route.fulfill({ json: legacy ? { presets } : [] }));
    await page.goto('/examples/variable-presets');
    await expect(page.locator('[data-show="active-preset"]')).toHaveText('basic');
    legacy = false;
    await page.reload();
    await expect(page.locator('[data-show="preset-status"]')).toHaveText('No saved presets. Add one with Manage presets.');
    await expect(page.getByRole('button', { name: 'Retry loading', exact: true })).toBeHidden();
});
