import {postRawRequest} from '../base_actions'

export const verifyAccount = (email, token) => {

    const params = {
      email: email
    };

    return postRawRequest(window.VERIFY_ACCOUNT_ENDPOINT)(params, {'X-CSRF-TOKEN': token});

}

export const emitOTP = (email, token, connection = 'email', send='code') => {
    const params = {
        username: email,
        connection:connection,
        send:send
    }

    return postRawRequest(window.EMIT_OTP_ENDPOINT)(params, {'X-CSRF-TOKEN': token});
}

export const resendVerificationEmail = (email, token) => {
    const params = {
      email: email
    };

    return postRawRequest(window.RESEND_VERIFICATION_EMAIL_ENDPOINT)(params, {'X-CSRF-TOKEN': token});
}

export const verify2FA = (otpValue, method, trustDevice, token) => {
    const params = {
        otp_value: otpValue,
        method: method,
        trust_device: trustDevice ? 1 : 0
    };

    return postRawRequest(window.VERIFY_2FA_ENDPOINT)(params, {'X-CSRF-TOKEN': token});
}

export const resend2FA = (method, token) => {
    const params = {
        method: method
    };

    return postRawRequest(window.RESEND_2FA_ENDPOINT)(params, {'X-CSRF-TOKEN': token});
}

export const verifyRecoveryCode = (recoveryCode, token) => {
    const params = {
        recovery_code: recoveryCode
    };

    return postRawRequest(window.RECOVERY_2FA_ENDPOINT)(params, {'X-CSRF-TOKEN': token});
}

export const cancelLogin = (token) => {
    return postRawRequest(window.CANCEL_LOGIN_ENDPOINT)({}, {'X-CSRF-TOKEN': token});
}
