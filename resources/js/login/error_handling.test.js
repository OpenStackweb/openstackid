import {
    classifyAccountVerifyError,
    isSessionExpired,
    ACCOUNT_NOT_FOUND,
    SESSION_EXPIRED,
    TOO_MANY_REQUESTS,
    GENERIC_ERROR,
    SESSION_EXPIRED_MESSAGE,
    GENERIC_RETRY_MESSAGE,
    TOO_MANY_REQUESTS_MESSAGE,
} from './error_handling';

describe('classifyAccountVerifyError', () => {
    test('404 is the only status that means account not found', () => {
        const r = classifyAccountVerifyError(404);
        expect(r.type).toBe(ACCOUNT_NOT_FOUND);
        expect(r.message.trim()).toBe('');
        expect(r.message).toBeTruthy();
    });

    test('419 is a session/cookies problem, not account not found', () => {
        expect(classifyAccountVerifyError(419)).toEqual({type: SESSION_EXPIRED, message: SESSION_EXPIRED_MESSAGE});
    });

    test('429 keeps the too many requests message', () => {
        expect(classifyAccountVerifyError(429)).toEqual({type: TOO_MANY_REQUESTS, message: TOO_MANY_REQUESTS_MESSAGE});
        expect(TOO_MANY_REQUESTS_MESSAGE).toBe('Too many requests. Try it later.');
    });

    test.each([500, 502, 503, 400, undefined, null])('status %p is a generic error', (status) => {
        expect(classifyAccountVerifyError(status)).toEqual({type: GENERIC_ERROR, message: GENERIC_RETRY_MESSAGE});
    });
});

describe('isSessionExpired', () => {
    test('detects 419 only', () => {
        expect(isSessionExpired({status: 419})).toBe(true);
        expect(isSessionExpired({status: 404})).toBe(false);
        expect(isSessionExpired({})).toBe(false);
        expect(isSessionExpired(undefined)).toBe(false);
    });
});
