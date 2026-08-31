import { expect, test } from '@playwright/test';
import { authState, runtimeFixture } from '../helpers/runtime.mjs';

for (const role of ['gm', 'kabag', 'staff', 'kepala', 'mentor']) {
  test.describe(`${role} resource boundary`, () => {
    test.use({ storageState: authState(role) });

    test(`${role} cannot open PIC-only user management by direct URL`, async ({ page }) => {
      const response = await page.goto('/admin/users');
      expect(response).not.toBeNull();
      expect(response.status()).not.toBe(200);
    });
  });
}

test.describe('PIC resource boundary', () => {
  test.use({ storageState: authState('pic') });

  test('@critical PIC can open user management', async ({ page }) => {
    const response = await page.goto('/admin/users');
    expect(response?.status()).toBe(200);
  });

  test('core role UI exposes no create route or destructive action', async ({ page }) => {
    await page.goto('/admin/roles');
    await expect(page.locator('a[href$="/admin/roles/create"]')).toHaveCount(0);
    await expect(page.getByRole('button', { name: /hapus|delete/i })).toHaveCount(0);
  });
});

test.describe('participant IDOR boundaries', () => {
  test.use({ storageState: authState('peserta_a') });

  test('@critical participant can view own pengajuan but cannot view another participant record', async ({ page }) => {
    const fixture = runtimeFixture();

    const own = await page.goto(`/peserta/pengajuans/${fixture.pengajuans.a}`);
    expect(own?.status()).toBe(200);

    const foreign = await page.goto(`/peserta/pengajuans/${fixture.pengajuans.b}`);
    expect(foreign).not.toBeNull();
    expect(foreign.status()).not.toBe(200);
  });

  test('participant cannot enter admin panel', async ({ page }) => {
    const response = await page.goto('/admin');
    expect(response).not.toBeNull();
    expect(response.status()).not.toBe(200);
  });
});
