import { test, expect } from '../../fixtures';
import { getLatestOtp } from '../../utils/otp';
import { ENROLL_URL, acknowledgeRecoveryCodesModal } from '../../utils/recovery_enrollment';

// SDS idp-mfa.md §4.10.1: an MFA-enforced user without usable recovery codes
// gets the "Save Your Recovery Codes" modal right after the 2FA challenge and
// reaches their destination only after confirming the batch.
//
// Unlike the specs that mock the verify response, this one runs the real
// sign-in: the enroll and acknowledge calls go through the real CSRF check,
// which a unit test cannot exercise (VerifyCsrfToken is skipped there) and which
// is what breaks if the page keeps using the token it was rendered with -
// Auth::login() rotates the session's CSRF token.
//
// Seeded by CI via idp:create-super-admin in BOTH pull_request_frontend_tests.yml
// and push_frontend_tests.yml, and used by this test only: its first sign-in is
// the one that enrolls the batch.
const ENROLL_USER_EMAIL = 'mfa-enroll@test.com';
const PASSWORD = '1Qaz2wsx!';
const VERIFY_URL = '**/auth/login/2fa/verify**';

test.describe('Recovery code enrollment after the 2FA challenge', () => {

  test('shows the non-dismissable modal, then continues only after the batch is acknowledged',
    async ({ loginPage, page }) => {
      await loginPage.goto();
      await loginPage.fillEmail(ENROLL_USER_EMAIL);
      await loginPage.fillPassword(PASSWORD);
      await expect(loginPage.twoFactorForm).toBeVisible();

      const otp = getLatestOtp(ENROLL_USER_EMAIL);
      await page.locator('[data-testid="two-factor-form"] input[type="tel"]').first().click();
      await page.keyboard.type(otp);

      const [verify, enroll] = await Promise.all([
        page.waitForResponse(VERIFY_URL),
        page.waitForResponse(ENROLL_URL),
        loginPage.verifyButton.click(),
      ]);

      const verifyBody = await verify.json();
      expect(verifyBody.recovery_codes_enrollment_required).toBe(true);
      // The rotated token the page must use for the calls that follow.
      expect(typeof verifyBody.csrf_token).toBe('string');

      // Real CSRF check on the enroll call: 200 means the page sent the fresh token.
      expect(enroll.status()).toBe(200);
      const batch = await enroll.json();
      expect(batch.recovery_codes).toHaveLength(10);
      expect(Number.isInteger(batch.batch_id)).toBe(true);

      const modal = page.locator('[data-testid="recovery-code-modal"]');
      await expect(modal).toBeVisible();
      for (const code of batch.recovery_codes.slice(0, 2)) {
        await expect(modal).toContainText(code);
      }

      // The modal cannot be dismissed with Escape, and the page has not navigated away.
      await page.keyboard.press('Escape');
      await expect(modal).toBeVisible();

      const acknowledge = await acknowledgeRecoveryCodesModal(page);
      expect(acknowledge.status).toBe(200);
      const status = acknowledge.body;
      expect(status.recovery_codes_acknowledged).toBe(true);
      expect(status.recovery_codes_enrollment_required).toBe(false);
      expect(status.recovery_codes_remaining).toBe(10);
    });
});
