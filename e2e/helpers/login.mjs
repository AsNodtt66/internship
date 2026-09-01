import { expect } from '@playwright/test';

export async function login(page, { panel, login, password }) {
  const loginUrl = panel === 'peserta' ? '/peserta/login' : '/admin/login';

  await page.goto(loginUrl);
  await page.getByLabel(/Email atau NIP|Email address/).fill(login);
  await page.getByRole('textbox', { name: /^Password/ }).fill(password);
  await page.getByRole('button', { name: 'Sign in' }).click();

  await expect(page).not.toHaveURL(new RegExp(`${loginUrl.replace('/', '\\/')}$`));
}
