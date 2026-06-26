import { type Page, type Locator } from '@playwright/test';

export class LoginPage {
  readonly page: Page;
  readonly emailInput: Locator;
  readonly passwordInput: Locator;
  readonly submitButton: Locator;
  readonly rememberMeCheckbox: Locator;
  readonly errorLabel: Locator;
  readonly otpInput: Locator;

  constructor(page: Page) {
    this.page = page;
    this.emailInput = page.locator('#email');
    this.passwordInput = page.locator('#password');
    this.submitButton = page.locator('button[type="submit"]');
    this.rememberMeCheckbox = page.locator('#remember');
    this.errorLabel = page.locator('[class*="error_label"]');
    this.otpInput = page.locator('[data-testid="otp_code"]');
  }

  async goto() {
    await this.page.goto('/auth/login');
  }

  async fillEmail(email: string) {
    await this.emailInput.fill(email);
    await this.submitButton.click();
  }

  async fillPassword(password: string) {
    await this.passwordInput.fill(password);
    await this.submitButton.click();
  }

  async login(email: string, password: string) {
    await this.goto();
    await this.fillEmail(email);
    await this.fillPassword(password);
  }

  async fillOtp(code: string) {
    await this.otpInput.fill(code);
    await this.submitButton.click();
  }
}
