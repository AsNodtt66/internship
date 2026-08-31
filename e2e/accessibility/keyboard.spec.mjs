import { expect, test } from '@playwright/test';
import { authState } from '../helpers/runtime.mjs';

test('landing skip link can receive keyboard focus', async ({ page }) => {
  await page.goto('/');
  await page.keyboard.press('Tab');
  const focused = page.locator(':focus');
  await expect(focused).toHaveAttribute('href', /^#/);
});

test.describe('participant keyboard smoke', () => {
  test.use({ storageState: authState('peserta_a') });

  test('participant dashboard contains a main content region and keyboard-focusable controls', async ({ page }) => {
    await page.goto('/peserta');
    await expect(page.locator('main, [role="main"]')).toHaveCount(1);
    await page.keyboard.press('Tab');
    await expect(page.locator(':focus')).not.toHaveCount(0);
  });
});
