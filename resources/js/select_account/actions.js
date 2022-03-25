import {createAction} from '../base_actions';
import axios from 'axios';

export const LOAD_FORMER_ACCOUNTS = 'LOAD_FORMER_ACCOUNTS';
export const REQUEST_REMOVE_FORMER_ACCOUNT = 'REQUEST_REMOVE_FORMER_ACCOUNT';
export const RESPONSE_REMOVE_FORMER_ACCOUNT = 'RESPONSE_REMOVE_FORMER_ACCOUNT';
export const ERROR_REMOVE_FORMER_ACCOUNT = 'ERROR_REMOVE_FORMER_ACCOUNT';

export const loadFormerAccounts = (formerAccounts) => (dispatch) => {
    dispatch(createAction(LOAD_FORMER_ACCOUNTS)({formerAccounts}));
}

export const removeFormerAccount = (username) => (dispatch, getState, {csrf_token}) => {

    const params = {
        username: username,
    }

    dispatch(createAction(REQUEST_REMOVE_FORMER_ACCOUNT)({}));

    return axios.delete(window.REMOVE_FORMER_ACCOUNT_ENDPOINT, {
        params,
        headers:{'X-CSRF-TOKEN': csrf_token},
    })
        .then(res => {
            console.log(res);
            console.log(res.data);
            dispatch(createAction(RESPONSE_REMOVE_FORMER_ACCOUNT)({username}));
            return Promise.resolve(res);
        }).catch((e) => {
            console.log(e);
            dispatch(createAction(ERROR_REMOVE_FORMER_ACCOUNT)({}));
            return Promise.reject(e);
        })
}


