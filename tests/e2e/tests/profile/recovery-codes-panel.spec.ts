import { test, expect } from '../../fixtures';
import type { Page } from '@playwright/test';
import { getLatestOtp } from '../../utils/otp';
import { ENROLL_URL, ACKNOWLEDGE_URL, acknowledgeRecoveryCodesModal } from '../../utils/recovery_enrollment';

// Recovery-code panel of the profile page, against the real server (SDS idp-mfa.md
// §4.10.3). jest covers every panel state in isolation; this spec proves the pieces
// work together: the real status keys of the profile view, the real CSRF check on
// enroll / regenerate / acknowledge, and that the panel lives inside the profile
// page's single <form> without ever submitting it.
//
// Seeded by CI via idp:create-super-admin in BOTH pull_request_frontend_tests.yml and
// push_frontend_tests.yml, and used by this spec only. It is ONE test made of steps,
// not several tests: the steps share the signed-in session and the account's recovery
// codes, which go from "none" to "acknowledged" (each Playwright test gets a fresh
// browser context, so separate tests would lose the session).
const PROFILE_USER_EMAIL = 'mfa-profile@test.com';
const PASSWORD = '1Qaz2wsx!';
const VERIFY_URL = '**/auth/login/2fa/verify**';
const REGENERATE_URL = '**/admin/api/v1/users/me/recovery-codes/regenerate**';
const PROFILE_PATH = '/accounts/user/profile';

/** Counts requests to a URL pattern from now on. */
function countRequests(page: Page, urlGlob: string): () => number {
  let count = 0;
  page.on('request', (req) => {
    const re = new RegExp('^' + urlGlob.replace(/[.+?^${}()|[\]\\]/g, '\\$&').replace(/\*\*/g, '.*') + '$');
    if (re.test(req.url())) count += 1;
  });
  return () => count;
}

/** The OTP row can land a moment after the 2FA form shows up: retry instead of failing. */
async function waitForOtp(email: string): Promise<string> {
  let otp = '';
  await expect.poll(() => {
    try {
      otp = getLatestOtp(email);
    } catch {
      otp = '';
    }
    return otp;
  }, { timeout: 15000 }).not.toBe('');
  return otp;
}

async function signInWithoutSavingTheCodes(page: Page, loginPage: any): Promise<void> {
  await loginPage.goto();
  await loginPage.fillEmail(PROFILE_USER_EMAIL);
  await loginPage.fillPassword(PASSWORD);
  await expect(loginPage.twoFactorForm).toBeVisible();

  // The login page would enroll and show the modal. Failing the enroll call makes it
  // continue to the destination without a batch (an enroll failure never blocks the
  // sign-in), which leaves the user with enrollment required and the window open.
  await page.route(ENROLL_URL, (route) => route.abort());

  await page.locator('[data-testid="two-factor-form"] input[type="tel"]').first().click();
  await page.keyboard.type(await waitForOtp(PROFILE_USER_EMAIL));
  await Promise.all([
    page.waitForResponse(VERIFY_URL),
    loginPage.verifyButton.click(),
  ]);
  await page.waitForURL((url) => !url.pathname.startsWith('/auth/login'), { timeout: 15000 });
  await page.unroute(ENROLL_URL);
}

