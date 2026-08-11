import { test, expect } from '../../fixtures';
import type { Page } from '@playwright/test';

// URL patterns — match both Docker (http://nginx/...) and local (http://localhost:8001/...).
// Trailing '**' is required: base_actions.js's postRawRequest() appends every
// param onto the URL as a query string (in addition to the body), so the real
// request is "<path>?otp_value=...&method=..." - a glob without the trailing
// wildcard requires an exact end-of-string match and silently never fires.
const VERIFY_URL   = '**/auth/login/2fa/verify**';
const RESEND_URL   = '**/auth/login/2fa/resend**';
const RECOVERY_URL = '**/auth/login/2fa/recovery**';
const CANCEL_URL   = '**/auth/login/cancel**';

// Each TS-* test gets its own MFA-enforced super-admin (mfa-ts-NNN@test.com,
// seeded by CI via idp:create-super-admin - see pull_request_frontend_tests.yml).
// A real login issues a real OTP challenge and counts against that user's own
// two_factor.rate_limit.max_otp_requests window, so sharing one fixed account
// across all 8 tests would exhaust the limit well before the suite finishes.
const MFA_USER_PASSWORD = '1Qaz2wsx!';

// Recovery codes are generated as 8 chars from [A-Z0-9] and shown as XXXX-XXXX
// (RecoveryCodeService::regenerateCodesForUser), but hashed without the dash.
const RECOVERY_CODE_AS_DISPLAYED = 'ABCD-1234';
const RECOVERY_CODE_NORMALIZED   = 'ABCD1234';

