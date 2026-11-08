import { readFileSync } from 'node:fs';
import { expect, test } from '@playwright/test';

const definition = JSON.parse(readFileSync(new URL('../../shared/definitions/creator-load-save.json', import.meta.url), 'utf8'));
const init = (log = [], clientId = 'client-1') => ({ type: 'init', clientId, seed: definition, log });
const title = page => page.getByRole('textbox', { name: 'Survey Title', exact: true });

test('connection failure blocks the editor and reconnect loads the shared form', async ({ page }) => {
    let connections = 0;
    await page.routeWebSocket(/\/ws\/forms\/support-team/, socket => {
        if (++connections === 1) socket.close({ code: 1013 });
        else socket.send(JSON.stringify(init()));
    });
    await page.goto('/examples/edit-together');
    await expect(page.locator('#creator')).toBeHidden();
    await page.getByRole('button', { name: 'Reconnect', exact: true }).click();
    await expect(title(page)).toHaveText(definition.title);
    await expect(page.locator('[data-show="connection-status"]')).toContainText('Connected as');
});

test('edits reach another author and a late joiner, and a failed save can be retried', async ({ context }) => {
    const sockets = [], log = [];
    let failSave = true, saved;
    await context.route('**/api/forms/support-team', route => {
        if (failSave) return route.fulfill({ status: 503, json: { error: 'Save unavailable. Please try again.' } });
        saved = route.request().postDataJSON();
        return route.fulfill({ status: 204 });
    });
    await context.routeWebSocket(/\/ws\/forms\/support-team/, socket => {
        socket.send(JSON.stringify(init(log, `client-${sockets.length + 1}`)));
        sockets.push(socket);
        socket.onMessage(raw => {
            const message = JSON.parse(raw);
            if (message.type === 'append') {
                log.push(message.payload);
                for (const peer of sockets) if (peer !== socket) peer.send(JSON.stringify({ type: 'record', payload: message.payload }));
            }
        });
    });
    const first = await context.newPage(), second = await context.newPage();
    await first.goto('/examples/edit-together');
    await second.goto('/examples/edit-together');
    await expect(title(second)).toHaveText(definition.title);
    await title(first).fill('Shared support form');
    await title(first).blur();
    await expect(title(second)).toHaveText('Shared support form');
    const third = await context.newPage();
    await third.goto('/examples/edit-together');
    await expect(title(third)).toHaveText('Shared support form');
    await third.getByRole('button', { name: 'Save Survey', exact: true }).click();
    await expect(third.locator('[data-show="schema-status"]')).toHaveText('Save unavailable. Please try again.');
    failSave = false;
    await third.getByRole('button', { name: 'Try saving again', exact: true }).click();
    await expect(third.locator('[data-show="schema-status"]')).toHaveText('Form schema saved.');
    expect(saved.title).toBe('Shared support form');
});

test('disconnect preserves a downloadable copy and reconnect replaces the frozen editor', async ({ page }) => {
    let socket;
    await page.routeWebSocket(/\/ws\/forms\/support-team/, current => {
        socket = current;
        socket.send(JSON.stringify(init()));
    });
    await page.goto('/examples/edit-together');
    await title(page).fill('Keep these local edits');
    await title(page).blur();
    socket.close({ code: 1013 });
    await expect(page.locator('#creator')).toHaveAttribute('inert', '');
    const downloaded = page.waitForEvent('download');
    await page.getByRole('button', { name: 'Download local copy', exact: true }).click();
    const file = await downloaded;
    expect(JSON.parse(readFileSync(await file.path(), 'utf8')).title).toBe('Keep these local edits');
    await page.getByRole('button', { name: 'Reconnect', exact: true }).click();
    await expect(title(page)).toHaveText(definition.title);
    await expect(page.locator('#creator')).not.toHaveAttribute('inert');
});
