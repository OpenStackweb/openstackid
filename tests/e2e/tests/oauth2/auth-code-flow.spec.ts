import { test, expect } from '../../fixtures';
import { getLatestOtp } from '../../utils/otp';
import type { Page, APIRequestContext } from '@playwright/test';
import type { LoginPage } from '../../pages/LoginPage';

// Seeded by database/seeds/TestSeeder.php ('oauth2_test_app') - confidential
// web client (token_endpoint_auth_method: client_secret_basic) with 'profile'
// among its granted API scopes and this redirect_uri.
const CLIENT_ID = '.-_~87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
const CLIENT_SECRET = 'ITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhgITc/6Y5N7kOtGKhg';
const REDIRECT_URI = 'https://www.test.com/oauth2';

const VERIFY_URL = '**/auth/login/2fa/verify**';

// MFA-enforced super-admins seeded by CI (idp:create-super-admin) - one per
// test so they don't share (and race against) the same
// two_factor.rate_limit.max_otp_requests window, and so granting consent or
// trusting a device in one test doesn't change another test's starting state.
const MFA_USER_PASSWORD = '1Qaz2wsx!';
const MFA_USER_EMAIL = 'mfa-oauth2@test.com';
const MFA_USER_EMAIL_CONSENT = 'mfa-oauth2-consent@test.com';
const MFA_USER_EMAIL_TRUST = 'mfa-oauth2-trust@test.com';

function authorizeUrl(): string {
  const params = new URLSearchParams({
    client_id: CLIENT_ID,
    redirect_uri: REDIRECT_URI,
    response_type: 'code',
    scope: 'profile',
  });
  return `/oauth2/auth?${params}`;
}

/**
 * Rebuilds `absoluteUrl` (typically server-generated from config('app.url'))
 * against the browser's CURRENT origin. The IDP always builds absolute
 * redirect/action URLs from its own configured app.url, which can be a
 * genuinely different DOMAIN than the one this suite is actually running
 * against (e.g. app.url is http://localhost but APP_URL=http://nginx in the
 * docker-compose e2e profile) - cookies are domain-scoped, unlike ports, so
 * following the server's literal value would leave the session cookie behind.
 */
function sameOriginUrl(page: Page, absoluteUrl: string): string {
  const target = new URL(absoluteUrl);
  const current = new URL(page.url());
  target.protocol = current.protocol;
  target.host = current.host;
  return target.toString();
}

/** Fills email + password and waits for the real MFA challenge to appear. */
async function loginToMfaChallenge(loginPage: LoginPage, email: string): Promise<void> {
  await loginPage.fillEmail(email);
  await loginPage.fillPassword(MFA_USER_PASSWORD);
  await expect(loginPage.twoFactorForm).toBeVisible();
}

/**
 * Types `otp` into the 2FA form and submits it, then follows the resulting
 * redirect ourselves (see sameOriginUrl doc above for why the client's own
 * `window.location.href = redirect_url` can't be trusted to carry the
 * session cookie): intercept the page's own verify2FA request, replay it via
 * page.request (shares the page's cookies, so there's no race with the
 * page's own script reading the body first), then fulfill the intercepted
 * request with redirect_url nulled out so the client's fallback navigation
 * becomes a harmless same-URL no-op.
 */
async function verifyOtpAndFollowRedirect(page: Page, loginPage: LoginPage, otp: string): Promise<void> {
  let verifyPayload: { redirect_url?: string } | undefined;
  await page.route(VERIFY_URL, async (route) => {
    const req = route.request();
    const response = await page.request.fetch(req.url(), {
      method: req.method(),
      headers: req.headers(),
      data: req.postData() ?? undefined,
    });
    verifyPayload = await response.json();
    await route.fulfill({
      status: response.status(),
      contentType: 'application/json',
      body: JSON.stringify({ ...verifyPayload, redirect_url: null }),
    });
  });

  await page.locator('[data-testid="two-factor-form"] input[type="tel"]').first().click();
  await page.keyboard.type(otp);
  await loginPage.verifyButton.click();
  // The route handler above makes its OWN request to the server before
  // fulfilling this one, so this can take noticeably longer than the
  // default poll timeout - especially the first request against a
  // just-started stack.
  await expect.poll(() => verifyPayload, { timeout: 15000 }).toBeTruthy();
  await page.goto(sameOriginUrl(page, verifyPayload!.redirect_url!));
}

