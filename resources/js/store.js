import { createStore, applyMiddleware, compose} from 'redux';
import thunk from 'redux-thunk';
import { persistStore, persistCombineReducers } from 'redux-persist'
import storage from 'redux-persist/es/storage'

const config = {
    key: 'root_idp',
    storage,
};

// reducers

import selectAccountReducer from '../js/select_account/reducer';

const reducers = persistCombineReducers(config, {
    selectAccountState: selectAccountReducer,
});

const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;

const store = createStore(reducers, composeEnhancers(applyMiddleware(thunk.withExtraArgument({
    csrf_token : document.head.querySelector('meta[name="csrf-token"]').content
}))));

const onRehydrateComplete = () => {

}

export const persistor = persistStore(store, null, onRehydrateComplete);
export default store;
