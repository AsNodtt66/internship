import fs from 'node:fs';
import path from 'node:path';

const fixturePath = path.resolve(process.cwd(), 'storage/framework/testing/e2e-fixtures.json');

export function runtimeFixture() {
  if (!fs.existsSync(fixturePath)) {
    throw new Error(`E2E fixture tidak ditemukan: ${fixturePath}. Jalankan TestingSeeder terlebih dahulu.`);
  }

  return JSON.parse(fs.readFileSync(fixturePath, 'utf8'));
}

export function authState(name) {
  return path.resolve(process.cwd(), 'playwright/.auth', `${name}.json`);
}
