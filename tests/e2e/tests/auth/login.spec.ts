import { test, expect } from '../../fixtures';

test.describe('Login flow', () => {
  test('shows login page', async ({ loginPage }) => {
    await loginPage.goto();
    await expect(loginPage.emailInput).toBeVisible();
  });

  test('advances to password step after valid email', async ({ loginPage }) => {
    await loginPage.goto();
    await loginPage.fillEmail('test@test.com');
    await expect(loginPage.passwordForm).toBeVisible();
  });

  test('shows error on invalid credentials', async ({ loginPage }) => {
    await loginPage.goto();
    await loginPage.fillEmail('test@test.com');
    await loginPage.fillPassword('wrongpassword');
    // Wrong password: server redirects back to login; the password step disappears
    await expect(loginPage.passwordForm).not.toBeVisible();
  });

  test('redirects to home after successful login', async ({ loginPage, page }) => {
    // Uses e2e@test.com — a plain user with no group memberships, so it is not
    // subject to the MFA enforcement applied to super-admins and admin groups
    // (see config/two_factor.php → enforced_groups). After correct credentials
    // the server issues a 302 to the profile page; postRawRequestFull captures
    // responseURL and sets window.location.href, navigating away from /auth/login.
    await loginPage.login('e2e@test.com', '1Qaz2wsx!');
    await expect(page).not.toHaveURL(/\/auth\/login/);
  });

  test('email step rejects unknown email', async ({ loginPage }) => {
    await loginPage.goto();
    await loginPage.fillEmail('nonexistent@example.com');
    // Unknown email: Continue button hides, account-creation options appear; no password step
    await expect(loginPage.emailSubmitButton).not.toBeVisible();
    await expect(loginPage.passwordForm).not.toBeVisible();
  });
});
