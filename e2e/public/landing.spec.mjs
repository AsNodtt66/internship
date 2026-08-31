import { expect, test } from '@playwright/test';

test('@critical landing page is reachable and has basic accessibility landmarks', async ({ page }) => {
  const response = await page.goto('/');
  expect(response?.status()).toBe(200);
  await expect(page.locator('html')).toHaveAttribute('lang', 'id');
  await expect(page.locator('main')).toHaveCount(1);
  await expect(page.locator('a[href^="#"]').filter({ hasText: /lewati|konten/i }).first()).toBeVisible();
});

test('landing page does not overflow horizontally on the configured viewport', async ({ page }) => {
  await page.goto('/');
  const dimensions = await page.evaluate(() => ({
    scrollWidth: document.documentElement.scrollWidth,
    clientWidth: document.documentElement.clientWidth,
  }));
  expect(dimensions.scrollWidth).toBeLessThanOrEqual(dimensions.clientWidth + 2);
});
