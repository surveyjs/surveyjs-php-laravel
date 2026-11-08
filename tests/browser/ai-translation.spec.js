import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const definition = JSON.parse(readFileSync(new URL('../../shared/definitions/ai-translation.json', import.meta.url), 'utf8'));

test('review, localized preview, explicit save, and reload preserve translations and answer values', async ({ page }) => {
    let saved = definition, writes = 0, failSave = true;
    const requests = [];
    await page.route('**/api/forms/support-translations', route => {
        if (route.request().method() === 'GET') return route.fulfill({ json: { definition: saved } });
        writes++;
        if (failSave) return route.fulfill({ status: 503, json: { error: 'Save unavailable' } });
        saved = route.request().postDataJSON();
        return route.fulfill({ status: 204 });
    });
    await page.route('**/api/translate', route => {
        const request = route.request().postDataJSON(); requests.push(request);
        return route.fulfill({ json: request.strings.map(text => `FR ${text}`) });
    });
    await page.goto('/examples/ai-translation');
    await page.locator('#translation-language').selectOption('fr');
    await page.getByRole('button', { name: 'Translate', exact: true }).click();
    await expect(page.locator('[data-show="translation-status"]')).toContainText('Translation ready.');
    expect(writes).toBe(0);
    expect(requests[0].to).toBe('fr');
    expect(requests[0].strings).not.toContain('Workshop registration');
    await page.getByRole('button', { name: 'Preview language', exact: true }).click();
    await expect(page.getByRole('textbox', { name: /^FR Full name/ })).toBeVisible();
    await expect(page.locator('#creator .sd-title').first()).toContainText("Inscription à l'atelier");
    await page.getByRole('button', { name: 'Save translations', exact: true }).click();
    await expect(page.locator('[data-show="schema-status"]')).toContainText('Your translations remain in the editor.');
    failSave = false;
    await page.getByRole('button', { name: 'Save translations', exact: true }).click();
    await expect(page.locator('[data-show="schema-status"]')).toHaveText('All changes saved.');
    const questions = saved.elements ?? saved.pages[0].elements;
    expect(questions.find(question => question.name === 'session').choices.map(choice => choice.value)).toEqual(['morning', 'afternoon']);
    expect(saved.title.fr).toBe("Inscription à l'atelier");
    await page.reload();
    await page.locator('#translation-language').selectOption('fr');
    await page.getByRole('button', { name: 'Preview language', exact: true }).click();
    await expect(page.getByRole('textbox', { name: /^FR Full name/ })).toBeVisible();
    await page.locator('#translation-language').selectOption('en');
    await expect(page.getByRole('textbox', { name: /^Full name/ })).toBeVisible();
});

for (const failure of ['network', 'malformed result']) {
    test(`${failure} finishes translation safely and the same language can be retried`, async ({ page }) => {
        let fail = true;
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/api/forms/support-translations', route => route.fulfill({ json: { definition } }));
        await page.route('**/api/translate', route => fail
            ? failure === 'network' ? route.abort('connectionfailed') : route.fulfill({ json: [123] })
            : route.fulfill({ json: route.request().postDataJSON().strings.map(text => `FR ${text}`) }));
        await page.goto('/examples/ai-translation');
        await page.locator('#translation-language').selectOption('fr');
        await page.getByRole('button', { name: 'Translate', exact: true }).click();
        await expect(page.locator('[data-show="translation-status"]')).toContainText('Existing translations are kept.');
        await expect(page.getByRole('button', { name: 'Translate', exact: true })).toBeEnabled();
        await page.getByRole('button', { name: 'Preview language', exact: true }).click();
        await expect(page.locator('#creator .sd-title').first()).toContainText("Inscription à l'atelier");
        fail = false;
        await page.getByRole('button', { name: 'Translate', exact: true }).click();
        await expect(page.locator('[data-show="translation-status"]')).toContainText('Translation ready.');
        expect(errors).toEqual([]);
    });
}
