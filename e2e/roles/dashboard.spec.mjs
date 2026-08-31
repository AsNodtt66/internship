import { expect, test } from '@playwright/test';
import { authState } from '../helpers/runtime.mjs';

const adminRoles = ['pic', 'gm', 'kabag', 'staff', 'kepala', 'mentor'];

for (const role of adminRoles) {
  test.describe(`${role} dashboard`, () => {
    test.use({ storageState: authState(role) });

    test(`@critical ${role} can enter its authorised admin panel`, async ({ page }) => {
      const response = await page.goto('/admin');
      expect(response?.status()).toBe(200);
      await expect(page).not.toHaveURL(/\/admin\/login/);
    });
  });
}

test.describe('participant dashboard', () => {
  test.use({ storageState: authState('peserta_a') });

  test('@critical participant can enter participant panel', async ({ page }) => {
    const response = await page.goto('/peserta');
    expect(response?.status()).toBe(200);
    await expect(page).not.toHaveURL(/\/peserta\/login/);
  });
});
