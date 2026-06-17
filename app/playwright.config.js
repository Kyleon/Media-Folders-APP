import { defineConfig, devices } from '@playwright/test';

/**
 * Smoke tests E2E de la PWA.
 *
 * Por defecto apuntan al WP local. Para correr contra otro entorno:
 *   PWA_URL=http://localhost:5173 WP_URL=https://yezraelperez.es npx playwright test
 *
 * Las credenciales se leen de variables de entorno para no commitearlas:
 *   PWA_TEST_USER, PWA_TEST_PASSWORD
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: 'list',
  use: {
    baseURL: process.env.PWA_URL || 'http://localhost:5173',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
  webServer: process.env.PWA_URL ? undefined : {
    command: 'npm run dev',
    url: 'http://localhost:5173',
    reuseExistingServer: !process.env.CI,
    timeout: 60_000,
  },
});
