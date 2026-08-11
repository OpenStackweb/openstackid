import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './tests/e2e/tests',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? parseInt(process.env.PLAYWRIGHT_WORKERS ?? '1') : undefined,
  reporter: [['html', { outputFolder: 'tests/e2e/report' }]],
  use: {
    baseURL: process.env.APP_URL || 'http://localhost:8001',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
