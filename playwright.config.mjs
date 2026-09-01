import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8000';
const isCI = Boolean(process.env.CI);

export default defineConfig({
  testDir: './e2e',
  timeout: 30_000,
  expect: { timeout: 7_500 },
  fullyParallel: false,
  forbidOnly: isCI,
  failOnFlakyTests: true,
  retries: isCI ? 2 : 0,
  workers: isCI ? 1 : undefined,
  // Visual baselines are approved only for the Chromium project. Keep their
  // path independent from the host OS so Windows and Linux compare the same file.
  snapshotPathTemplate: '__screenshots__{/projectName}/{testFilePath}/{arg}{ext}',
  outputDir: 'test-results/playwright-artifacts',
  reporter: [
    ['list'],
    ['junit', { outputFile: 'test-results/playwright-junit.xml' }],
    ['html', { outputFolder: 'playwright-report', open: 'never' }],
  ],
  use: {
    baseURL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  webServer: process.env.PLAYWRIGHT_EXTERNAL_SERVER === '1' ? undefined : {
    command: 'php artisan serve --env=testing --host=127.0.0.1 --port=8000',
    url: `${baseURL}/up`,
    reuseExistingServer: !isCI,
    timeout: 120_000,
    stdout: 'pipe',
    stderr: 'pipe',
  },
  projects: [
    {
      name: 'setup',
      testMatch: /.*\.setup\.mjs/,
    },
    {
      name: 'chromium',
      testIgnore: /.*\.setup\.mjs/,
      use: { ...devices['Desktop Chrome'] },
      dependencies: ['setup'],
    },
    {
      name: 'firefox',
      testIgnore: /.*\.setup\.mjs/,
      use: { ...devices['Desktop Firefox'] },
      dependencies: ['setup'],
    },
    {
      name: 'webkit',
      testIgnore: /.*\.setup\.mjs/,
      use: { ...devices['Desktop Safari'] },
      dependencies: ['setup'],
    },
    {
      name: 'mobile-chrome',
      testIgnore: /.*\.setup\.mjs/,
      use: { ...devices['Pixel 5'] },
      dependencies: ['setup'],
    },
  ],
});
