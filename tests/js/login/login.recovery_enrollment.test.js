import { FLOW, HTTP_CODES } from '../../../resources/js/login/constants';

// actions.js makes real XHR calls — stub every export so the module loads cleanly.
jest.mock('../../../resources/js/login/actions', () => ({
  verifyAccount: jest.fn(),
  emitOTP: jest.fn(),
  resendVerificationEmail: jest.fn(),
  verify2FA: jest.fn(),
  resend2FA: jest.fn(),
  verifyRecoveryCode: jest.fn(),
  enrollRecoveryCodes: jest.fn(),
  acknowledgeRecoveryCodes: jest.fn(),
  cancelLogin: jest.fn(),
}));

delete window.location;
window.location = { href: '', reload: jest.fn() };

import { LoginPage } from '../../../resources/js/login/login';
import {
  verify2FA,
  verifyRecoveryCode,
  enrollRecoveryCodes,
  acknowledgeRecoveryCodes,
} from '../../../resources/js/login/actions';

const PROPS = {
  userName: 'admin@example.com',
  flow: FLOW.PASSWORD,
  allowNativeAuth: true,
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

const REDIRECT = '/oauth2/auth?client_id=abc';
const BATCH = { recovery_codes: ['AAAA-BBBB', 'CCCC-DDDD'], batch_id: 41 };

function makeInstance() {
  const inst = new LoginPage(PROPS);
  inst.setState = jest.fn().mockImplementation((updaterOrObject) => {
    const patch =
      typeof updaterOrObject === 'function' ? updaterOrObject(inst.state) : updaterOrObject;
    inst.state = { ...inst.state, ...patch };
  });
  inst.showAlert = jest.fn();
  inst.resetToPasswordFlow = jest.fn();
  return inst;
}

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));
// Auth::login() rotates the session CSRF token, so verify returns the current one
// and the enrollment calls must send it instead of the page-rendered props.token.
const FRESH_TOKEN = 'fresh-csrf-token';
const verifyOk = (extra = {}) =>
  Promise.resolve({ response: { redirect_url: REDIRECT, csrf_token: FRESH_TOKEN, ...extra } });