/**
 * Submits the consent form's Accept action and follows the resulting
 * /oauth2/auth redirect (postConsent() redirects back there, same pattern as
 * postLogin(), so the authorize endpoint can re-evaluate the request now
 * that consent was just granted). Returns the authorization code from the
 * final redirect to the client's redirect_uri.
 */
async function acceptConsentAndGetCode(page: Page): Promise<string> {
  const csrfToken = await page.locator('#_token').inputValue();
  const consentResponse = await page.request.post(
    new URL('/accounts/user/consent', page.url()).toString(),
    { form: { _token: csrfToken, trust: 'AllowOnce' }, maxRedirects: 0 }
  );
  expect(consentResponse.status()).toBe(302);
  const consentLocation = consentResponse.headers()['location'];
  expect(consentLocation).toBeTruthy();

  const authorizeResponse = await page.request.get(sameOriginUrl(page, consentLocation!), { maxRedirects: 0 });
  expect(authorizeResponse.status()).toBe(302);
  const location = authorizeResponse.headers()['location'];
  expect(location).toBeTruthy();

  const code = new URL(location!).searchParams.get('code');
  expect(code).toBeTruthy();
  return code!;
}

/** Exchanges an authorization code for an access token, exactly as a real OAuth2 client would. */
async function exchangeCodeForToken(request: APIRequestContext, code: string): Promise<void> {
  const tokenResponse = await request.post('/oauth2/token/', {
    headers: {
      Authorization: `Basic ${Buffer.from(`${CLIENT_ID}:${CLIENT_SECRET}`).toString('base64')}`,
    },
    form: { grant_type: 'authorization_code', code, redirect_uri: REDIRECT_URI },
  });

  expect(tokenResponse.status()).toBe(200);
  const tokenPayload = await tokenResponse.json();
  expect(tokenPayload.access_token).toBeTruthy();
  expect(tokenPayload.token_type).toBe('Bearer');
}

