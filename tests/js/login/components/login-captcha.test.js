import React from 'react';
import { render, screen, fireEvent, act } from '@testing-library/react';

jest.mock('@marsidev/react-turnstile', () => require('../../helpers/turnstile_mock'));

import { mockTurnstile } from '../../helpers/turnstile_mock';
import { CAPTCHA_MESSAGES } from '../../../../resources/js/components/turnstile_captcha';
import PasswordInputForm from '../../../../resources/js/login/components/password_input_form';
import OTPInputForm from '../../../../resources/js/login/components/otp_input_form';

// Once the failed-attempts threshold is reached both login forms gate their native POST on
// a Turnstile token. Without one they must not call onAuthenticate and must ask the user to
// wait (or offer a retry when Turnstile fails), instead of "you must check CAPTCHA".

const COMMON = {
  formAction: '/auth/login',
  disableInput: false,
  userNameValue: 'ada@example.com',
  csrfToken: 'csrf-token',
  captchaPublicKey: 'site-key',
  loginAttempts: 3,
};

const FORMS = [
  {
    name: 'PasswordInputForm',
    Form: PasswordInputForm,
    submitLabel: /^continue$/i,
    props: {
      ...COMMON,
      showPassword: false,
      passwordValue: 'secret-password',
      passwordError: '',
      onUserPasswordChange: jest.fn(),
      handleClickShowPassword: jest.fn(),
      handleMouseDownPassword: jest.fn(),
      handleEmitOtpAction: jest.fn(),
      forgotPasswordAction: '/auth/password/reset',
      maxLoginFailedAttempts: 10,
      userIsActive: true,
      helpAction: '/help',
    },
  },
  {
    name: 'OTPInputForm',
    Form: OTPInputForm,
    submitLabel: /^continue$/i,
    props: {
      ...COMMON,
      otpCode: '123456',
      otpError: '',
      otpLength: 6,
      otpLifetime: null,
      codeVersion: 0,
      onCodeChange: jest.fn(),
      onReset: jest.fn(),
    },
  },
];

describe.each(FORMS)('$name captcha gate', ({ Form, props, submitLabel }) => {
  let onAuthenticate;

  const renderForm = (overrides = {}) =>
    render(<Form {...props} onAuthenticate={onAuthenticate} {...overrides} />);
  const clickSubmit = () => fireEvent.click(screen.getByRole('button', { name: submitLabel }));

  beforeEach(() => {
    mockTurnstile.clear();
    // Returning false keeps jsdom from attempting the native navigation.
    onAuthenticate = jest.fn(() => false);
    // api.js loaded: retry resets the existing widget.
    window.turnstile = {};
  });

  afterEach(() => {
    delete window.turnstile;
  });

  it('has no captcha and submits normally below the failed-attempts threshold', () => {
    renderForm({ shouldShowCaptcha: () => false });

    expect(screen.queryByTestId('turnstile')).not.toBeInTheDocument();
    clickSubmit();
    expect(onAuthenticate).toHaveBeenCalledTimes(1);
  });

  it('does not authenticate before Turnstile issues a token and asks the user to wait', () => {
    renderForm({ shouldShowCaptcha: () => true });

    clickSubmit();

    expect(onAuthenticate).not.toHaveBeenCalled();
    expect(screen.getByRole('alert')).toHaveTextContent(CAPTCHA_MESSAGES.VERIFYING);
    expect(screen.queryByText(/must check captcha/i)).not.toBeInTheDocument();
  });

  it('authenticates once Turnstile has issued a token', () => {
    renderForm({ shouldShowCaptcha: () => true });

    mockTurnstile.token = 'turnstile-token';
    act(() => mockTurnstile.props.onSuccess('turnstile-token'));
    clickSubmit();

    expect(onAuthenticate).toHaveBeenCalledTimes(1);
  });

  it('offers a retry when Turnstile fails', () => {
    renderForm({ shouldShowCaptcha: () => true });

    act(() => mockTurnstile.props.onError('200500'));

    expect(screen.getByText(CAPTCHA_MESSAGES.FAILED)).toBeInTheDocument();
    fireEvent.click(screen.getByRole('button', { name: /retry security check/i }));
    expect(mockTurnstile.reset).toHaveBeenCalledTimes(1);
  });

  it('does not gate the submit when no site key is configured', () => {
    renderForm({ shouldShowCaptcha: () => true, captchaPublicKey: '' });

    expect(screen.queryByTestId('turnstile')).not.toBeInTheDocument();
    clickSubmit();
    expect(onAuthenticate).toHaveBeenCalledTimes(1);
  });
});
