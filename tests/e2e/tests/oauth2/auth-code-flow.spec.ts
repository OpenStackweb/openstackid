import { test, expect } from '../../fixtures';

// These values match the seeded test client from DatabaseSeeder
const SEEDED_CLIENT_ID = 'Jiz87D8/Vcvr6fvQbH4HyNgwTlfSyQ3x.openstack.client';
const REDIRECT_URI = 'https://www.test.com/oauth2';

test.describe('OAuth2 Authorization Code Flow', () => {
  test('unauthenticated request redirects to login', async ({ page }) => {
    const params = new URLSearchParams({
      client_id: SEEDED_CLIENT_ID,
      redirect_uri: REDIRECT_URI,
      response_type: 'code',
      scope: 'profile',
    });

    await page.goto(`/oauth2/auth?${params}`);
    await expect(page).toHaveURL(/\/auth\/login/);
  });

  test('authenticated user sees consent screen', async ({ authenticatedPage, page }) => {
    const params = new URLSearchParams({
      client_id: SEEDED_CLIENT_ID,
      redirect_uri: REDIRECT_URI,
      response_type: 'code',
      scope: 'profile',
    });

    await page.goto(`/oauth2/auth?${params}`);
    // Should show consent page, not redirect back to login
    await expect(page).not.toHaveURL(/\/auth\/login/);
  });
});
