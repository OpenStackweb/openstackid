import {getRawRequest, postRawRequest, putRawRequest, deleteRawRequest} from "../../../base_actions";

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

export const getClients = async (page = 1, order = 'updated_at', orderDir = 'desc', filters = {}, userId) => {
    const params = {
        page: page,
        per_page: PAGE_SIZE,
    };

    const filter = parseFilter(filters);

    if (filter.length > 0) {
        params['filter[]'] = filter;
    }

    // order
    if (order != null && orderDir != null) {
        const orderDirSign = (orderDir === 'asc') ? '+' : '-';
        params['order'] = `${orderDirSign}${order}`;
    }

    const {response} = await getRawRequest(window.GET_CLIENTS_ENDPOINT)(params);
    return response;
}

export const addClient = async (entity, token) => {
    return postRawRequest(window.ADD_CLIENT_ENDPOINT)(normalizeEntity(entity), {}, {'X-CSRF-TOKEN': token});
}

export const deleteClient = async (id, token) => {
    return deleteRawRequest(window.DELETE_CLIENT_ENDPOINT.replace('@id', id))({'X-CSRF-TOKEN': token});
}

export const activateClient = async (id, token) => {
    return putRawRequest(window.ACTIVATE_CLIENT_ENDPOINT.replace('@id', id))({}, {}, {'X-CSRF-TOKEN': token});
}

export const deactivateClient = async (id, token) => {
    return deleteRawRequest(window.DEACTIVATE_CLIENT_ENDPOINT.replace('@id', id))({'X-CSRF-TOKEN': token});
}

const normalizeEntity = (entity) => {
    const normEntity = {...entity}

    normEntity.active = normEntity.active ? 1 : 0;

    if (normEntity.admin_users) {
        normEntity.admin_users = entity.admin_users.map((au) => au.id);
    }

    return normEntity;
}