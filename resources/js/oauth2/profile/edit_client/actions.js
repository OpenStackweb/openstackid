import {getRawRequest, putRawRequest, deleteRawRequest} from "../../../base_actions";

export const PAGE_SIZE = 30;

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

export const addScope = async (clientId, scopeId, token) => {
    return putRawRequest(window.ADD_CLIENT_SCOPE_ENDPOINT.replace('@client_id', clientId).replace('@scope_id', scopeId))({}, {}, {'X-CSRF-TOKEN': token});
}

export const removeScope = async (clientId, scopeId, token) => {
    return deleteRawRequest(window.REMOVE_CLIENT_SCOPE_ENDPOINT.replace('@client_id', clientId).replace('@scope_id', scopeId))({'X-CSRF-TOKEN': token});
}