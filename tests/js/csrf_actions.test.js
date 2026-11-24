/**
 * @jest-environment jsdom
 */

// Every state-changing call of the admin / profile React pages must send the CSRF token as the
// X-CSRF-TOKEN *header*: Laravel's VerifyCsrfToken only reads the `_token` input or that header,
// so a token passed as a JSON body field, a query parameter or a multipart field answers 419.
// superagent is mocked so the test sees exactly what each action puts on the wire, independently
// of the argument order of the base_actions helpers.

const mockRequests = [];

jest.mock('superagent', () => {
  const build = (method) => (url) => {
    const req = {method, url: String(url), headers: {}, body: undefined, fields: {}, files: {}};
    mockRequests.push(req);
    const chain = {
      set: (h) => { Object.assign(req.headers, h); return chain; },
      send: (b) => { req.body = b; return chain; },
      field: (k, v) => { req.fields[k] = v; return chain; },
      attach: (k, f) => { req.files[k] = f; return chain; },
      timeout: () => Promise.resolve({body: {}}),
    };
    return chain;
  };
  return {get: build('GET'), post: build('POST'), put: build('PUT'), delete: build('DELETE')};
});

import * as profile from '../../resources/js/profile/actions';
import * as editUser from '../../resources/js/admin/edit_user/actions';
import * as clients from '../../resources/js/oauth2/profile/clients/actions';
import * as editClient from '../../resources/js/oauth2/profile/edit_client/actions';

const TOKEN = 'tok-123';
const PIC = new Blob(['x'], {type: 'image/png'});

const profileEntity = () => ({first_name: 'a'});
const adminUserEntity = () => ({first_name: 'a', post_code: 1824, groups: ['1']});

beforeAll(() => {
  Object.assign(window, {
    CSFR_TOKEN: TOKEN,
    SAVE_PROFILE_ENDPOINT: '/admin/api/v1/users/me',
    SAVE_PIC_ENDPOINT: '/admin/api/v1/users/me/pic',
    REVOKE_ACCESS_TOKENS_ENDPOINT: '/admin/api/v1/users/me/tokens/@value?hint=%40hint',
    REVOKE_ALL_TOKENS_ENDPOINT: '/admin/api/v1/users/me/tokens',
    ADD_CLIENT_ENDPOINT: '/admin/api/v1/clients',
    DELETE_CLIENT_ENDPOINT: '/admin/api/v1/clients/@id',
    ACTIVATE_CLIENT_ENDPOINT: '/admin/api/v1/clients/@id/active',
    DEACTIVATE_CLIENT_ENDPOINT: '/admin/api/v1/clients/@id/active',
    REGENERATE_CLIENT_SECRET_ENDPOINT: '/admin/api/v1/clients/@client_id/secret',
    REVOKE_TOKENS_ENDPOINT: '/admin/api/v1/clients/@client_id/token/@value/@hint',
    UPDATE_CLIENT_DATA_ENDPOINT: '/admin/api/v1/clients/@client_id',
    ADD_CLIENT_SCOPE_ENDPOINT: '/admin/api/v1/clients/@client_id/scopes/@scope_id',
    REMOVE_CLIENT_SCOPE_ENDPOINT: '/admin/api/v1/clients/@client_id/scopes/@scope_id',
    ADD_PUBLIC_KEY_ENDPOINT: '/admin/api/v1/clients/@client_id/public_keys',
    REMOVE_PUBLIC_KEY_ENDPOINT: '/admin/api/v1/clients/@client_id/public_keys/@public_key_id',
    CLIENT_TYPES: {}, APP_TYPES: {},
  });
});

beforeEach(() => { mockRequests.length = 0; });

const CASES = [
  ['profile: save with picture', () => profile.save(profileEntity(), PIC, TOKEN), 2],
  ['profile: revoke token', () => profile.revokeToken('v', 'access_token'), 1],
  ['profile: revoke all tokens', () => profile.revokeAllTokens(), 1],
  ['admin edit user: save with picture', () => editUser.save(adminUserEntity(), PIC, TOKEN), 2],
  ['clients: add', () => clients.addClient({app_name: 'x'}, TOKEN), 1],
  ['clients: delete', () => clients.deleteClient(1, TOKEN), 1],
  ['clients: activate', () => clients.activateClient(1, TOKEN), 1],
  ['clients: deactivate', () => clients.deactivateClient(1, TOKEN), 1],
  ['edit client: regenerate secret', () => editClient.regenerateClientSecret(1), 1],
  ['edit client: revoke token', () => editClient.revokeToken(1, 'v', 'access_token'), 1],
  ['edit client: update data', () => editClient.updateClientData(1, {app_name: 'x'}, editClient.ClientEntitySection.OAUTH), 1],
  ['edit client: add scope', () => editClient.addScope(1, 2), 1],
  ['edit client: remove scope', () => editClient.removeScope(1, 2), 1],
  ['edit client: add public key', () => editClient.addPublicKey(1, {kid: 'k'}), 1],
  ['edit client: remove public key', () => editClient.removePublicKey(1, 2), 1],
];

describe.each(CASES)('%s', (name, run, expectedRequests) => {
  it('sends X-CSRF-TOKEN as a header on every request', async () => {
    await run();
    expect(mockRequests).toHaveLength(expectedRequests);
    for (const req of mockRequests) {
      expect(req.method).not.toBe('GET');
      expect(req.headers['X-CSRF-TOKEN']).toBe(TOKEN);
    }
  });

  it('does not leak the token into the body, query string or multipart fields', async () => {
    await run();
    for (const req of mockRequests) {
      expect(req.url).not.toContain(TOKEN);
      expect(JSON.stringify(req.body ?? null)).not.toContain(TOKEN);
      expect(req.fields).not.toHaveProperty('X-CSRF-TOKEN');
    }
  });
});