test.describe('OAuth2 Authorization Code Flow', () => {
  test('unauthenticated request redirects to login', async ({ page }) => {
    await page.goto(authorizeUrl());
    await expect(page).toHaveURL(/\/auth\/login/);
  });

  test('MFA-enforced login completes the full authorization code flow after the challenge (memento survives MFA)',
    async ({ loginPage, page, request }) => {
      // Start the OAuth2 authorization request with no session - the server
      // serializes it into the session (the "memento") and redirects to login.
      await page.goto(authorizeUrl());
      await expect(page).toHaveURL(/\/auth\/login/);

      // Real native-form login (see login-mfa-flow.spec.ts) against an
      // MFA-enforced account triggers a real challenge, not a mocked one.
      await loginToMfaChallenge(loginPage, MFA_USER_EMAIL);
      const otp = getLatestOtp(MFA_USER_EMAIL);
      await verifyOtpAndFollowRedirect(page, loginPage, otp);

      // If the memento had been dropped anywhere across the MFA detour, this
      // would land on the default post-login destination instead of the
      // consent screen for THIS specific client.
      await expect(page).toHaveURL(/\/accounts\/user\/consent/);
      await expect(page.getByText('oauth2_test_app').first()).toBeVisible();

      const code = await acceptConsentAndGetCode(page);
      await exchangeCodeForToken(request, code);
    });

  test('returning user with prior consent skips the consent screen entirely',
    async ({ loginPage, page, request }) => {
      await page.goto(authorizeUrl());
      await expect(page).toHaveURL(/\/auth\/login/);

      await loginToMfaChallenge(loginPage, MFA_USER_EMAIL_CONSENT);
      const otp = getLatestOtp(MFA_USER_EMAIL_CONSENT);
      await verifyOtpAndFollowRedirect(page, loginPage, otp);
      await expect(page).toHaveURL(/\/accounts\/user\/consent/);

      // Grant consent once - this is the baseline "first-time" experience
      // already covered by the test above, just needed here as setup.
      await acceptConsentAndGetCode(page);

      // A second authorization request for the SAME client/scope, in the
      // SAME authenticated session, must now skip the consent screen
      // entirely (InteractiveGrantType::handle()'s has_former_consent +
      // auto_approval branch) and redirect straight to the client.
      const secondAuthorize = await page.request.get(authorizeUrl(), { maxRedirects: 0 });
      expect(secondAuthorize.status()).toBe(302);
      const location = secondAuthorize.headers()['location'];
      expect(location).toBeTruthy();
      expect(location).not.toMatch(/accounts\/user\/consent/);
      expect(location).toContain(REDIRECT_URI);

      const code = new URL(location!).searchParams.get('code');
      expect(code).toBeTruthy();
      await exchangeCodeForToken(request, code!);
    });

  test('trusting the device during MFA lets a later login skip the challenge',
    async ({ loginPage, page }) => {
      // NOTE: MFACookieManager::queueDeviceTrustCookie() issues the
      // device_trust_token cookie with Secure=true. Browsers only persist
      // Secure cookies over a "potentially trustworthy origin" - real HTTPS,
      // or specifically http://localhost - so this test requires running
      // against http://localhost (host dev via `npx playwright test`, or CI -
      // see .github/workflows/*_frontend_tests.yml's APP_URL). It will not
      // observe the cookie under the docker-compose e2e profile's
      // http://nginx, which is a plain (non-localhost) HTTP origin.
      await page.goto(authorizeUrl());
      await expect(page).toHaveURL(/\/auth\/login/);

      await loginToMfaChallenge(loginPage, MFA_USER_EMAIL_TRUST);
      await page.locator('#trust_device').check();

      const otp = getLatestOtp(MFA_USER_EMAIL_TRUST);
      await verifyOtpAndFollowRedirect(page, loginPage, otp);
      await expect(page).toHaveURL(/\/accounts\/user\/consent/);

      const cookies = await page.context().cookies();
      expect(cookies.some((c) => c.name === 'device_trust_token')).toBe(true);

      // Log out and start a completely fresh authorization request - only
      // the trusted-device cookie (not the now-cleared session) should be
      // available to let this second login skip the MFA challenge.
      await page.request.get(new URL('/accounts/user/logout', page.url()).toString());
      await page.goto(authorizeUrl());
      await expect(page).toHaveURL(/\/auth\/login/);

      await loginPage.fillEmail(MFA_USER_EMAIL_TRUST);

      // The password step's native form POST redirects (on success) via
      // Redirect::action() - same config('app.url') cross-origin caveat as
      // verifyOtpAndFollowRedirect() above, so replay it the same way rather
      // than letting the browser follow the native 302 itself.
      let postLoginRedirectUrl = '';
      await page.route('**/auth/login', async (route) => {
        if (route.request().method() !== 'POST') {
          await route.continue();
          return;
        }
        const req = route.request();
        const response = await page.request.fetch(req.url(), {
          method: 'POST',
          headers: req.headers(),
          data: req.postData() ?? undefined,
          maxRedirects: 0,
        });
        postLoginRedirectUrl = response.headers()['location'] ?? '';
        await route.fulfill({ status: 200, contentType: 'text/plain', body: '' });
      });

      await loginPage.fillPassword(MFA_USER_PASSWORD);
      await expect.poll(() => postLoginRedirectUrl, { timeout: 15000 }).toBeTruthy();

      // The device is trusted, so no 2FA challenge should have been issued.
      await expect(page.locator('[data-testid="two-factor-form"]')).not.toBeVisible();

      await page.goto(sameOriginUrl(page, postLoginRedirectUrl));
      await expect(page).toHaveURL(/\/accounts\/user\/consent/);
    });
});
