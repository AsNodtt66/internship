import { expect, test } from '@playwright/test';

test.describe('visual regression: stable public pages', () => {
  test.beforeEach(async ({ browserName }) => {
    test.skip(browserName !== 'chromium', 'Visual baselines are maintained for Chromium only.');
  });

  test.use({
    viewport: { width: 1440, height: 1000 },
    deviceScaleFactor: 1,
    colorScheme: 'light',
    locale: 'id-ID',
    timezoneId: 'Asia/Jakarta',
    reducedMotion: 'reduce',
  });

  async function freezeMotion(page) {
    await page.addStyleTag({
      content: `
        *, *::before, *::after {
          animation: none !important;
          caret-color: transparent !important;
          scroll-behavior: auto !important;
          transition: none !important;
        }
        .rise {
          opacity: 1 !important;
          transform: none !important;
        }
      `,
    });
  }

  test('landing page matches the approved desktop rendering', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('main')).toBeVisible();
    await freezeMotion(page);

    await expect(page).toHaveScreenshot('landing-desktop.png', {
      fullPage: true,
      mask: [page.locator('footer p')],
      maskColor: '#0E2C4B',
    });
  });

  test('participant login matches the approved desktop rendering', async ({ page }) => {
    await page.goto('/peserta/login');
    await expect(page.locator('form')).toBeVisible();
    await freezeMotion(page);

    await expect(page).toHaveScreenshot('participant-login-desktop.png', {
      fullPage: true,
      // Native font rasterisation differs slightly between Windows and Linux CI.
      maxDiffPixelRatio: 0.015,
    });
  });
});
