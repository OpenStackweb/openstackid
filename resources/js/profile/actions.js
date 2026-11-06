import {deleteRawRequest, getRawRequest, postRawRequestFull, putFile, putRawRequest} from "../base_actions";
import moment from "moment";

export const PAGE_SIZE = 10;

const parseFilter = (filters) => {
    if (!filters || Object.keys(filters).length === 0 || !filters.value) return [];

    const filter = [];

    switch (filters.operatorValue) {
        case 'contains':
        case 'startsWith':
        case 'endsWith':
            filter.push(`${filters.columnField}=@${filters.value}`);
            break;
        case 'isEmpty':
            filter.push(`${filters.columnField}==''`);
            break;
        case 'isNotEmpty':
            filter.push(`${filters.columnField}>=''`);
            break;
        case 'equals':
            filter.push(`${filters.columnField}==${filters.value}`);
            break;
        case 'after':
            filter.push(`${filters.columnField}>${filters.value}`);
            break;
        case 'before':
            filter.push(`${filters.columnField}<${filters.value}`);
            break;
    }
    return filter;
}

export const fetchWithParams =
    async (page = 1, order = 'created_at', orderDir = 'desc', filters = {}, userId, endpoint) => {

    const params = {
        page: page,
        per_page: PAGE_SIZE,
    };

    const filter = parseFilter(filters);
    if(userId) {
        filter.push(`owner_id==${userId}`)
    }

    if (filter.length > 0) {
        params['filter[]'] = filter;
    }

    // order
    if (order != null && orderDir != null) {
        const orderDirSign = (orderDir === 'asc') ? '+' : '-';
        params['order'] = `${orderDirSign}${order}`;
    }

    const {response} = await getRawRequest(endpoint)(params);
    return response;
}

export const getUserActions = async (page = 1, order = 'created_at', orderDir = 'desc', filters = {}, userId = null) =>
    fetchWithParams(page, order, orderDir, filters, userId, window.GET_USER_ACTIONS_ENDPOINT);

export const getUserAccessTokens = async (page = 1, order = 'created_at', orderDir = 'desc', filters = {}, userId = null) =>
    fetchWithParams(page, order, orderDir, filters, userId, window.GET_USER_ACCESS_TOKENS_ENDPOINT);

export const save = async (entity, pic, token) => {

    return putRawRequest(window.SAVE_PROFILE_ENDPOINT)(normalizeEntity(entity), {'X-CSRF-TOKEN': token}).then(() => {
        if (pic) {
            return putFile(window.SAVE_PIC_ENDPOINT)(pic, 'pic', {'X-CSRF-TOKEN': token});
        }
        return Promise.resolve();
    });
}

export const revokeToken = async (value, hint) => {
    return deleteRawRequest(window.REVOKE_ACCESS_TOKENS_ENDPOINT
        .replace('@value', value)
        .replace('%40hint', hint)
    )({'X-CSRF-TOKEN': window.CSFR_TOKEN});
}

export const revokeAllTokens = async () => {
    return deleteRawRequest(window.REVOKE_ALL_TOKENS_ENDPOINT)({'X-CSRF-TOKEN': window.CSFR_TOKEN});
}

// Accept: application/json: the `auth` middleware must answer these XHRs with JSON
// instead of redirecting to a session backurl.
const jsonHeaders = () => ({'X-CSRF-TOKEN': window.CSFR_TOKEN, 'Accept': 'application/json'});

// currentPassword is optional: a user without a password (social-only account)
// regenerates inside the recovery enrollment window instead of confirming one.
export const regenerateRecoveryCodes = async (currentPassword = null) => {
    const params = currentPassword ? {current_password: currentPassword} : {};
    return postRawRequestFull(window.REGENERATE_RECOVERY_CODES_ENDPOINT)(params, jsonHeaders());
}

// Generates a batch without the password, right after a 2FA challenge.
export const enrollRecoveryCodes = async () => {
    return postRawRequestFull(window.ENROLL_RECOVERY_CODES_ENDPOINT)({}, jsonHeaders());
}

// "I've Saved These Codes": confirms the exact batch that was displayed.
export const acknowledgeRecoveryCodes = async (batchId) => {
    return postRawRequestFull(window.ACKNOWLEDGE_RECOVERY_CODES_ENDPOINT)({batch_id: batchId}, jsonHeaders());
}

export const enableTwoFactor = async (method) => {
    const params = {method};
    return postRawRequestFull(window.ENABLE_TWO_FACTOR_ENDPOINT)(params, {'X-CSRF-TOKEN': window.CSFR_TOKEN});
}

const normalizeEntity = (entity) => {
    entity.public_profile_show_photo = entity.public_profile_show_photo ? 1 : 0;
    entity.public_profile_show_fullname = entity.public_profile_show_fullname ? 1 : 0;
    entity.public_profile_show_email = entity.public_profile_show_email ? 1 : 0;
    entity.public_profile_allow_chat_with_me = entity.public_profile_allow_chat_with_me ? 1 : 0;
    if (entity.birthday) {
        entity.birthday = moment(`${entity.birthday} 12:00`).unix();
    }

    return entity;
}