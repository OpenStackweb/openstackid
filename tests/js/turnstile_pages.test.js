import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';

jest.mock('@marsidev/react-turnstile', () => require('./helpers/turnstile_mock'));
jest.mock('react-password-strength-bar', () => () => null);

import { mockTurnstile } from './helpers/turnstile_mock';
import { CAPTCHA_MESSAGES } from '../../resources/js/components/turnstile_captcha';
import { SignUpPage } from '../../resources/js/signup/signup';
import { ForgotPasswordPage } from '../../resources/js/forgot_password/forgot_password';
import { ResetPasswordPage } from '../../resources/js/reset_password/reset_password';
import { SetPasswordPage } from '../../resources/js/set_password/set_password';
import { EmailVerificationPage } from '../../resources/js/email_verification/email_verification';

// Every Turnstile-protected auth page must gate its HTML form post on the shared captcha:
// no post (and a "please wait" message, not "check the captcha") until a token exists.
// The captcha's own states (timeout, error, retry) are covered in components/turnstile_captcha.test.js.

const PASSWORD = 'Abcdef123!';
const PASSWORD_POLICY = {
  min_length: 10,
  max_length: 30,
  shape_pattern: '^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*+\\-])[A-Za-z0-9#?!@$%^&*+\\-]+$',
  allowed_special_characters: '[A-Za-z0-9#?!@$%^&*+-]',
  shape_warning: 'Password must include upper, lower, number and special character',
};
const COUNTRIES = [{ value: 'US', text: 'United States' }];
const COMMON = {
  appLogo: '',
  captchaPublicKey: 'site-key',
  csrfToken: 'csrf-token',
  infoBannerContent: '',
  showInfoBanner: false,
  sessionStatus: '',
  submitButtonText: 'Submit form',
};

const PAGES = [
  {
    name: 'SignUpPage',
    Page: SignUpPage,
    submitLabel: /register now/i,
    props: {
      ...COMMON,
      clientId: '',
      codeOfConductUrl: '',
      countries: COUNTRIES,
      initialValues: {
        first_name: 'Ada', last_name: 'Lovelace', email: 'ada@example.com',
        country_iso_code: 'US', password: PASSWORD, password_confirmation: PASSWORD,
      },
      passwordPolicy: PASSWORD_POLICY,
      redirectUri: '',
      signInAction: '/auth/login',
      signUpAction: '/auth/register',
      signUpError: '',
      tenantName: 'FNTECH',
    },
  },
  {
    name: 'ForgotPasswordPage',
    Page: ForgotPasswordPage,
    submitLabel: 'Submit form',
    props: {
      ...COMMON,
      clientId: '',
      forgotPasswordAction: '/auth/password/email',
      forgotPasswordError: '',
      initialValues: { email: 'ada@example.com' },
      redirectUri: '',
    },
  },
  {
    name: 'ResetPasswordPage',
    Page: ResetPasswordPage,
    submitLabel: 'Submit form',
    props: {
      ...COMMON,
      token: 'reset-token',
      initialValues: { email: 'ada@example.com', password: PASSWORD, password_confirmation: PASSWORD },
      passwordPolicy: PASSWORD_POLICY,
      resetPasswordAction: '/auth/password/reset',
      resetPasswordError: '',
    },
  },
  {
    name: 'SetPasswordPage',
    Page: SetPasswordPage,
    submitLabel: 'Submit form',
    props: {
      ...COMMON,
      clientId: '',
      countries: COUNTRIES,
      initialValues: {
        email: 'ada@example.com', first_name: 'Ada', last_name: 'Lovelace', company: 'Acme',
        country_iso_code: 'US', password: PASSWORD, password_confirmation: PASSWORD,
      },
      passwordPolicy: PASSWORD_POLICY,
      redirectUri: '',
      setPasswordAction: '/auth/password/set',
      setPasswordError: '',
      token: 'set-token',
    },
  },
  {
    name: 'EmailVerificationPage',
    Page: EmailVerificationPage,
    submitLabel: 'Submit form',
    props: {
      ...COMMON,
      emailVerificationAction: '/auth/verification/resend',
      emailVerificationError: '',
      initialValues: { email: 'ada@example.com' },
    },
  },
];

describe.each(PAGES)('$name security check wiring', ({ Page, props, submitLabel }) => {
  let formSubmit;
  const clickSubmit = () => fireEvent.click(screen.getByRole('button', { name: submitLabel }));

  beforeEach(() => {
    mockTurnstile.clear();
    formSubmit = jest.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(() => {});
  });

  afterEach(() => {
    formSubmit.mockRestore();
  });

  it('does not post before Turnstile issues a token and asks the user to wait', async () => {
    render(<Page {...props} />);

    clickSubmit();

    expect(await screen.findByText(CAPTCHA_MESSAGES.VERIFYING)).toBeInTheDocument();
    expect(screen.queryByText(/remember to check the captcha/i)).not.toBeInTheDocument();
    expect(formSubmit).not.toHaveBeenCalled();
  });

  it('posts the form once Turnstile has issued a token', async () => {
    render(<Page {...props} />);

    mockTurnstile.token = 'turnstile-token';
    act(() => mockTurnstile.props.onSuccess('turnstile-token'));
    clickSubmit();

    await waitFor(() => expect(formSubmit).toHaveBeenCalledTimes(1));
  });

  it('offers a retry when Turnstile fails', () => {
    render(<Page {...props} />);

    act(() => mockTurnstile.props.onError('200500'));

    expect(screen.getByText(CAPTCHA_MESSAGES.FAILED)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /retry security check/i })).toBeInTheDocument();
  });

  it('neither renders the widget nor blocks the post when no site key is configured', async () => {
    render(<Page {...props} captchaPublicKey="" />);

    expect(screen.queryByTestId('turnstile')).not.toBeInTheDocument();
    clickSubmit();

    await waitFor(() => expect(formSubmit).toHaveBeenCalledTimes(1));
  });
});
