// Shared between the profile page's RecoveryCodesPanel and the login page's
// post-MFA-recovery-login warning, so dismissing the low-code warning in
// either place suppresses it everywhere else for the rest of the session.
export const RECOVERY_CODES_LOW_WARNING_DISMISSED_KEY = "recovery_codes_low_warning_dismissed";
export const DEFAULT_RECOVERY_CODES_LOW_THRESHOLD = 3;
