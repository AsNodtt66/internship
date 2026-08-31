import { expect } from '@playwright/test';

export async function login(page, { panel, login, password }) {
  const loginUrl = panel === 'peserta' ? '/peserta/login' : '/admin/login';

  await page.goto(loginUrl);
  await page.locator('input[name="data.email"]').fill(login);
  await page.locator('input[name="data.password"]').fill(password);
  await page.locator('button[type="submit"]').click();

  await expect(page).not.toHaveURL(new RegExp(`${loginUrl.replace('/', '\\/')}$`));
}