describe('LoginPage recovery-code enrollment after 2FA', () => {
  let inst;

  beforeEach(() => {
    jest.clearAllMocks();
    window.location.href = '';
    inst = makeInstance();
    inst.state.twoFactorCode = '123456';
    inst.state.recoveryCode = 'ABCD1234';
  });

  describe('when enrollment is required', () => {
    it('OTP sign-in — enrolls and shows the modal without navigating', async () => {
      verify2FA.mockReturnValue(verifyOk({ recovery_codes_enrollment_required: true }));
      enrollRecoveryCodes.mockReturnValue(Promise.resolve({ response: BATCH }));

      inst.onVerify2FA();
      await flush();

      expect(enrollRecoveryCodes).toHaveBeenCalledWith(FRESH_TOKEN);
      expect(inst.state.recoveryEnrollment).toMatchObject({
        codes: BATCH.recovery_codes,
        batchId: 41,
        redirectUrl: REDIRECT,
        csrfToken: FRESH_TOKEN,
        busy: false,
        error: null,
      });
      expect(window.location.href).toBe('');
    });

    it('falls back to the page token when verify does not return one', async () => {
      verify2FA.mockReturnValue(
        Promise.resolve({ response: { redirect_url: REDIRECT, recovery_codes_enrollment_required: true } }),
      );
      enrollRecoveryCodes.mockReturnValue(Promise.resolve({ response: BATCH }));

      inst.onVerify2FA();
      await flush();

      expect(enrollRecoveryCodes).toHaveBeenCalledWith('csrf-token');
    });

    it('recovery-code sign-in — shows the modal instead of the low-code banner', async () => {
      verifyRecoveryCode.mockReturnValue(
        verifyOk({
          recovery_codes_enrollment_required: true,
          recovery_codes_remaining: 0,
          recovery_codes_low_threshold: 3,
        }),
      );
      enrollRecoveryCodes.mockReturnValue(Promise.resolve({ response: BATCH }));

      inst.onVerifyRecovery();
      await flush();

      expect(inst.state.recoveryEnrollment).not.toBeNull();
      expect(inst.state.lowRecoveryCodesWarning).toBeNull();
      expect(window.location.href).toBe('');
    });

    it.each([
      ['empty codes', { recovery_codes: [], batch_id: 41 }],
      ['missing codes', { batch_id: 41 }],
      ['non-integer batch id', { recovery_codes: ['AAAA-BBBB'], batch_id: '41' }],
      ['missing batch id', { recovery_codes: ['AAAA-BBBB'] }],
      ['empty body', null],
    ])('invalid enroll body (%s) — treated as a failure, sign-in continues', async (_, body) => {
      verify2FA.mockReturnValue(verifyOk({ recovery_codes_enrollment_required: true }));
      enrollRecoveryCodes.mockReturnValue(Promise.resolve({ response: body }));

      inst.onVerify2FA();
      await flush();

      expect(inst.state.recoveryEnrollment).toBeNull();
      expect(window.location.href).toBe(REDIRECT);
    });

    it('enroll failure (412 / 429 / network) — never blocks the sign-in', async () => {
      verify2FA.mockReturnValue(verifyOk({ recovery_codes_enrollment_required: true }));
      enrollRecoveryCodes.mockReturnValue(Promise.reject({ status: HTTP_CODES.TOO_MANY_REQUESTS }));

      inst.onVerify2FA();
      await flush();

      expect(inst.state.recoveryEnrollment).toBeNull();
      expect(window.location.href).toBe(REDIRECT);
    });
  });

  describe('when enrollment is not required', () => {
    it('OTP sign-in — navigates straight to redirect_url without enrolling', async () => {
      verify2FA.mockReturnValue(verifyOk({ recovery_codes_enrollment_required: false }));

      inst.onVerify2FA();
      await flush();

      expect(enrollRecoveryCodes).not.toHaveBeenCalled();
      expect(window.location.href).toBe(REDIRECT);
    });

    it('recovery-code sign-in with few codes left — keeps the low-code banner', async () => {
      verifyRecoveryCode.mockReturnValue(
        verifyOk({
          recovery_codes_enrollment_required: false,
          recovery_codes_remaining: 1,
          recovery_codes_low_threshold: 3,
        }),
      );

      inst.onVerifyRecovery();
      await flush();

      expect(enrollRecoveryCodes).not.toHaveBeenCalled();
      expect(inst.state.lowRecoveryCodesWarning).toEqual({ remaining: 1, redirectUrl: REDIRECT });
    });
  });

  describe('onAcknowledgeRecoveryCodes', () => {
    beforeEach(() => {
      inst.state.recoveryEnrollment = {
        codes: BATCH.recovery_codes,
        batchId: 41,
        redirectUrl: REDIRECT,
        csrfToken: FRESH_TOKEN,
        busy: false,
        error: null,
      };
    });

    it('navigates to redirect_url only after the acknowledgment succeeds', async () => {
      let resolveAck;
      acknowledgeRecoveryCodes.mockReturnValue(new Promise((resolve) => { resolveAck = resolve; }));

      inst.onAcknowledgeRecoveryCodes();

      expect(acknowledgeRecoveryCodes).toHaveBeenCalledWith(41, FRESH_TOKEN);
      expect(inst.state.recoveryEnrollment.busy).toBe(true);
      expect(window.location.href).toBe('');

      resolveAck({ response: { recovery_codes_acknowledged: true } });
      await flush();

      expect(window.location.href).toBe(REDIRECT);
    });

    it('ignores a second click while the acknowledgment is in flight', () => {
      acknowledgeRecoveryCodes.mockReturnValue(new Promise(() => {}));

      inst.onAcknowledgeRecoveryCodes();
      inst.onAcknowledgeRecoveryCodes();

      expect(acknowledgeRecoveryCodes).toHaveBeenCalledTimes(1);
    });

    it('412 (batch replaced) — enrolls again and shows the new batch', async () => {
      acknowledgeRecoveryCodes.mockReturnValue(Promise.reject({ status: HTTP_CODES.PRECONDITION_FAILED }));
      enrollRecoveryCodes.mockReturnValue(
        Promise.resolve({ response: { recovery_codes: ['EEEE-FFFF'], batch_id: 77 } }),
      );

      inst.onAcknowledgeRecoveryCodes();
      await flush();

      expect(enrollRecoveryCodes).toHaveBeenCalledTimes(1);
      expect(enrollRecoveryCodes).toHaveBeenCalledWith(FRESH_TOKEN);
      expect(inst.state.recoveryEnrollment).toMatchObject({
        codes: ['EEEE-FFFF'],
        batchId: 77,
        busy: false,
        error: null,
      });
      expect(window.location.href).toBe('');
    });

    it('412 and the second enroll fails — navigates anyway', async () => {
      acknowledgeRecoveryCodes.mockReturnValue(Promise.reject({ status: HTTP_CODES.PRECONDITION_FAILED }));
      enrollRecoveryCodes.mockReturnValue(Promise.reject({ status: HTTP_CODES.PRECONDITION_FAILED }));

      inst.onAcknowledgeRecoveryCodes();
      await flush();

      expect(window.location.href).toBe(REDIRECT);
    });

    it('any other failure — keeps the modal open with an error, ready to retry', async () => {
      acknowledgeRecoveryCodes.mockReturnValue(
        Promise.reject({ status: HTTP_CODES.INTERNAL_SERVER_ERROR }),
      );

      inst.onAcknowledgeRecoveryCodes();
      await flush();

      expect(window.location.href).toBe('');
      expect(enrollRecoveryCodes).not.toHaveBeenCalled();
      expect(inst.state.recoveryEnrollment.busy).toBe(false);
      expect(inst.state.recoveryEnrollment.error).toEqual(expect.any(String));
      expect(inst.state.recoveryEnrollment.batchId).toBe(41);

      // Retry re-sends the same batch.
      acknowledgeRecoveryCodes.mockReturnValue(Promise.resolve({ response: {} }));
      inst.onAcknowledgeRecoveryCodes();
      await flush();

      expect(acknowledgeRecoveryCodes).toHaveBeenLastCalledWith(41, FRESH_TOKEN);
      expect(window.location.href).toBe(REDIRECT);
    });
  });
});
