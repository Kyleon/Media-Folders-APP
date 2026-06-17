import { test, expect } from '@playwright/test';

/**
 * Smoke tests E2E mínimos. No requieren backend real para los primeros
 * dos casos (verifican carga del shell de la PWA). El test de login real
 * solo corre si se proveen credenciales de test:
 *   PWA_TEST_BASE=https://yezraelperez.es PWA_TEST_USER=... PWA_TEST_PASSWORD=...
 */

test('PWA shell carga sin errores de consola', async ({ page }) => {
  const errors = [];
  page.on('pageerror', (e) => errors.push(e.message));
  page.on('console', (msg) => { if (msg.type() === 'error') errors.push(msg.text()); });

  await page.goto('/');
  await expect(page).toHaveTitle(/Yezrael|YPVA|Admin/i);
  expect(errors, `Errores en consola: ${errors.join(' · ')}`).toHaveLength(0);
});

test('login muestra error con credenciales inválidas', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[type="text"], input[placeholder*="usuario"i]', 'usuario-invalido');
  await page.fill('input[type="password"]', 'password-invalido');
  await page.click('button[type="submit"], button:has-text("Entrar")');
  // Mensaje de error visible (puede ser "incorrectos", "denegado" o un código)
  await expect(page.locator('text=/incorrect|denegado|denied|sesion/i')).toBeVisible({ timeout: 8000 });
});

test.skip(
  !process.env.PWA_TEST_USER || !process.env.PWA_TEST_PASSWORD,
  'login real solo si PWA_TEST_USER y PWA_TEST_PASSWORD están definidos'
);
test('login válido lleva a /media', async ({ page }) => {
  await page.goto('/login');
  if (process.env.PWA_TEST_BASE) {
    await page.fill('input[placeholder*="https"], input[name="baseUrl"]', process.env.PWA_TEST_BASE);
  }
  await page.fill('input[type="text"], input[placeholder*="usuario"i]', process.env.PWA_TEST_USER);
  await page.fill('input[type="password"]', process.env.PWA_TEST_PASSWORD);
  await page.click('button[type="submit"], button:has-text("Entrar")');
  await expect(page).toHaveURL(/\/media|\//, { timeout: 10_000 });
});
