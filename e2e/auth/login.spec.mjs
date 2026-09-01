import { expect, test } from '@playwright/test';
import { runtimeFixture } from '../helpers/runtime.mjs';

test('admin login rejects invalid credentials', async ({ page }) => {
  await page.goto('/admin/login');
  await page.getByLabel(/email atau nip/i).fill('nobody@example.test');
  await page.getByRole('textbox', { name: /^password/i }).fill('WrongPassword!');
  await page.getByRole('button', { name: 'Sign in' }).click();
  await expect(page).toHaveURL(/\/admin\/login/);
});

test('inactive account cannot enter admin panel', async ({ page }) => {
  const fixture = runtimeFixture();
  await page.goto('/admin/login');
  await page.getByLabel(/email atau nip/i).fill(fixture.users.inactive.email);
  await page.getByRole('textbox', { name: /^password/i }).fill(fixture.password);
  await page.getByRole('button', { name: 'Sign in' }).click();
  await expect(page).toHaveURL(/\/admin\/login/);
});
