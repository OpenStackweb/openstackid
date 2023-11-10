import {getRawRequest, postRawRequest, putRawRequest, deleteRawRequest} from "../../../base_actions";

export const PAGE_SIZE = 30;

export const regenerateClientSecret = async (clientId) => {
    return putRawRequest(window.REGENERATE_CLIENT_SECRET_ENDPOINT
        .replace('@client_id', clientId))({'X-CSRF-TOKEN': window.CSFR_TOKEN});
}

export const getAccessTokens = async (clientId, page = 1, perPage = PAGE_SIZE, order = 'created_at', orderDir = 'desc', filters = {}) => {
    const params = {
        page: page,
        per_page: perPage,
    };

    // order
    if (order != null && orderDir != null) {
        const orderDirSign = (orderDir === 'asc') ? '+' : '-';
        params['order'] = `${orderDirSign}${order}`;
    }

    const {response} = await getRawRequest(window.GET_ACCESS_TOKENS_ENDPOINT.replace('@client_id', clientId))(params);
    return response;
}

export const getRefreshTokens = async (clientId, page = 1, perPage = PAGE_SIZE, order = 'created_at', orderDir = 'desc', filters = {}) => {
    const params = {
        page: page,
        per_page: perPage,
    };

    // order
    if (order != null && orderDir != null) {
        const orderDirSign = (orderDir === 'asc') ? '+' : '-';
        params['order'] = `${orderDirSign}${order}`;
    }

    const {response} = await getRawRequest(window.GET_REFRESH_TOKENS_ENDPOINT.replace('@client_id', clientId))(params);
    return response;
}

export const revokeToken = async (clientId, value, hint) => {
    return deleteRawRequest(window.REVOKE_TOKENS_ENDPOINT
        .replace('@client_id', clientId)
        .replace('@value', value)
        .replace('@hint', hint))({'X-CSRF-TOKEN': window.CSFR_TOKEN});
}

export const updateClientData = async (clientId, entity) => {
    return putRawRequest(window.UPDATE_CLIENT_DATA_ENDPOINT
        .replace('@client_id', clientId))(normalizeEntity(entity), {}, {'X-CSRF-TOKEN': window.CSFR_TOKEN});
}

export const addScope = async (clientId, scopeId) => {
    return putRawRequest(window.ADD_CLIENT_SCOPE_ENDPOINT
        .replace('@client_id', clientId)
        .replace('@scope_id', scopeId))({}, {}, {'X-CSRF-TOKEN': window.CSFR_TOKEN});
}

export const removeScope = async (clientId, scopeId) => {
    return deleteRawRequest(window.REMOVE_CLIENT_SCOPE_ENDPOINT
        .replace('@client_id', clientId)
        .replace('@scope_id', scopeId))({'X-CSRF-TOKEN': window.CSFR_TOKEN});
}

export const addPublicKey = async (clientId, entity) => {
    return postRawRequest(window.ADD_PUBLIC_KEY_ENDPOINT
        .replace('@client_id', clientId))(normalizeEntity(entity), {}, {'X-CSRF-TOKEN': window.CSFR_TOKEN});
}

export const getPublicKeys = async (clientId, page = 1, perPage = PAGE_SIZE) => {
    const params = {
        page: page,
        per_page: perPage,
    };

    const {response} = await getRawRequest(window.GET_PUBLIC_KEYS_ENDPOINT.replace('@client_id', clientId))(params);
    return response;
}

export const removePublicKey = async (clientId, keyId) => {
    return deleteRawRequest(window.REMOVE_PUBLIC_KEY_ENDPOINT.replace('@client_id', clientId)
        .replace('@public_key_id', keyId))({'X-CSRF-TOKEN': window.CSFR_TOKEN});
}

const normalizeEntity = (entity) => {
    const normEntity = {...entity};
    normEntity.active = entity.active ? 1 : 0;
    normEntity.app_active = entity.app_active ? 1 : 0;
    normEntity.logout_session_required = entity.logout_session_required ? 1 : 0;
    normEntity.logout_use_iframe = entity.logout_use_iframe ? 1 : 0;
    normEntity.rotate_refresh_token = entity.rotate_refresh_token ? 1 : 0;

    if (normEntity.admin_users) {
        //console.log(normEntity.admin_users.map((au) => au.id))
        normEntity.admin_users = entity.admin_users.map((au) => au.id);
    }

    normEntity.allowed_origins = Array.isArray(entity.allowed_origins) ?
        entity.allowed_origins.filter(a => a).join(',') : entity.allowed_origins;

    normEntity.redirect_uris = Array.isArray(entity.redirect_uris) ?
        entity.redirect_uris.filter(r => r).join(',') : entity.redirect_uris;

    normEntity.post_logout_redirect_uris = Array.isArray(entity.post_logout_redirect_uris) ?
        entity.post_logout_redirect_uris.filter(r => r).join(',') : entity.post_logout_redirect_uris;

    normEntity.contacts = Array.isArray(entity.contacts) ? entity.contacts.filter(c => c).join(',') : entity.contacts;

    return normEntity;
}