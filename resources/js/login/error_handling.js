export const SESSION_EXPIRED_MESSAGE =
    'Your session has expired or your browser is blocking cookies. Please reload the page and try again.';
export const GENERIC_RETRY_MESSAGE = 'Something went wrong. Please try again.';
export const TOO_MANY_REQUESTS_MESSAGE = 'Too many requests. Try it later.';

export const ACCOUNT_NOT_FOUND = 'account_not_found';
export const SESSION_EXPIRED = 'session_expired';
export const TOO_MANY_REQUESTS = 'too_many_requests';
export const GENERIC_ERROR = 'generic_error';

/**
 * Classifies a failed POST /auth/login/account-verify.
 * Only a real 404 means the account does not exist; anything else must never
 * switch the page to the sign-up state.
 * @param {number|undefined} status HTTP status (undefined on network errors)
 * @returns {{type: string, message: string}}
 */
export const classifyAccountVerifyError = (status) => {
    switch (Number(status)) {
        case 404:
            // non-empty on purpose: errors.email only has to be truthy for this branch
            return {type: ACCOUNT_NOT_FOUND, message: ' '};
        case 419:
            return {type: SESSION_EXPIRED, message: SESSION_EXPIRED_MESSAGE};
        case 429:
            return {type: TOO_MANY_REQUESTS, message: TOO_MANY_REQUESTS_MESSAGE};
        default:
            return {type: GENERIC_ERROR, message: GENERIC_RETRY_MESSAGE};
    }
};

export const isSessionExpired = (error) => Number(error?.status) === 419;
