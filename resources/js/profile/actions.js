import {getRawRequest, putFile, putRawRequest} from "../base_actions";
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

export const getUserActions = async (page = 1, order = 'created_at', orderDir = 'desc', filters = {}, userId) => {
    const params = {
        page: page,
        per_page: PAGE_SIZE,
    };

    const filter = parseFilter(filters);
    filter.push(`owner_id==${userId}`)

    if (filter.length > 0) {
        params['filter[]'] = filter;
    }

    // order
    if (order != null && orderDir != null) {
        const orderDirSign = (orderDir === 'asc') ? '+' : '-';
        params['order'] = `${orderDirSign}${order}`;
    }

    const {response} = await getRawRequest(window.GET_USER_ACTIONS_ENDPOINT)(params);
    return response;
}

export const save = async (entity, pic, token) => {

    return putRawRequest(window.SAVE_PROFILE_ENDPOINT)(normalizeEntity(entity), {'X-CSRF-TOKEN': token}).then(() => {
        if (pic) {
            return putFile(window.SAVE_PIC_ENDPOINT)(pic, 'pic', {'X-CSRF-TOKEN': token});
        }
        return Promise.resolve();
    });
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