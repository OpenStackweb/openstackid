import { type Page, type Locator } from '@playwright/test';

export class RegisterPage {
  readonly page: Page;
  readonly firstNameInput: Locator;
  readonly lastNameInput: Locator;
  readonly emailInput: Locator;
  readonly passwordInput: Locator;
  readonly passwordConfirmInput: Locator;
  readonly codeOfConductCheckbox: Locator;
  readonly submitButton: Locator;
  // MUI FormHelperText error messages (not CSS-module classes, so not hashed)
  readonly errorContainer: Locator;
  // SweetAlert2 popup shown for server-side errors (e.g. duplicate email)
  readonly swalPopup: Locator;

  constructor(page: Page) {
    this.page = page;
    this.firstNameInput = page.locator('[name="first_name"]');
    this.lastNameInput = page.locator('[name="last_name"]');
    this.emailInput = page.locator('[name="email"]');
    this.passwordInput = page.locator('[name="password"]');
    this.passwordConfirmInput = page.locator('[name="password_confirmation"]');
    this.codeOfConductCheckbox = page.locator('[name="agree_code_of_conduct"]');
    this.submitButton = page.locator('button[type="submit"]');
    this.errorContainer = page.locator('p.MuiFormHelperText-root.Mui-error').first();
    this.swalPopup = page.locator('.swal2-popup');
  }

  async goto() {
    await this.page.goto('/auth/register');
  }

  // MUI Select does not render a native <select>; click the visible button then the option.
  async selectCountry(countryCode: string) {
    await this.page.getByRole('button', { name: 'Select a country' }).click();
    await this.page.locator(`[data-value="${countryCode}"]`).click();
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
    await this.selectCountry(data.country);
    await this.codeOfConductCheckbox.check();
    await this.submitButton.click();
  }
}
