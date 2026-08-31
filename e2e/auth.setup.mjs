import fs from 'node:fs';
import path from 'node:path';
import { test as setup } from '@playwright/test';
import { login } from './helpers/login.mjs';
import { authState, runtimeFixture } from './helpers/runtime.mjs';

const authDir = path.resolve(process.cwd(), 'playwright/.auth');
fs.mkdirSync(authDir, { recursive: true });

const accounts = [
  ['pic', 'admin'],
  ['gm', 'admin'],
  ['kabag', 'admin'],
  ['staff', 'admin'],
  ['kepala', 'admin'],
  ['mentor', 'admin'],
  ['peserta_a', 'peserta'],
  ['peserta_b', 'peserta'],
];

for (const [name, panel] of accounts) {
  setup(`authenticate ${name}`, async ({ page }) => {
    const fixture = runtimeFixture();
    const user = fixture.users[name];
    const identifier = name === 'mentor' ? user.nip : user.email;

    await login(page, {
      panel,
      login: identifier,
      password: fixture.password,
    });

    await page.context().storageState({ path: authState(name) });
  });
}
