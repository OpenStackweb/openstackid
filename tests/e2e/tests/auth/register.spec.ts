import { test, expect } from '../../fixtures';

test.describe('Registration flow', () => {
  test('shows registration form', async ({ registerPage }) => {
    await registerPage.goto();
    await expect(registerPage.firstNameInput).toBeVisible();
    await expect(registerPage.emailInput).toBeVisible();
  });

  test('shows validation errors on empty submit', async ({ registerPage, page }) => {
    await registerPage.goto();
    await registerPage.submitButton.click();
    await expect(registerPage.errorContainer).toBeVisible();
  });

  test('shows error on duplicate email', async ({ registerPage }) => {
    await registerPage.register({
      firstName: 'Test',
      lastName: 'User',
      email: 'test@test.com', // already seeded
      password: 'TestPass123!',
      country: 'US',
    });
    // Duplicate email: server redirects back and React shows a SweetAlert2 dialog
    await expect(registerPage.swalPopup).toBeVisible();
  });
});
