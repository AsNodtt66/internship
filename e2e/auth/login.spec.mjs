import { expect, test } from '@playwright/test';
import { runtimeFixture } from '../helpers/runtime.mjs';

test('admin login rejects invalid credentials', async ({ page }) => {
  await page.goto('/admin/login');
  await page.locator('input[name="data.email"]').fill('nobody@example.test');
  await page.locator('input[name="data.password"]').fill('WrongPassword!');
  await page.locator('button[type="submit"]').click();
  await expect(page).toHaveURL(/\/admin\/login/);
});

test('inactive account cannot enter admin panel', async ({ page }) => {
  const fixture = runtimeFixture();
  await page.goto('/admin/login');
  await page.locator('input[name="data.email"]').fill(fixture.users.inactive.email);
  await page.locator('input[name="data.password"]').fill(fixture.password);
  await page.locator('button[type="submit"]').click();
  await expect(page).toHaveURL(/\/admin\/login/);
});
