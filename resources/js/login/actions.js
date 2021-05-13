import {getRawRequest} from '../base_actions'



export const verifyAccount = (email) => {
    const params = {
      email: email
    };

    return getRawRequest(window.VERIFY_ACCOUNT_ENDPOINT)(params);

}
