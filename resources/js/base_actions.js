import request from 'superagent';
import URI from "urijs";

let http = request;

export const createAction = type => payload => ({
    type,
    payload
});

export const RESET_LOADING = 'RESET_LOADING';
export const START_LOADING = 'START_LOADING';
export const STOP_LOADING = 'STOP_LOADING';

export const resetLoading = createAction(RESET_LOADING);
export const startLoading = createAction(START_LOADING);
export const stopLoading = createAction(STOP_LOADING);

const xhrs = {};

const cancel = (key) => {
    if (xhrs[key]) {
        xhrs[key].xhr.abort();
        console.log(`aborted request ${key}`);
        delete xhrs[key];
    }
}

const schedule = (key, req) => {
    // console.log(`scheduling ${key}`);
    xhrs[key] = req;
};

const end = (key) => {
    delete xhrs[key];
}

const isObjectEmpty = (obj) => {
    return Object.keys(obj).length === 0 && obj.constructor === Object;
}

export const getRawRequest = (endpoint) => (params) => {
    let url = URI(endpoint);

    if (!isObjectEmpty(params))
        url = url.query(params);

    let key = url.toString();

    cancel(key);

    let req = http.get(url.toString());
    schedule(key, req);

    return req.timeout({
        response: 60000,
        deadline: 60000,
    }).then((res) => {
        let json = res.body;
        end(key);
        return Promise.resolve({response: json});
    }).catch((error) => {
        end(key);
        return Promise.reject(error);
    })
}

export const postRawRequest = (endpoint) => (params, headers = {}) => {
    let url = URI(endpoint);

    if (!isObjectEmpty(params))
        url = url.query(params);

    let key = url.toString();

    cancel(key);

    let req = http.post(url.toString());

    schedule(key, req);

    return req.set(headers).send(params).timeout({
        response: 60000,
        deadline: 60000,
    }).then((res) => {
        let json = res.body;
        end(key);
        return Promise.resolve({response: json});
    }).catch((error) => {
        end(key);
        return Promise.reject(error);
    })
}

export const putRawRequest = (endpoint) => (payload = null, params={}, headers = {}) => {
    let url = URI(endpoint);

    if (!isObjectEmpty(params))
        url = url.query(params);

    let key = url.toString();

    cancel(key);

    let req = http.put(url.toString());

    schedule(key, req);

    if(payload != null)
        req.send(payload);

    return req.set(headers).timeout({
        response: 60000,
        deadline: 60000,
    }).then((res) => {
        let json = res.body;
        end(key);
        return Promise.resolve({response: json});
    }).catch((error) => {
        end(key);
        return Promise.reject(error);
    })
}

export const postFile = (
    endpoint
) => (file, fileParamName = 'file',
      fileMetadata = {},
      headers = {}
) => {

    let url = URI(endpoint);

    let key = url.toString();

    cancel(key);

    let req = http.post(url);
    if (file)
        req = req.attach(fileParamName, file);

    schedule(key, req);

    if (!isObjectEmpty(fileMetadata)) {
        Object.keys(fileMetadata).forEach(function (key) {
            let value = fileMetadata[key];
            req.field(key, value);
        });
    }

    return req.set(headers).timeout({
        response: 60000,
        deadline: 60000,
    }).then((res) => {
        let json = res.body;
        end(key);
        return Promise.resolve({response: json});
    }).catch((error) => {
        end(key);
        return Promise.reject(error);
    })

};

export const putFile = (endpoint) => (file, fileParamName = 'file', fileMetadata = {}, headers = {}) => {

    let url = URI(endpoint);

    let key = url.toString();

    cancel(key);

    let req = http.put(url);

    if (file)
        req = req.attach(fileParamName, file);

    schedule(key, req);

    if (!isObjectEmpty(fileMetadata)) {
        Object.keys(fileMetadata).forEach(function (key) {
            let value = fileMetadata[key];
            req.field(key, value);
        });
    }

    return req.set(headers).timeout({
        response: 60000,
        deadline: 60000,
    }).then((res) => {
        let json = res.body;
        end(key);
        return Promise.resolve({response: json});
    }).catch((error) => {
        end(key);
        return Promise.reject(error);
    })

};
