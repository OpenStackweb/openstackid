import {getRawRequest, postRawRequest, putRawRequest, deleteRawRequest} from "../../../base_actions";

export const PAGE_SIZE = 30;

export const regenerateClientSecret = async (clientId, token) => {
    return putRawRequest(window.REGENERATE_CLIENT_SECRET_ENDPOINT.replace('@client_id', clientId))({'X-CSRF-TOKEN': token});
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

export const revokeToken = async (clientId, value, hint, token) => {
    return deleteRawRequest(window.REVOKE_TOKENS_ENDPOINT
        .replace('@client_id', clientId)
        .replace('@value', value)
        .replace('@hint', hint))({'X-CSRF-TOKEN': token});
}

export const updateOAuthClientData = async (clientId, entity, token) => {
    return putRawRequest(window.UPDATE_CLIENT_DATA_ENDPOINT.replace('@client_id', clientId))(normalizeEntity(entity), {}, {'X-CSRF-TOKEN': token});
}

export const addScope = async (clientId, scopeId, token) => {
    return putRawRequest(window.ADD_CLIENT_SCOPE_ENDPOINT
        .replace('@client_id', clientId)
        .replace('@scope_id', scopeId))({}, {}, {'X-CSRF-TOKEN': token});
}

export const removeScope = async (clientId, scopeId, token) => {
    return deleteRawRequest(window.REMOVE_CLIENT_SCOPE_ENDPOINT.replace('@client_id', clientId)
        .replace('@scope_id', scopeId))({'X-CSRF-TOKEN': token});
}

export const addPublicKey = async (clientId, entity, token) => {
    return postRawRequest(window.ADD_PUBLIC_KEY_ENDPOINT.replace('@client_id', clientId))(normalizeEntity(entity), {}, {'X-CSRF-TOKEN': token});
}

export const getPublicKeys = async (clientId, page = 1, perPage = PAGE_SIZE) => {
    const params = {
        page: page,
        per_page: perPage,
    };

    const {response} = await getRawRequest(window.GET_PUBLIC_KEYS_ENDPOINT.replace('@client_id', clientId))(params);
    return response;
}

export const removePublicKey = async (clientId, keyId, token) => {
    return deleteRawRequest(window.REMOVE_PUBLIC_KEY_ENDPOINT.replace('@client_id', clientId)
        .replace('@public_key_id', keyId))({'X-CSRF-TOKEN': token});
}

const normalizeEntity = (entity) => {
    entity.active = entity.active ? 1 : 0;
    entity.app_active = entity.app_active ? 1 : 0;
    entity.logout_session_required = entity.logout_session_required ? 1 : 0;
    entity.logout_use_iframe = entity.logout_use_iframe ? 1 : 0;
    entity.rotate_refresh_token = entity.rotate_refresh_token ? 1 : 0;

    if (entity.admin_users) {
        //console.log(entity.admin_users.map((au) => au.id))
        entity.admin_users = entity.admin_users.map((au) => au.id);
    }

    entity.allowed_origins = Array.isArray(entity.allowed_origins) ?
        entity.allowed_origins.filter(a => a).join(',') : entity.allowed_origins;

    entity.redirect_uris = Array.isArray(entity.redirect_uris) ?
        entity.redirect_uris.filter(r => r).join(',') : entity.redirect_uris;

    entity.contacts = Array.isArray(entity.contacts) ? entity.contacts.filter(c => c).join(',') : entity.contacts;

    return entity;
}