test('profile recovery-codes panel, from "not set up yet" to an acknowledged batch', async ({ loginPage, page }) => {
  await signInWithoutSavingTheCodes(page, loginPage);

  await test.step('a user with no usable batch sees "not set up yet" and nothing is generated on load', async () => {
    const enrollCalls = countRequests(page, ENROLL_URL);
    await page.goto(PROFILE_PATH);

    await expect(page.getByTestId('recovery-codes-not-set-up')).toContainText('Your recovery codes are not set up yet.');
    await expect(page.getByTestId('setup-recovery-codes-button')).toBeVisible();
    // Never "10 of 10 remaining" for a batch nobody confirmed.
    await expect(page.getByTestId('recovery-codes-count')).toHaveCount(0);

    await page.reload();
    await expect(page.getByTestId('setup-recovery-codes-button')).toBeVisible();
    expect(enrollCalls(), 'enrollment must only happen when the user asks for it').toBe(0);
  });

  await test.step('"Set up recovery codes" shows a non-dismissable modal; acknowledging it completes the panel', async () => {
    // A marker that survives only while the page is not reloaded or navigated: proves the
    // panel's buttons do not submit the profile page's enclosing <form>.
    await page.evaluate(() => { (window as any).__noReload = true; });

    const [enroll] = await Promise.all([
      page.waitForResponse(ENROLL_URL),
      page.getByTestId('setup-recovery-codes-button').click(),
    ]);
    expect(enroll.status(), 'the real CSRF check accepts the profile page token').toBe(200);
    const batch = await enroll.json();
    expect(batch.recovery_codes).toHaveLength(10);

    const modal = page.getByTestId('recovery-code-modal');
    await expect(modal).toBeVisible();
    await expect(modal).toContainText(batch.recovery_codes[0]);
    await page.keyboard.press('Escape');
    await expect(modal).toBeVisible();
    // Nothing is counted until the batch is confirmed.
    await expect(page.getByTestId('recovery-codes-count')).toHaveCount(0);

    const acknowledge = await acknowledgeRecoveryCodesModal(page);
    expect(acknowledge.status).toBe(200);
    expect(acknowledge.body.recovery_codes_acknowledged).toBe(true);

    await expect(modal).toBeHidden();
    await expect(page.getByTestId('recovery-codes-count')).toContainText('Recovery Codes: 10 of 10 remaining');
    expect(await page.evaluate(() => (window as any).__noReload)).toBe(true);
  });

  await test.step('after a reload the acknowledged batch is shown and the setup action is gone', async () => {
    await page.goto(PROFILE_PATH);

    await expect(page.getByTestId('recovery-codes-count')).toContainText('Recovery Codes: 10 of 10 remaining');
    await expect(page.getByTestId('setup-recovery-codes-button')).toHaveCount(0);
    await expect(page.getByTestId('recovery-codes-not-set-up')).toHaveCount(0);
  });

  await test.step('a wrong password keeps the existing batch', async () => {
    await page.getByText('Regenerate Codes').click();
    await page.getByTestId('recovery-codes-current-password').locator('input').fill('not-the-password');
    const [regenerate] = await Promise.all([
      page.waitForResponse(REGENERATE_URL),
      page.getByTestId('confirm-regenerate-button').click(),
    ]);

    expect(regenerate.status()).toBe(412);
    await expect(page.getByTestId('recovery-code-modal')).toHaveCount(0);
    await expect(page.getByTestId('recovery-codes-count')).toContainText('Recovery Codes: 10 of 10 remaining');
  });

  await test.step('regenerating with the current password replaces the batch and needs a new acknowledgment', async () => {
    await page.goto(PROFILE_PATH);
    await page.evaluate(() => { (window as any).__noReload = true; });
    const ackCalls = countRequests(page, ACKNOWLEDGE_URL);

    await page.getByText('Regenerate Codes').click();
    await page.getByTestId('recovery-codes-current-password').locator('input').fill(PASSWORD);

    const [regenerate] = await Promise.all([
      page.waitForResponse(REGENERATE_URL),
      page.getByTestId('confirm-regenerate-button').click(),
    ]);
    expect(regenerate.status()).toBe(200);
    const batch = await regenerate.json();
    expect(batch.recovery_codes_acknowledged).toBe(false);
    expect(batch.recovery_codes_enrollment_required).toBe(true);

    await expect(page.getByTestId('recovery-code-modal')).toBeVisible();
    // The new batch is not usable yet: the panel must not claim "10 remaining".
    await expect(page.getByTestId('recovery-codes-count')).toHaveCount(0);
    expect(ackCalls()).toBe(0);

    const acknowledge = await acknowledgeRecoveryCodesModal(page);
    expect(acknowledge.status).toBe(200);
    await expect(page.getByTestId('recovery-codes-count')).toContainText('Recovery Codes: 10 of 10 remaining');
    expect(await page.evaluate(() => (window as any).__noReload)).toBe(true);
  });
});
