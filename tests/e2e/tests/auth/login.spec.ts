import { test, expect } from '../../fixtures';

test.describe('Login flow', () => {
  test('shows login page', async ({ loginPage }) => {
    await loginPage.goto();
    await expect(loginPage.emailInput).toBeVisible();
  });

  test('advances to password step after valid email', async ({ loginPage }) => {
    await loginPage.goto();
    await loginPage.fillEmail('test@test.com');
    await expect(loginPage.passwordInput).toBeVisible();
  });

  test('shows error on invalid credentials', async ({ loginPage }) => {
    await loginPage.goto();
    await loginPage.fillEmail('test@test.com');
    await loginPage.fillPassword('wrongpassword');
    await expect(loginPage.errorLabel).toBeVisible();
  });

  test('redirects to home after successful login', async ({ loginPage, page }) => {
    await loginPage.login('test@test.com', '1Qaz2wsx!');
    await expect(page).not.toHaveURL(/\/auth\/login/);
  });

  test('email step rejects unknown email', async ({ loginPage }) => {
    await loginPage.goto();
    await loginPage.fillEmail('nonexistent@example.com');
    await expect(loginPage.errorLabel).toBeVisible();
  });
});
