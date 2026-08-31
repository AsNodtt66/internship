import { expect, test } from '@playwright/test';
import { authState, runtimeFixture } from '../helpers/runtime.mjs';

test.describe('private document owner', () => {
  test.use({ storageState: authState('peserta_a') });

  test('@critical owner can download own private document', async ({ request }) => {
    const fixture = runtimeFixture();
    const response = await request.get(`/documents/pengajuan/${fixture.pengajuans.a}/file_cv`);
    expect(response.status()).toBe(200);
    expect(response.headers()['cache-control']).toContain('no-store');
    expect(response.headers()['x-content-type-options']).toBe('nosniff');
  });
});

test.describe('private document cross-user boundary', () => {
  test.use({ storageState: authState('peserta_b') });

  test('@critical another participant cannot download the owner document', async ({ request }) => {
    const fixture = runtimeFixture();
    const response = await request.get(`/documents/pengajuan/${fixture.pengajuans.a}/file_cv`, { maxRedirects: 0 });
    expect(response.status()).not.toBe(200);
  });
});

test('anonymous user cannot download a private document', async ({ request }) => {
  const fixture = runtimeFixture();
  const response = await request.get(`/documents/pengajuan/${fixture.pengajuans.a}/file_cv`, { maxRedirects: 0 });
  expect(response.status()).not.toBe(200);
});
