import { test, expect } from '../../fixtures';
import type { Page, Route } from '@playwright/test';

// URL patterns — match both Docker (http://nginx/...) and local (http://localhost:8001/...).
const LOGIN_POST   = '**/auth/login';
const VERIFY_URL   = '**/auth/login/2fa/verify';
const RESEND_URL   = '**/auth/login/2fa/resend';
const RECOVERY_URL = '**/auth/login/2fa/recovery';
const CANCEL_URL   = '**/auth/login/cancel';

/** Server response shape when password auth succeeds for an MFA-enrolled user. */
const MFA_CHALLENGE = { error_code: 'mfa_required', otp_length: 6, otp_lifetime: 300 };

/**
 * Fill the react-otp-input digit boxes inside the two-factor form.
 * Clicks the first input so focus is guaranteed, then types the code one
 * character at a time — the library auto-advances focus after each digit.
 */
async function fillOtp(page: Page, code: string): Promise<void> {
  await page.locator('[data-testid="two-factor-form"] input[type="tel"]').first().click();
  await page.keyboard.type(code);
}

// ─────────────────────────────────────────────────────────────────────────────

test.describe('MFA Login Flow', () => {

  /**
   * Shared setup: intercept the password POST once, return an MFA challenge,
   * and wait for React to render the two-factor form.
   * This lets every test below start from the MFA step without needing a
   * real MFA-enrolled account in the database.
   */
  test.beforeEach(async ({ loginPage, page }) => {
    const handler = async (route: Route) => {
      if (route.request().method() === 'POST') {
        // Fulfill first, then unroute — unrouting the handler while its own
        // route is still unresolved makes Playwright auto-resolve it, so a
        // fulfill() called after unroute() throws "Route is already handled".
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify(MFA_CHALLENGE),
        });
        await page.unroute(LOGIN_POST, handler);
      } else {
        await route.continue();
      }
    };
    await page.route(LOGIN_POST, handler);

    await loginPage.goto();
    await loginPage.fillEmail('test@test.com');
    await loginPage.fillPassword('any-password');
    await expect(loginPage.twoFactorForm).toBeVisible();
  });

  // TS-001 ─────────────────────────────────────────────────────────────────
  test('TS-001: password login triggers MFA challenge (flow transition)',
    async ({ loginPage }) => {
      // BeforeEach drives the full transition; just assert the resulting state.
      await expect(loginPage.twoFactorForm).toBeVisible();
      await expect(loginPage.passwordForm).not.toBeVisible();
      await expect(loginPage.verifyButton).toBeVisible();
      await expect(loginPage.resendLink).toBeVisible();
      await expect(loginPage.cancelLink).toBeVisible();
      await expect(loginPage.useRecoveryLink).toBeVisible();
    });

  // TS-002 ─────────────────────────────────────────────────────────────────
  test('TS-002: correct OTP — verify API called, no error shown',
    async ({ loginPage, page }) => {
      await page.route(VERIFY_URL, route =>
        route.fulfill({ status: 200, contentType: 'application/json', body: '{}' })
      );

      await fillOtp(page, '123456');

      // Capture the response and click atomically to avoid race conditions.
      const [response] = await Promise.all([
        page.waitForResponse(VERIFY_URL),
        loginPage.verifyButton.click(),
      ]);

      expect(response.status()).toBe(200);
      // Error label must not appear — the inline error path was not taken.
      await expect(loginPage.errorLabel).not.toBeVisible({ timeout: 1000 });
    });

  // TS-003 ─────────────────────────────────────────────────────────────────
  test('TS-003: wrong OTP — inline error displayed',
    async ({ loginPage, page }) => {
      await page.route(VERIFY_URL, route =>
        route.fulfill({
          status: 401,
          contentType: 'application/json',
          body: JSON.stringify({ error_code: 'mfa_verification_failed' }),
        })
      );

      await fillOtp(page, '000000');
      await loginPage.verifyButton.click();

      await expect(loginPage.errorLabel).toBeVisible();
      await expect(loginPage.errorLabel).toContainText(
        'Invalid or expired verification code'
      );
    });

  // TS-004 ─────────────────────────────────────────────────────────────────
  test('TS-004: cancel — back to password form',
    async ({ loginPage, page }) => {
      // cancelLogin fires a background POST; absorb it so no network error surfaces.
      await page.route(CANCEL_URL, route =>
        route.fulfill({ status: 200, contentType: 'application/json', body: '{}' })
      );

      await loginPage.cancelLink.click();

      // resetToPasswordFlow() clears user_name/user_verified, so the app returns
      // all the way to the email step — not the password step.
      await expect(loginPage.twoFactorForm).not.toBeVisible();
      await expect(loginPage.emailInput).toBeVisible();
    });

  // TS-005 ─────────────────────────────────────────────────────────────────
  test('TS-005: use recovery code — recovery form shown and API called',
    async ({ loginPage, page }) => {
      await page.route(RECOVERY_URL, route =>
        route.fulfill({ status: 200, contentType: 'application/json', body: '{}' })
      );

      await loginPage.useRecoveryLink.click();
      await expect(loginPage.recoveryForm).toBeVisible();
      await expect(loginPage.twoFactorForm).not.toBeVisible();

      await page.locator('#recovery_code').fill('ABCD-1234-EFGH-5678');

      const [response] = await Promise.all([
        page.waitForResponse(RECOVERY_URL),
        loginPage.verifyButton.click(),
      ]);
      expect(response.status()).toBe(200);
    });

  // TS-006 ─────────────────────────────────────────────────────────────────
  test('TS-006: resend OTP — success notification shown',
    async ({ loginPage, page }) => {
      await page.route(RESEND_URL, route =>
        route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ otp_length: 6, otp_lifetime: 300 }),
        })
      );

      await loginPage.resendLink.click();

      // CustomSnackbar renders as a MUI Alert with role="alert".
      const snackbar = page.locator('[role="alert"]');
      await expect(snackbar).toBeVisible();
      await expect(snackbar).toContainText('new verification code');
    });

  // TS-007 ─────────────────────────────────────────────────────────────────
  test('TS-007: MFA session expired — back to password form with warning snackbar',
    async ({ loginPage, page }) => {
      await page.route(VERIFY_URL, route =>
        route.fulfill({
          status: 401,
          contentType: 'application/json',
          body: JSON.stringify({ error_code: 'mfa_session_expired' }),
        })
      );

      await fillOtp(page, '123456');
      await loginPage.verifyButton.click();

      // resetToPasswordFlow() clears user_name/user_verified — returns to email step.
      await expect(loginPage.twoFactorForm).not.toBeVisible();
      await expect(loginPage.emailInput).toBeVisible();

      const snackbar = page.locator('[role="alert"]');
      await expect(snackbar).toBeVisible();
      await expect(snackbar).toContainText('session has expired');
    });

  // TS-008 ─────────────────────────────────────────────────────────────────
  test('TS-008: rate limit — 429 inline error shown',
    async ({ loginPage, page }) => {
      await page.route(VERIFY_URL, route =>
        route.fulfill({
          status: 429,
          contentType: 'application/json',
          body: JSON.stringify({
            error_code: 'mfa_rate_limit',
            error_message: 'Too many attempts. Please try again later.',
          }),
        })
      );

      await fillOtp(page, '123456');
      await loginPage.verifyButton.click();

      await expect(loginPage.errorLabel).toBeVisible();
      await expect(loginPage.errorLabel).toContainText('Too many attempts');
    });
});
