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
