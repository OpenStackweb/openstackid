import {getRawRequest, putRawRequest} from "../../base_actions";
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
    return filter;
}

export const getUserActions = async (page = 1, order = 'created_at', orderDir = 'asc', filters = {}, userId) => {
    const params = {
        page: page,
        per_page: PAGE_SIZE,
    };

    const filter = parseFilter(filters);
    filter.push(`owner==${userId}`)

    params['filter[]'] = filter;

    // order
    if (order != null && orderDir != null) {
        const orderDirSign = (orderDir === 'asc') ? '+' : '-';
        params['order'] = `${orderDirSign}${order}`;
    }

    const {response} = await getRawRequest(window.GET_USER_ACTIONS_ENDPOINT)(params);
    return response;
}

export const fetchGroups = async (url, query) => {
    const params = {
        page: 1,
        per_page: 10,
    };
    const filter = [];
    filter.push(`name=@${query}`);
    filter.push(`slug=@${query}`);
    filter.push(`active==1`);
    params['filter[]'] = filter;
    params['order'] = 'name,slug';

    const {response} = await getRawRequest(url)(params);
    return response.data;
}

export const save = async (values, token) => {
    values.public_profile_show_photo = values.public_profile_show_photo ? 1 : 0;
    values.public_profile_show_fullname = values.public_profile_show_fullname ? 1 : 0;
    values.public_profile_show_email = values.public_profile_show_email ? 1 : 0;
    values.public_profile_allow_chat_with_me = values.public_profile_allow_chat_with_me ? 1 : 0;
    values.active = values.active ? 1 : 0;
    values.email_verified = values.email_verified ? 1 : 0;
    if (values.birthday) {
        values.birthday = moment(`${values.birthday} 12:00`).unix();
    }
    values['groups[]'] = values.groups

    return putRawRequest(window.SAVE_PROFILE_ENDPOINT)(values, {'X-CSRF-TOKEN': token});
}