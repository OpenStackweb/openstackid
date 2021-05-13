import request from 'superagent';
import URI from "urijs";
let http = request;
import Swal from 'sweetalert2';

export const createAction = type => payload => ({
    type,
    payload
});

export const RESET_LOADING  = 'RESET_LOADING';
export const START_LOADING  = 'START_LOADING';
export const STOP_LOADING   = 'STOP_LOADING';

export const resetLoading = createAction(RESET_LOADING);
export const startLoading = createAction(START_LOADING);
export const stopLoading  = createAction(STOP_LOADING);

const xhrs = {};

const cancel = (key) => {
    if(xhrs[key]) {
        xhrs[key].abort();
        console.log(`aborted request ${key}`);
        delete xhrs[key];
    }
}

const schedule = (key, req) => {
    // console.log(`scheduling ${key}`);
    xhrs[key] = req;
};

const isObjectEmpty = (obj) => {
    return Object.keys(obj).length === 0 && obj.constructor === Object ;
}

export const getRawRequest = (endpoint, errorHandler = null) => (params) => {
    let url = URI(endpoint);

    if(!isObjectEmpty(params))
        url = url.query(params);

    let key = url.toString();

    cancel(key);

    return new Promise((resolve, reject) => {
        let req = http.get(url.toString())
            .timeout({
                response: 60000,
                deadline: 60000,
            })
            .end(
                (err, res) => {
                    if (err || !res.ok) {
                        if(errorHandler) {
                            errorHandler(err, res);
                        }
                        return reject({ err, res })
                    }
                    let json = res.body;
                    return resolve({response: json});
                }
            )

        schedule(key, req);
    });
}

