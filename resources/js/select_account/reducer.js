import {LOAD_FORMER_ACCOUNTS, RESPONSE_REMOVE_FORMER_ACCOUNT} from "./actions";

const DEFAULT_STATE = {
    accounts : [],
};

const selectAccountReducer = (state = DEFAULT_STATE, action) => {

    const { type, payload } = action

    switch(type){
        case LOAD_FORMER_ACCOUNTS:
            const {formerAccounts} = payload;
            return {...state, accounts: formerAccounts};
        case RESPONSE_REMOVE_FORMER_ACCOUNT:
            const { username } = payload;
            return {...state, accounts: state.accounts.filter((a) =>  a.username !== username )}
        default:
            return state;
            break;
    }
}

export default selectAccountReducer
