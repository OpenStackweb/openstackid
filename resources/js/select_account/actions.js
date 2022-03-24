import { deleteRawRequest} from '../base_actions'

export const removeFormerAccount = (usernamel, token) => {
    const params = {
        username: usernamel,
    }
    return deleteRawRequest(window.REMOVE_FORMER_ACCOUNT_ENDPOINT)(params, {'X-CSRF-TOKEN': token});
}
