import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const definition = JSON.parse(readFileSync(new URL('../../shared/definitions/creator-uploads.json', import.meta.url), 'utf8'));

for (const width of [1440, 1920]) {
    test(`upload and schema-save server code fit the Code panel at ${width}px`, async ({ page }) => {
        await page.setViewportSize({ width, height: 1000 });
        await page.route('**/api/forms/support-assets', route => route.fulfill({ json: { definition } }));
        await page.goto('/examples/creator-uploads#code');
        const code = page.locator('#panel-code');
        await expect(code).toContainText("Route::post('/api/files'");
        await expect(code).toContainText("Route::put('/api/forms/{id}'");
        await code.locator('[data-definition-code] > summary').click();
        const overflow = await code.locator('pre').evaluateAll(elements => elements.map(element => ({
            width: element.clientWidth,
            scrollWidth: element.scrollWidth,
            whiteSpace: getComputedStyle(element.querySelector('code')).whiteSpace,
        })));
        expect(overflow).toHaveLength(4);
        for (const block of overflow) {
            expect(block.scrollWidth).toBeLessThanOrEqual(block.width);
            expect(block.whiteSpace).toBe('pre');
        }
    });
}
