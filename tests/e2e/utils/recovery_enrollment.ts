import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';

// Same trailing-wildcard reasoning as VERIFY_URL in login-mfa-flow.spec.ts.
export const ENROLL_URL      = '**/admin/api/v1/users/me/recovery-codes/enroll**';
export const ACKNOWLEDGE_URL = '**/admin/api/v1/users/me/recovery-codes/acknowledge**';

/**
 * A user who requires 2FA and has no usable batch of recovery codes gets the
 * non-dismissable "Save Your Recovery Codes" modal right after the challenge
 * (SDS idp-mfa.md §4.10.1), and the page navigates only once the batch is
 * acknowledged. Every MFA-enforced account seeded by CI starts without codes,
 * so any spec that completes a real challenge has to confirm the batch.
 *
 * The acknowledge button stays disabled for a few seconds on purpose (the user
 * is meant to read the codes first), hence the generous timeouts.
 *
 * The page navigates as soon as the acknowledgment succeeds, which evicts the
 * response body from the browser; the body is captured through a route instead.
 *
 * The login page navigates once the batch is acknowledged (profile panel: it does not). Pass
 * `expectNavigation` there and this waits for that navigation to finish, so a `page.goto`
 * issued right after cannot race the page's own navigation.
 *
 * @returns the acknowledge response status and JSON body, so callers can assert on them.
 */
export async function acknowledgeRecoveryCodesModal(
  page: Page,
  { expectNavigation = false }: { expectNavigation?: boolean } = {},
): Promise<{ status: number; body: any }> {
  await expect(page.locator('[data-testid="recovery-code-modal"]')).toBeVisible({ timeout: 15000 });

  let captured: { status: number; body: any } | undefined;
  await page.route(ACKNOWLEDGE_URL, async (route) => {
    const response = await route.fetch();
    captured = { status: response.status(), body: await response.json().catch(() => null) };
    await route.fulfill({ response });
  });

  const acknowledge = page.locator('[data-testid="acknowledge-codes-button"]');
  await expect(acknowledge).toBeEnabled({ timeout: 15000 });

  // Registered before the click: the navigation starts as soon as the response is handled.
  const navigated = expectNavigation ? page.waitForEvent('load', { timeout: 15000 }) : null;
  await acknowledge.click();

  await expect.poll(() => captured, { timeout: 15000 }).toBeTruthy();
  if (navigated) await navigated;
  return captured!;
}
