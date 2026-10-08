export const HTTP_CODES = {
  OK: 200,
  BAD_REQUEST: 400,
  UNAUTHORIZED: 401,
  FORBIDDEN: 403,
  NOT_FOUND: 404,
  PRECONDITION_FAILED: 412,
  TOO_MANY_REQUESTS: 429,
  INTERNAL_SERVER_ERROR: 500,
};

export const MFA_METHODS = {
  EMAIL_OTP: "email_otp",
  TOTP: "totp",
};

export const FLOW = {
  PASSWORD: "password",
  MFA: "2fa",
  RECOVERY: "recovery",
  OTP: "otp",
};

export const OTP_LENGTH_DEFAULT = 6;
export const OTP_TTL_DEFAULT = 300;
export const MFA_METHOD_DEFAULT = MFA_METHODS.EMAIL_OTP;
export const CAPTCHA_FIELD = 'cf-turnstile-response';

// Cooldown applied to any "resend code" action (MFA and passwordless OTP) to
// avoid hammering the resend endpoint (the backend also rate-limits server-side).
export const RESEND_COOLDOWN_SECONDS = 30;

// Success confirmation shown after a code is (re)sent - shared by MFA's
// onResend2FA() and passwordless's emitOtpAction() so the two flows can't
// silently diverge in wording.
export const CODE_RESENT_MESSAGE = "A new verification code has been sent to your email.";

export const MFA_ERROR_CODE = {
  MFA_SESSION_EXPIRED: "mfa_session_expired",
};
