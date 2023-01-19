import {getRawRequest, putRawRequest} from "../base_actions";
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
    }

    console.log('filters', filter)

    return filter;
}

export const getUserActions = async (page = 1, order = 'created_at', orderDir = 'asc', filters = {}) => {
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

    const {response} = await getRawRequest(window.GET_USER_ACTIONS_ENDPOINT)(params);
    return response;
}

export const save = async (values, token) => {
    values.public_profile_show_photo = values.public_profile_show_photo ? 1 : 0;
    values.public_profile_show_fullname = values.public_profile_show_fullname ? 1 : 0;
    values.public_profile_show_email = values.public_profile_show_email ? 1 : 0;
    values.public_profile_allow_chat_with_me = values.public_profile_allow_chat_with_me ? 1 : 0;
    values.birthday = moment(`${values.birthday} 12:00`).unix();

    return putRawRequest(window.SAVE_PROFILE_ENDPOINT)(values, {'X-CSRF-TOKEN': token});
}