function mfaUserEmailFor(testTitle: string): string {
  const match = testTitle.match(/TS-(\d+)/);
  if (!match) {
    throw new Error(`Test title "${testTitle}" is missing the TS-NNN prefix used to pick its MFA user`);
  }
  return `mfa-ts-${match[1]}@test.com`;
}

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
   * Shared setup: the password step submits as a native form POST (server
   * redirect + session state, not an XHR React can intercept - see
   * login.js's onAuthenticate()), so getting to the MFA step means a real
   * login against a real MFA-enforced account, not a mocked response.
   */
  test.beforeEach(async ({ loginPage }, testInfo) => {
    await loginPage.goto();
    await loginPage.fillEmail(mfaUserEmailFor(testInfo.title));
    await loginPage.fillPassword(MFA_USER_PASSWORD);
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
      // onVerify2FA() always does `window.location.href = response.redirect_url
      // || window.location.href` on success, so even this empty-body mock
      // occasionally triggers a real same-page navigation; give the assertion
      // room to survive that reload instead of racing a tight 1s window.
      await expect(loginPage.errorLabel).not.toBeVisible({ timeout: 5000 });
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

      // resetToPasswordFlow() keeps the already-verified identity and sets
      // authFlow back to FLOW.PASSWORD - it does not clear user_name/user_verified,
      // so cancel returns to the password step, not all the way to email entry.
      await expect(loginPage.twoFactorForm).not.toBeVisible();
      await expect(loginPage.passwordForm).toBeVisible();
    });

  // TS-005 ─────────────────────────────────────────────────────────────────
  test('TS-005: use recovery code — recovery form shown and normalized code posted',
    async ({ loginPage, page }) => {
      await page.route(RECOVERY_URL, route =>
        route.fulfill({ status: 200, contentType: 'application/json', body: '{}' })
      );

      await loginPage.useRecoveryLink.click();
      await expect(loginPage.recoveryForm).toBeVisible();
      await expect(loginPage.twoFactorForm).not.toBeVisible();

      // Typed exactly as the code is displayed to the user (XXXX-XXXX); the
      // separator is presentational only and must never reach the endpoint.
      await loginPage.recoveryCodeInput.fill(RECOVERY_CODE_AS_DISPLAYED);
      await expect(loginPage.recoveryCodeInput).toHaveValue(RECOVERY_CODE_NORMALIZED);

      const [request] = await Promise.all([
        page.waitForRequest(RECOVERY_URL),
        loginPage.verifyButton.click(),
      ]);

      // postRawRequest() mirrors every param onto the query string, so the
      // normalized value is assertable straight off the request URL.
      expect(new URL(request.url()).searchParams.get('recovery_code'))
        .toBe(RECOVERY_CODE_NORMALIZED);
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

      // resetToPasswordFlow() keeps the already-verified identity and sets
      // authFlow back to FLOW.PASSWORD - it does not clear user_name/user_verified,
      // so an expired session returns to the password step, not email entry.
      await expect(loginPage.twoFactorForm).not.toBeVisible();
      await expect(loginPage.passwordForm).toBeVisible();

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

  // TS-009 ─────────────────────────────────────────────────────────────────
  test('TS-009: back to verification code — OTP mode restored with a clean recovery field',
    async ({ loginPage }) => {
      await loginPage.useRecoveryLink.click();
      await expect(loginPage.recoveryForm).toBeVisible();

      await loginPage.recoveryCodeInput.fill(RECOVERY_CODE_NORMALIZED);
      await loginPage.backToOtpLink.click();

      // The OTP flow must be intact — same form, still able to submit a code.
      await expect(loginPage.twoFactorForm).toBeVisible();
      await expect(loginPage.recoveryForm).not.toBeVisible();
      await expect(loginPage.passwordForm).not.toBeVisible();

      // Re-entering recovery mode must not resurrect the abandoned code.
      await loginPage.useRecoveryLink.click();
      await expect(loginPage.recoveryCodeInput).toHaveValue('');
    });

  // TS-010 ─────────────────────────────────────────────────────────────────
  test('TS-010: invalid/used recovery code — inline error, still in the recovery form',
    async ({ loginPage, page }) => {
      // A used code fails exactly like an unknown one: the backend answers
      // mfa_invalid_recovery for both (AbstractMFAChallengeStrategy).
      await page.route(RECOVERY_URL, route =>
        route.fulfill({
          status: 401,
          contentType: 'application/json',
          body: JSON.stringify({ error_code: 'mfa_invalid_recovery' }),
        })
      );

      await loginPage.useRecoveryLink.click();
      await loginPage.recoveryCodeInput.fill(RECOVERY_CODE_NORMALIZED);
      await loginPage.verifyButton.click();

      await expect(loginPage.errorLabel).toBeVisible();
      await expect(loginPage.errorLabel).toContainText('Invalid recovery code');
      // The MFA flow must not be abandoned on a bad code.
      await expect(loginPage.recoveryForm).toBeVisible();
      await expect(loginPage.passwordForm).not.toBeVisible();
    });

  // TS-011 ─────────────────────────────────────────────────────────────────
  test('TS-011: recovery rate limit — 429 inline error shown',
    async ({ loginPage, page }) => {
      await page.route(RECOVERY_URL, route =>
        route.fulfill({
          status: 429,
          contentType: 'application/json',
          body: JSON.stringify({
            error_code: 'mfa_rate_limit',
            error_message: 'Too many attempts. Please try again later.',
          }),
        })
      );

      await loginPage.useRecoveryLink.click();
      await loginPage.recoveryCodeInput.fill(RECOVERY_CODE_NORMALIZED);
      await loginPage.verifyButton.click();

      await expect(loginPage.errorLabel).toBeVisible();
      await expect(loginPage.errorLabel).toContainText('Too many attempts');
      await expect(loginPage.recoveryForm).toBeVisible();
    });

  // TS-012 ─────────────────────────────────────────────────────────────────
  test('TS-012: recovery session expired — back to password form with warning snackbar',
    async ({ loginPage, page }) => {
      await page.route(RECOVERY_URL, route =>
        route.fulfill({
          status: 401,
          contentType: 'application/json',
          body: JSON.stringify({ error_code: 'mfa_session_expired' }),
        })
      );
      // resetToPasswordFlow() fires cancelLogin() in the background; absorb it.
      await page.route(CANCEL_URL, route =>
        route.fulfill({ status: 200, contentType: 'application/json', body: '{}' })
      );

      await loginPage.useRecoveryLink.click();
      await loginPage.recoveryCodeInput.fill(RECOVERY_CODE_NORMALIZED);
      await loginPage.verifyButton.click();

      await expect(loginPage.recoveryForm).not.toBeVisible();
      await expect(loginPage.passwordForm).toBeVisible();

      const snackbar = page.locator('[role="alert"]');
      await expect(snackbar).toBeVisible();
      await expect(snackbar).toContainText('session has expired');
    });
});
