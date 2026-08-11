import { test as base } from '@playwright/test';
import { LoginPage } from '../pages/LoginPage';
import { RegisterPage } from '../pages/RegisterPage';

type E2EFixtures = {
  loginPage: LoginPage;
  registerPage: RegisterPage;
  authenticatedPage: LoginPage;
};

export const test = base.extend<E2EFixtures>({
  page: async ({ page }, use) => {
    // When running inside the Docker playwright container, APP_URL=http://nginx is
    // injected by docker-compose. The PHP app bakes http://localhost:8001 into the
    // page HTML (asset src attributes and window.*_ENDPOINT globals). Two problems:
    //   1. Assets at http://localhost:8001/assets/** → unreachable from the container.
    //   2. XHR to http://localhost:8001/** is cross-origin from http://nginx, so the
    //      browser omits session cookies → server returns 419 CSRF error.
    //
    // Fix A: route.continue rewrite for assets (scripts, images, CSS).
    // Fix B: addInitScript intercepts window.*_ENDPOINT assignments before they are
    //         read by React, rewriting them to http://nginx so XHR is same-origin.
    //
    // From the host (APP_URL unset or http://localhost:*), no interception is needed.
    const internalUrl = process.env.APP_URL;
    if (internalUrl && !internalUrl.includes('localhost')) {
      // Fix A: rewrite asset URLs.
      await page.route(/^http:\/\/localhost(:\d+)?\//, (route) => {
        const rewritten = route.request().url()
          .replace(/^http:\/\/localhost(:\d+)?/, internalUrl);
        route.continue({ url: rewritten });
      });

      // Fix B: intercept window.*_ENDPOINT property assignments so every XHR
      // made by React targets http://nginx (same origin), ensuring the session
      // cookie is automatically included and CSRF validation succeeds.
      const endpoints = [
        'VERIFY_ACCOUNT_ENDPOINT',
        'EMIT_OTP_ENDPOINT',
        'RESEND_VERIFICATION_EMAIL_ENDPOINT',
        'VERIFY_2FA_ENDPOINT',
        'RESEND_2FA_ENDPOINT',
        'CANCEL_LOGIN_ENDPOINT',
        'RECOVERY_2FA_ENDPOINT',
        'FORM_ACTION_ENDPOINT',
      ];
      await page.addInitScript(({ endpoints, internalUrl }) => {
        for (const key of endpoints) {
          let _val;
          Object.defineProperty(window, key, {
            configurable: true,
            enumerable: true,
            set(v) {
              _val = typeof v === 'string'
                ? v.replace(/http:\/\/localhost(:\d+)?/, internalUrl)
                : v;
            },
            get() { return _val; },
          });
        }
      }, { endpoints, internalUrl });
    }
    await use(page);
  },

  loginPage: async ({ page }, use) => {
    await use(new LoginPage(page));
  },

  registerPage: async ({ page }, use) => {
    await use(new RegisterPage(page));
  },

  // Pre-authenticated session using the raw E2E user (no group memberships,
  // so MFA is never enforced and the login completes without a 2FA challenge).
  // Override via TEST_USER_EMAIL / TEST_USER_PASSWORD env vars if needed.
  authenticatedPage: async ({ page }, use) => {
    const loginPage = new LoginPage(page);
    await loginPage.login(
      process.env.TEST_USER_EMAIL || 'e2e@test.com',
      process.env.TEST_USER_PASSWORD || '1Qaz2wsx!'
    );
    await use(loginPage);
  },
});

export { expect } from '@playwright/test';
