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

export const MFA_ERROR_CODE = {
  MFA_SESSION_EXPIRED: "mfa_session_expired",
  MFA_CHALLENGE_REQUIRED: "mfa_required",
};
