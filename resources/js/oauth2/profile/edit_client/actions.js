import {getRawRequest, postRawRequest, putRawRequest, deleteRawRequest} from "../../../base_actions";

export const PAGE_SIZE = 30;

export const ClientEntitySection = {
    LOGOUT_OPTIONS: "LOGOUT_OPTIONS",
    OAUTH: "OAUTH",
    PUBLIC_KEYS: "PUBLIC_KEYS",
    SECURITY_SETTINGS: "SECURITY_SETTINGS"
}

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

export const updateClientData = async (clientId, entity, entitySection) => {
    return putRawRequest(window.UPDATE_CLIENT_DATA_ENDPOINT
        .replace('@client_id', clientId))(normalizeEntity(entity, entitySection), {}, {'X-CSRF-TOKEN': window.CSFR_TOKEN});
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
        .replace('@client_id', clientId))(normalizeEntity(entity, ClientEntitySection.PUBLIC_KEYS), {}, {'X-CSRF-TOKEN': window.CSFR_TOKEN});
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

const normalizePKCEDependencies = (entity) => {
    if (!entity.pkce_enabled) {
        entity.use_refresh_token = 0;
        entity.rotate_refresh_token = 0;
    }
    return entity;
}

const normalizeEntity = (entity, entitySection) => {
    let normEntity = {};
    const clientTypes = window.CLIENT_TYPES;
    const appTypes = window.APP_TYPES;

    normEntity.application_type = entity.application_type;

    switch (entitySection) {
        case ClientEntitySection.LOGOUT_OPTIONS:
            normEntity.logout_uri = entity.logout_uri;
            normEntity.logout_session_required = entity.logout_session_required ? 1 : 0;
            normEntity.logout_use_iframe = entity.logout_use_iframe ? 1 : 0;
            normEntity.post_logout_redirect_uris = Array.isArray(entity.post_logout_redirect_uris) ?
                entity.post_logout_redirect_uris.filter(r => r).join(',') : entity.post_logout_redirect_uris;
            break;
        case ClientEntitySection.OAUTH:
            normEntity.client_id = entity.client_id;
            normEntity.client_secret = entity.client_secret;
            normEntity.contacts = Array.isArray(entity.contacts) ? entity.contacts.filter(c => c).join(',') : entity.contacts;
            normEntity.use_refresh_token = entity.use_refresh_token ? 1 : 0;
            normEntity.rotate_refresh_token = entity.rotate_refresh_token ? 1 : 0;
            normEntity.app_name = entity.app_name;
            normEntity.app_description = entity.app_description;
            if (entity.admin_users) {
                normEntity.admin_users = entity.admin_users.map((au) => au.id);
            }
            normEntity.website = entity.website;
            normEntity.logo_uri = entity.logo_uri;
            normEntity.tos_uri = entity.tos_uri;
            normEntity.policy_uri = entity.policy_uri;
            normEntity.redirect_uris = Array.isArray(entity.redirect_uris) ?
                entity.redirect_uris.filter(r => r).join(',') : entity.redirect_uris;
            normEntity.allowed_origins = Array.isArray(entity.allowed_origins) ?
                entity.allowed_origins.filter(a => a).join(',') : entity.allowed_origins;

            if (entity.client_type === clientTypes.Public) {
                normEntity.pkce_enabled = entity.pkce_enabled ? 1 : 0;
                normEntity = normalizePKCEDependencies(normEntity);
            }
            break;
        case ClientEntitySection.PUBLIC_KEYS:
            normEntity.kid = entity.kid;
            normEntity.valid_from = entity.valid_from;
            normEntity.valid_to = entity.valid_to;
            normEntity.active = entity.active;
            normEntity.usage = entity.usage;
            normEntity.alg = entity.alg;
            normEntity.type = entity.type;
            normEntity.pem_content = entity.pem_content;
            break;
        case ClientEntitySection.SECURITY_SETTINGS:
            if (entity.client_type === clientTypes.Public) {
                normEntity.pkce_enabled = entity.pkce_enabled ? 1 : 0;
                normEntity = normalizePKCEDependencies(normEntity);
            }
            normEntity.otp_enabled = 0;
            if (entity.otp_enabled) {
                normEntity.otp_enabled = 1;
                normEntity.otp_length = entity.otp_length;
                normEntity.otp_lifetime = entity.otp_lifetime;
            }

            if ([appTypes.JSClient, appTypes.Native, appTypes.WebApp].includes(entity.application_type))
                 normEntity.max_allowed_user_sessions = entity.max_allowed_user_sessions;

            normEntity.default_max_age = entity.default_max_age;
            normEntity.token_endpoint_auth_signing_alg = entity.token_endpoint_auth_signing_alg;
            normEntity.token_endpoint_auth_method = entity.token_endpoint_auth_method;
            normEntity.subject_type = entity.subject_type;
            normEntity.jwks_uri = entity.jwks_uri;
            normEntity.userinfo_signed_response_alg = entity.userinfo_signed_response_alg;
            normEntity.id_token_signed_response_alg = entity.id_token_signed_response_alg;
            normEntity.userinfo_encrypted_response_alg = entity.userinfo_encrypted_response_alg;
            normEntity.id_token_encrypted_response_alg = entity.id_token_encrypted_response_alg;
            normEntity.userinfo_encrypted_response_enc = entity.userinfo_encrypted_response_enc;
            normEntity.id_token_encrypted_response_enc = entity.id_token_encrypted_response_enc;
            break;
    }

    return normEntity;
}