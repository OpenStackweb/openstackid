import React from 'react';
import { FLOW, HTTP_CODES, MFA_ERROR_CODE } from '../../../resources/js/login/constants';

// actions.js makes real XHR calls — stub every export so the module loads cleanly.
jest.mock('../../../resources/js/login/actions', () => ({
  verifyAccount: jest.fn(),
  emitOTP: jest.fn(),
  resendVerificationEmail: jest.fn(),
  verify2FA: jest.fn(),
  resend2FA: jest.fn(),
  verifyRecoveryCode: jest.fn(),
  authenticateWithPassword: jest.fn(),
  cancelLogin: jest.fn(),
}));

// Replace window.location with a plain object so href assignments and reload() are observable.
// jsdom does not allow direct reassignment of window.location, so we delete it first.
delete window.location;
window.location = { href: '', reload: jest.fn() };

import { LoginPage } from '../../../resources/js/login/login';

// ─── Minimal props that satisfy the LoginPage constructor ────────────────────

const PROPS = {
  userName: '',
  user_pic: null,
  user_fullname: null,
  user_verified: null,
  email_verified: null,
  flow: FLOW.PASSWORD,
  allowNativeAuth: true,
  showInfoBanner: false,
  infoBannerContent: null,
  otpLength: 6,
  otpLifetime: 300,
  mfaMethod: 'email_otp',
  authError: '',
  token: 'csrf-token',
  appName: 'Test IDP',
  appLogo: '',
  formAction: '/auth/login',
  forgotPasswordAction: '/auth/forgot',
  createAccountAction: '/auth/register',
  helpAction: '/help',
  captchaPublicKey: '',
  verifyEmailAction: '/auth/verify',
};

/**
 * Build a LoginPage instance with:
 *  - setState applied synchronously so instance.state reflects changes immediately
 *  - showAlert and resetToPasswordFlow replaced by jest.fn() stubs
 */
function makeInstance() {
  const inst = new LoginPage(PROPS);

  inst.setState = jest.fn().mockImplementation((updaterOrObject) => {
    const patch =
      typeof updaterOrObject === 'function'
        ? updaterOrObject(inst.state)
        : updaterOrObject;
    inst.state = { ...inst.state, ...patch };
  });

  inst.showAlert = jest.fn();
  inst.resetToPasswordFlow = jest.fn();

  return inst;
}

/** Build a superagent-style rejected-promise error object. */
function makeError(status, body = null) {
  return { status, response: body ? { body } : null };
}

// ─────────────────────────────────────────────────────────────────────────────

describe('LoginPage', () => {

  describe('handleMfaError', () => {
    let inst;

    beforeEach(() => {
      inst = makeInstance();
      window.location.reload.mockClear();
    });

    it('401 + mfa_session_expired — resets to password flow and shows warning snackbar', () => {
      inst.handleMfaError(
        makeError(HTTP_CODES.UNAUTHORIZED, { error_code: MFA_ERROR_CODE.MFA_SESSION_EXPIRED }),
        'twofactor',
      );

      expect(inst.resetToPasswordFlow).toHaveBeenCalledTimes(1);
      expect(inst.showAlert).toHaveBeenCalledWith(
        'Your verification session has expired. Please sign in again.',
        'warning',
      );
    });

    it('429 — sets inline field error from body.error_message and re-enables input', () => {
      const msg = 'Too many OTP attempts. Wait 60 s.';
      inst.handleMfaError(
        makeError(HTTP_CODES.TOO_MANY_REQUESTS, { error_code: 'mfa_rate_limit', error_message: msg }),
        'twofactor',
      );

      expect(inst.state.errors.twofactor).toBe(msg);
      expect(inst.state.disableInput).toBe(false);
      // Should NOT navigate or reset to email step.
      expect(inst.resetToPasswordFlow).not.toHaveBeenCalled();
    });

    it('401 invalid code — sets "Invalid or expired verification code" for the given field', () => {
      inst.handleMfaError(
        makeError(HTTP_CODES.UNAUTHORIZED, { error_code: 'mfa_verification_failed' }),
        'twofactor',
      );

      expect(inst.state.errors.twofactor).toContain('Invalid or expired verification code');
      expect(inst.state.disableInput).toBe(false);
    });

    it('412 precondition failed — sets "Please enter a valid code." as field error', () => {
      inst.handleMfaError(
        makeError(HTTP_CODES.PRECONDITION_FAILED),
        'twofactor',
      );

      expect(inst.state.errors.twofactor).toBe('Please enter a valid code.');
      expect(inst.state.disableInput).toBe(false);
    });

    it('undefined status (null error) — calls window.location.reload()', () => {
      inst.handleMfaError(null, 'twofactor');

      expect(window.location.reload).toHaveBeenCalledTimes(1);
      expect(inst.showAlert).not.toHaveBeenCalled();
    });
  });

  describe('handleAuthenticatePasswordOk', () => {
    let inst;

    beforeEach(() => {
      inst = makeInstance();
      window.location.href = '';
    });

    it('mfa_required — transitions authFlow to MFA and stores otp_length / otp_lifetime', () => {
      inst.handleAuthenticatePasswordOk({
        response: {
          error_code: MFA_ERROR_CODE.MFA_CHALLENGE_REQUIRED,
          otp_length: 8,
          otp_lifetime: 600,
        },
        status: HTTP_CODES.OK,
        finalUrl: null,
      });

      expect(inst.state.authFlow).toBe(FLOW.MFA);
      expect(inst.state.otpLength).toBe(8);
      expect(inst.state.otpLifetime).toBe(600);
      expect(inst.state.disableInput).toBe(false);
    });

    it('success redirect — navigates to finalUrl when status is 200 and finalUrl is present', () => {
      const finalUrl = 'https://example.com/dashboard';
      inst.handleAuthenticatePasswordOk({
        response: {},
        status: HTTP_CODES.OK,
        finalUrl,
      });

      expect(window.location.href).toBe(finalUrl);
    });
  });
});
