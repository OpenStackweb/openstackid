import { type Page, type Locator } from '@playwright/test';

export class RegisterPage {
  readonly page: Page;
  readonly firstNameInput: Locator;
  readonly lastNameInput: Locator;
  readonly emailInput: Locator;
  readonly passwordInput: Locator;
  readonly passwordConfirmInput: Locator;
  readonly countrySelect: Locator;
  readonly codeOfConductCheckbox: Locator;
  readonly submitButton: Locator;
  readonly errorContainer: Locator;

  constructor(page: Page) {
    this.page = page;
    this.firstNameInput = page.locator('[name="first_name"]');
    this.lastNameInput = page.locator('[name="last_name"]');
    this.emailInput = page.locator('[name="email"]');
    this.passwordInput = page.locator('[name="password"]');
    this.passwordConfirmInput = page.locator('[name="password_confirmation"]');
    this.countrySelect = page.locator('[name="country_iso_code"]');
    this.codeOfConductCheckbox = page.locator('[name="agree_code_of_conduct"]');
    this.submitButton = page.locator('button[type="submit"]');
    this.errorContainer = page.locator('[class*="error"]');
  }

  async goto() {
    await this.page.goto('/auth/register');
  }

  async register(data: {
    firstName: string;
    lastName: string;
    email: string;
    password: string;
    country: string;
  }) {
    await this.goto();
    await this.firstNameInput.fill(data.firstName);
    await this.lastNameInput.fill(data.lastName);
    await this.emailInput.fill(data.email);
    await this.passwordInput.fill(data.password);
    await this.passwordConfirmInput.fill(data.password);
    await this.countrySelect.selectOption(data.country);
    await this.codeOfConductCheckbox.check();
    await this.submitButton.click();
  }
}
