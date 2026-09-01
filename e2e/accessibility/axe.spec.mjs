import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

const anonymousStorageState = { cookies: [], origins: [] };

async function expectNoSeriousOrCriticalViolations(page) {
  const results = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21aa'])
    .analyze();

  const seriousOrCritical = results.violations.filter(({ impact }) => (
    impact === 'serious' || impact === 'critical'
  ));

  expect(seriousOrCritical).toEqual([]);
}

test.describe('anonymous accessibility', () => {
  test.use({ storageState: anonymousStorageState });

  test.beforeEach(async ({ page }) => {
    // Scan settled colours, not the temporary alpha values of entrance motion.
    await page.emulateMedia({ reducedMotion: 'reduce' });
  });

  test('public landing page has no serious or critical axe violations', async ({ page }) => {
    await page.goto('/');

    await expect(page.locator('main')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Daftar Magang' })).toBeVisible();

    await expectNoSeriousOrCriticalViolations(page);
  });

  test('admin login page has no serious or critical axe violations', async ({ page }) => {
    await page.goto('/admin/login');

    await expect(page.getByRole('textbox', { name: /email atau nip/i })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Sign in' })).toBeVisible();

    await expectNoSeriousOrCriticalViolations(page);
  });
});
