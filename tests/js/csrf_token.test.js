/**
 * @jest-environment jsdom
 */
import $ from 'jquery';

jest.mock('../../resources/js/base_actions', () => ({
  putRawRequest: jest.fn(() => jest.fn(() => Promise.resolve())),
  putFile: jest.fn(() => jest.fn(() => Promise.resolve())),
  deleteRawRequest: jest.fn(() => jest.fn(() => Promise.resolve())),
  getRawRequest: jest.fn(),
}));

import { putRawRequest, putFile } from '../../resources/js/base_actions';
import { save } from '../../resources/js/profile/actions';

const TOKEN = 'tok-123';

describe('profile save', () => {
  it('sends the CSRF token as a header, not as a query parameter', async () => {
    const put = jest.fn(() => Promise.resolve());
    putRawRequest.mockReturnValue(put);
    putFile.mockReturnValue(jest.fn(() => Promise.resolve()));

    await save({first_name: 'a'}, null, TOKEN);

    // putRawRequest(endpoint)(payload, params, headers)
    const [, params, headers] = put.mock.calls[0];
    expect(params).toEqual({});
    expect(headers).toEqual({'X-CSRF-TOKEN': TOKEN});
  });
});

describe('jQuery CSRF prefilter (public/assets/js/ajax.utils.js)', () => {
  let requests;

  beforeEach(() => {
    jest.resetModules();
    document.head.innerHTML = `<meta name="csrf-token" content="${TOKEN}">`;
    window.jQuery = window.$ = $;
    requests = [];
    $.ajaxTransport('+*', (options) => ({
      send: (headers, complete) => {
        requests.push({type: options.type, url: options.url, headers: {...headers}});
        complete(200, 'OK', {text: '{}'});
      },
      abort: () => {},
    }));
    require('../../public/assets/js/ajax.utils.js');
  });

  it.each(['POST', 'PUT', 'DELETE'])('adds X-CSRF-TOKEN to same-origin %s', async (type) => {
    await $.ajax({url: '/admin/api/v1/x', type});
    expect(requests[0].headers['X-CSRF-TOKEN']).toBe(TOKEN);
  });

  it('does not add the token to GET requests', async () => {
    await $.ajax({url: '/admin/api/v1/x', type: 'GET'});
    expect(requests[0].headers['X-CSRF-TOKEN']).toBeUndefined();
  });

  it('never leaks the token to another origin', async () => {
    await $.ajax({url: 'https://evil.example/x', type: 'POST', crossDomain: true});
    expect(requests[0].headers['X-CSRF-TOKEN']).toBeUndefined();
  });
});
