import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const sample = JSON.parse(readFileSync(new URL('../../shared/definitions/creator-load-save.json', import.meta.url), 'utf8'));
async function title(page, value) {
    const input = page.getByRole('textbox', { name: 'Survey Title', exact: true });
    await input.fill(value);
    await input.blur();
}

test('a failed initial load offers retry before showing an editor', async ({ page }) => {
    let fail = true;
    await page.route('**/api/forms/support', route => fail
        ? route.fulfill({ status: 503, json: { error: 'Load unavailable' } })
        : route.fulfill({ json: { definition: sample } }));
    await page.goto('/examples/creator-load-save');
    await expect(page.locator('#creator')).toBeHidden();
    await expect(page.locator('[data-show="schema-status"]')).toHaveText('Load unavailable');
    fail = false;
    await page.getByRole('button', { name: 'Retry loading', exact: true }).click();
    await expect(page.getByRole('textbox', { name: 'Survey Title', exact: true })).toHaveText(sample.title);
});

test('saves are serialized and the newest edit follows an in-flight save', async ({ page }) => {
    const pending = Promise.withResolvers();
    const writes = [];
    let active = 0, maximum = 0;
    await page.route('**/api/forms/support', async route => {
        if (route.request().method() === 'GET') return route.fulfill({ json: { definition: sample } });
        writes.push(route.request().postDataJSON());
        active++; maximum = Math.max(maximum, active);
        if (writes.length === 1) await pending.promise;
        active--;
        return route.fulfill({ status: 204 });
    });
    await page.goto('/examples/creator-load-save');
    await title(page, 'First edit');
    await expect.poll(() => writes.length).toBe(1);
    await title(page, 'Newest edit');
    await page.waitForTimeout(700);
    expect(writes).toHaveLength(1);
    pending.resolve();
    await expect(page.locator('[data-show="schema-status"]')).toHaveText('All changes saved.');
    expect(writes.at(-1).title).toBe('Newest edit');
    expect(maximum).toBe(1);
});

test('opening the form waits for save confirmation and renders the saved schema', async ({ context, page }) => {
    const pending = Promise.withResolvers();
    let saved = sample;
    await context.route('**/api/forms/support', async route => {
        if (route.request().method() === 'GET') return route.fulfill({ json: { definition: saved } });
        saved = route.request().postDataJSON();
        await pending.promise;
        return route.fulfill({ status: 204 });
    });
    await page.goto('/examples/creator-load-save');
    await title(page, 'Saved preview title');
    const opened = page.waitForEvent('popup');
    await page.getByRole('link', { name: 'Open the form', exact: true }).click();
    const popup = await opened;
    expect(popup.url()).toBe('about:blank');
    pending.resolve();
    await expect(popup.locator('#creator .sd-title').first()).toContainText('Saved preview title');
});

test('save failures retain edits, retry saves them, and restore sample persists the original form', async ({ page }) => {
    let fail = true, saved = { ...sample, title: 'Previously edited form' };
    await page.route('**/api/forms/support', route => {
        if (route.request().method() === 'GET') return route.fulfill({ json: { definition: saved } });
        if (fail) return route.abort('connectionfailed');
        saved = route.request().postDataJSON();
        return route.fulfill({ status: 204 });
    });
    await page.goto('/examples/creator-load-save');
    await title(page, 'Keep these edits');
    await expect(page.locator('[data-show="schema-status"]')).toContainText('Your changes remain in this editor.');
    await expect(page.getByRole('textbox', { name: 'Survey Title', exact: true })).toHaveText('Keep these edits');
    fail = false;
    await page.getByRole('button', { name: 'Try saving again', exact: true }).click();
    await expect(page.locator('[data-show="schema-status"]')).toHaveText('All changes saved.');
    expect(saved.title).toBe('Keep these edits');
    await page.getByRole('button', { name: 'Restore sample', exact: true }).click();
    await expect(page.getByRole('textbox', { name: 'Survey Title', exact: true })).toHaveText(sample.title);
    await expect.poll(() => saved.title).toBe(sample.title);
});

test('viewer edits are clearly unsaved and restoring the stored sample is unavailable', async ({ context, page }) => {
    await context.addCookies([{ name: 'demo_user', value: 'bob', url: 'http://127.0.0.1:8000' }]);
    await page.route('**/api/forms/support', route => route.request().method() === 'GET'
        ? route.fulfill({ json: { definition: sample } })
        : route.fulfill({ status: 403, json: { error: "You don't have editor rights" } }));
    await page.goto('/examples/creator-load-save');
    await title(page, 'Unsaved viewer edit');
    await expect(page.locator('[data-show="schema-status"]')).toContainText("You don't have editor rights");
    await expect(page.getByRole('textbox', { name: 'Survey Title', exact: true })).toHaveText('Unsaved viewer edit');
    await expect(page.getByRole('button', { name: 'Restore sample', exact: true })).toBeDisabled();
});
