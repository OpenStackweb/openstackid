import { type Page, type Locator } from '@playwright/test';

export class LoginPage {
  readonly page: Page;
  readonly emailInput: Locator;
  readonly passwordInput: Locator;
  // Email step: button has title="Continue" (text is ">")
  readonly emailSubmitButton: Locator;
  // Password step: button text is "Continue", type="button" (no title)
  readonly passwordSubmitButton: Locator;
  readonly rememberMeCheckbox: Locator;
  readonly errorLabel: Locator;
  readonly otpInput: Locator;
  // Password step container
  readonly passwordForm: Locator;
  // Two-factor (MFA) step
  readonly twoFactorForm: Locator;
  readonly verifyButton: Locator;
  readonly resendLink: Locator;
  readonly cancelLink: Locator;
  readonly useRecoveryLink: Locator;
  // Recovery code step
  readonly recoveryForm: Locator;

  constructor(page: Page) {
    this.page = page;
    this.emailInput = page.locator('#email');
    this.passwordInput = page.locator('#password');
    this.emailSubmitButton = page.locator('button[title="Continue"]');
    this.passwordSubmitButton = page.getByRole('button', { name: 'Continue' });
    this.rememberMeCheckbox = page.locator('#remember');
    this.errorLabel = page.locator('[data-testid="error-label"]');
    this.otpInput = page.locator('[data-testid="otp_code"]');
    this.passwordForm = page.locator('[data-testid="password-form"]');
    this.twoFactorForm = page.locator('[data-testid="two-factor-form"]');
    this.verifyButton = page.locator('[data-testid="verify-button"]');
    this.resendLink = page.locator('[data-testid="resend-link"]');
    this.cancelLink = page.locator('[data-testid="cancel-link"]');
    this.useRecoveryLink = page.locator('[data-testid="use-recovery-link"]');
    this.recoveryForm = page.locator('[data-testid="recovery-form"]');
  }

  async goto() {
    await this.page.goto('/auth/login');
  }

  async fillEmail(email: string) {
    await this.emailInput.fill(email);
    await this.emailSubmitButton.click();
  }

  async fillPassword(password: string) {
    await this.passwordInput.fill(password);
    await this.passwordSubmitButton.click();
  }

  async login(email: string, password: string) {
    await this.goto();
    await this.fillEmail(email);
    await this.fillPassword(password);
  }

  async fillOtp(code: string) {
    await this.otpInput.fill(code);
    await this.passwordSubmitButton.click();
  }
}
