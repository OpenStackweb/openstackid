import React from 'react';
import { FLOW, HTTP_CODES, MFA_ERROR_CODE } from '../../../resources/js/login/constants';
import {
  RECOVERY_CODES_LOW_WARNING_DISMISSED_KEY,
  DEFAULT_RECOVERY_CODES_LOW_THRESHOLD,
} from '../../../resources/js/shared/recovery_codes';

// actions.js makes real XHR calls — stub every export so the module loads cleanly.
jest.mock('../../../resources/js/login/actions', () => ({
  verifyAccount: jest.fn(),
  emitOTP: jest.fn(),
  resendVerificationEmail: jest.fn(),
  verify2FA: jest.fn(),
  resend2FA: jest.fn(),
  verifyRecoveryCode: jest.fn(),
  cancelLogin: jest.fn(),
}));

// Replace window.location with a plain object so href assignments and reload() are observable.
// jsdom does not allow direct reassignment of window.location, so we delete it first.
delete window.location;
window.location = { href: '', reload: jest.fn() };

import { LoginPage } from '../../../resources/js/login/login';
import { verifyRecoveryCode, cancelLogin } from '../../../resources/js/login/actions';

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

/** Let the pending .then()/.catch() callbacks of the mocked action run. */
const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

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

  // ─── Recovery-code login flow (CU-86ba2zp4f) ───────────────────────────────

  describe('recovery code flow', () => {
    let inst;

    beforeEach(() => {
      inst = makeInstance();
      inst.state = { ...inst.state, authFlow: FLOW.MFA };
      window.location.href = '';
      window.location.reload.mockClear();
      verifyRecoveryCode.mockReset();
      cancelLogin.mockReset();
      sessionStorage.clear();
    });

    describe('mode switching', () => {

      it('onUseRecovery — switches to the recovery flow with a clean field', () => {
        inst.state = {
          ...inst.state,
          recoveryCode: 'STALE123',
          errors: { ...inst.state.errors, recovery: 'Invalid recovery code. Please try again.' },
        };

        inst.onUseRecovery();

        expect(inst.state.authFlow).toBe(FLOW.RECOVERY);
        expect(inst.state.recoveryCode).toBe('');
        expect(inst.state.errors.recovery).toBe('');
      });

      it('onBackToOtp — returns to OTP mode and drops the abandoned recovery attempt', () => {
        inst.state = {
          ...inst.state,
          authFlow: FLOW.RECOVERY,
          recoveryCode: 'ABCD1234',
          errors: {
            ...inst.state.errors,
            recovery: 'Invalid recovery code. Please try again.',
            twofactor: 'Invalid or expired verification code. Please try again.',
          },
        };

        inst.onBackToOtp();

        expect(inst.state.authFlow).toBe(FLOW.MFA);
        expect(inst.state.recoveryCode).toBe('');
        expect(inst.state.errors.recovery).toBe('');
        expect(inst.state.errors.twofactor).toBe('');
      });

      it('resetToPasswordFlow — clears the recovery code on the way back to password', async () => {
        const real = makeInstance();
        real.resetToPasswordFlow = LoginPage.prototype.resetToPasswordFlow.bind(real);
        cancelLogin.mockReturnValue(Promise.resolve({}));
        real.state = { ...real.state, authFlow: FLOW.RECOVERY, recoveryCode: 'ABCD1234' };

        real.resetToPasswordFlow();
        await flush();

        expect(real.state.authFlow).toBe(FLOW.PASSWORD);
        expect(real.state.recoveryCode).toBe('');
        expect(real.state.errors.recovery).toBe('');
      });
    });

    describe('onRecoveryCodeChange', () => {

      it('normalizes the displayed dash and lowercase away', () => {
        // Codes are shown as XXXX-XXXX but hashed without the separator.
        inst.onRecoveryCodeChange({ target: { value: 'abcd-1234' } });
        expect(inst.state.recoveryCode).toBe('ABCD1234');
      });

      it('clears a previous inline error as soon as the user edits the field', () => {
        inst.state = {
          ...inst.state,
          errors: { ...inst.state.errors, recovery: 'Invalid recovery code. Please try again.' },
        };

        inst.onRecoveryCodeChange({ target: { value: 'A' } });

        expect(inst.state.errors.recovery).toBe('');
      });
    });

    describe('onVerifyRecovery', () => {

      it('empty code — inline error, no request issued', () => {
        inst.state = { ...inst.state, recoveryCode: '' };

        inst.onVerifyRecovery();

        expect(verifyRecoveryCode).not.toHaveBeenCalled();
        expect(inst.state.errors.recovery).toBe('Recovery code is empty');
      });

      it('does not re-submit while a request is already in flight', () => {
        inst.state = { ...inst.state, recoveryCode: 'ABCD1234', disableInput: true };

        inst.onVerifyRecovery();

        expect(verifyRecoveryCode).not.toHaveBeenCalled();
      });

      it('valid code — posts the normalized code and navigates to redirect_url', async () => {
        verifyRecoveryCode.mockReturnValue(
          Promise.resolve({ response: { redirect_url: 'https://idp.test/authorize', recovery_codes_remaining: 7 } }),
        );
        inst.state = { ...inst.state, recoveryCode: 'ABCD1234' };

        inst.onVerifyRecovery();
        expect(verifyRecoveryCode).toHaveBeenCalledWith('ABCD1234', PROPS.token);
        await flush();

        expect(window.location.href).toBe('https://idp.test/authorize');
        expect(inst.state.lowRecoveryCodesWarning).toBeNull();
      });

      it('valid code with few codes left — shows the warning instead of navigating', async () => {
        verifyRecoveryCode.mockReturnValue(
          Promise.resolve({
            response: {
              redirect_url: 'https://idp.test/authorize',
              recovery_codes_remaining: DEFAULT_RECOVERY_CODES_LOW_THRESHOLD - 1,
            },
          }),
        );
        inst.state = { ...inst.state, recoveryCode: 'ABCD1234' };

        inst.onVerifyRecovery();
        await flush();

        expect(window.location.href).toBe('');
        expect(inst.state.lowRecoveryCodesWarning).toEqual({
          remaining: DEFAULT_RECOVERY_CODES_LOW_THRESHOLD - 1,
          redirectUrl: 'https://idp.test/authorize',
        });
      });

      it('low-code warning already dismissed this session — navigates straight through', async () => {
        sessionStorage.setItem(RECOVERY_CODES_LOW_WARNING_DISMISSED_KEY, '1');
        verifyRecoveryCode.mockReturnValue(
          Promise.resolve({ response: { redirect_url: 'https://idp.test/authorize', recovery_codes_remaining: 1 } }),
        );
        inst.state = { ...inst.state, recoveryCode: 'ABCD1234' };

        inst.onVerifyRecovery();
        await flush();

        expect(window.location.href).toBe('https://idp.test/authorize');
        expect(inst.state.lowRecoveryCodesWarning).toBeNull();
      });

      it('invalid/used code — inline error and the user stays in the recovery flow', async () => {
        verifyRecoveryCode.mockReturnValue(
          Promise.reject(makeError(HTTP_CODES.UNAUTHORIZED, { error_code: 'mfa_invalid_recovery' })),
        );
        inst.state = { ...inst.state, authFlow: FLOW.RECOVERY, recoveryCode: 'ABCD1234' };

        inst.onVerifyRecovery();
        await flush();

        expect(inst.state.errors.recovery).toBe('Invalid recovery code. Please try again.');
        expect(inst.state.authFlow).toBe(FLOW.RECOVERY);
        expect(inst.state.disableInput).toBe(false);
        expect(inst.resetToPasswordFlow).not.toHaveBeenCalled();
        expect(window.location.href).toBe('');
      });

      it('mfa_session_expired — returns to the password flow with a warning', async () => {
        verifyRecoveryCode.mockReturnValue(
          Promise.reject(
            makeError(HTTP_CODES.UNAUTHORIZED, { error_code: MFA_ERROR_CODE.MFA_SESSION_EXPIRED }),
          ),
        );
        inst.state = { ...inst.state, authFlow: FLOW.RECOVERY, recoveryCode: 'ABCD1234' };

        inst.onVerifyRecovery();
        await flush();

        expect(inst.resetToPasswordFlow).toHaveBeenCalledTimes(1);
        expect(inst.showAlert).toHaveBeenCalledWith(
          'Your verification session has expired. Please sign in again.',
          'warning',
        );
      });

      it('mfa_rate_limit — surfaces the server message inline', async () => {
        const msg = 'Too many attempts. Please try again later.';
        verifyRecoveryCode.mockReturnValue(
          Promise.reject(
            makeError(HTTP_CODES.TOO_MANY_REQUESTS, { error_code: 'mfa_rate_limit', error_message: msg }),
          ),
        );
        inst.state = { ...inst.state, authFlow: FLOW.RECOVERY, recoveryCode: 'ABCD1234' };

        inst.onVerifyRecovery();
        await flush();

        expect(inst.state.errors.recovery).toBe(msg);
        expect(inst.state.authFlow).toBe(FLOW.RECOVERY);
        expect(inst.state.disableInput).toBe(false);
      });
    });

    it('onContinueAfterLowRecoveryCodes — remembers the dismissal and resumes the redirect', () => {
      inst.state = {
        ...inst.state,
        lowRecoveryCodesWarning: { remaining: 1, redirectUrl: 'https://idp.test/authorize' },
      };

      inst.onContinueAfterLowRecoveryCodes();

      expect(sessionStorage.getItem(RECOVERY_CODES_LOW_WARNING_DISMISSED_KEY)).toBe('1');
      expect(window.location.href).toBe('https://idp.test/authorize');
    });
  });
});
