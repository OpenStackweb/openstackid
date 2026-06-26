import { test as base } from '@playwright/test';
import { LoginPage } from '../pages/LoginPage';
import { RegisterPage } from '../pages/RegisterPage';

type E2EFixtures = {
  loginPage: LoginPage;
  registerPage: RegisterPage;
  authenticatedPage: LoginPage;
};

export const test = base.extend<E2EFixtures>({
  loginPage: async ({ page }, use) => {
    await use(new LoginPage(page));
  },

  registerPage: async ({ page }, use) => {
    await use(new RegisterPage(page));
  },

  // Pre-authenticated session using the default seeded admin account
  authenticatedPage: async ({ page }, use) => {
    const loginPage = new LoginPage(page);
    await loginPage.login(
      process.env.TEST_USER_EMAIL || 'test@test.com',
      process.env.TEST_USER_PASSWORD || '1Qaz2wsx!'
    );
    await use(loginPage);
  },
});

export { expect } from '@playwright/test';
